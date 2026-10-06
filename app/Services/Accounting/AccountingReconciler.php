<?php

namespace App\Services\Accounting;

use Throwable;

/**
 * Brings existing documents in line with the ledger through the bridge. Changes are
 * made only by posting and reversing vouchers; existing rows are never edited away.
 */
class AccountingReconciler
{
    public function __construct(
        private AccountingIntegrity $integrity,
        private OperationalAccountingBridge $bridge,
    ) {}

    /**
     * @param  callable(string): void|null  $onError
     * @return array{documents: int, post: int, repost: int, reverse: int, unchanged: int, none: int, orphans: int, links: int, changed: int, errors: int}
     */
    public function run(int $companyId, ?string $eventKey = null, bool $dryRun = true, ?callable $onError = null): array
    {
        $summary = ['documents' => 0, 'post' => 0, 'repost' => 0, 'reverse' => 0, 'unchanged' => 0, 'none' => 0, 'orphans' => 0, 'links' => 0, 'changed' => 0, 'errors' => 0];
        $onlyEvents = $eventKey !== null ? [$eventKey] : null;

        foreach ($this->integrity->documents($companyId, $eventKey) as $document) {
            $summary['documents']++;

            try {
                $preview = $this->bridge->preview($document);

                foreach ($preview['events'] as $key => $action) {
                    if ($eventKey === null || $key === $eventKey) {
                        $summary[$action]++;
                    }
                }
                $summary['orphans'] += $eventKey === null ? $preview['orphans'] : 0;
                $summary['links'] += $preview['unlinked'];

                if (! $dryRun && $this->needsSync($preview, $eventKey)) {
                    $summary['changed'] += $this->bridge->sync($document, $onlyEvents);
                }
            } catch (Throwable $e) {
                $summary['errors']++;
                if ($onError) {
                    $onError(class_basename($document).' #'.$document->getKey().': '.$e->getMessage());
                }
                report($e);
            }
        }

        return $summary;
    }

    /**
     * @param  array{events: array<string, string>, orphans: int, unlinked: int}  $preview
     */
    private function needsSync(array $preview, ?string $eventKey): bool
    {
        foreach ($preview['events'] as $key => $action) {
            if (($eventKey === null || $key === $eventKey) && in_array($action, ['post', 'repost', 'reverse'], true)) {
                return true;
            }
        }

        return $preview['unlinked'] > 0 || ($eventKey === null && $preview['orphans'] > 0);
    }
}
