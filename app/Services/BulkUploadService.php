<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkUploadService
{
    /**
     * Process an uploaded CSV file against a model and column specification.
     *
     * @param  class-string  $modelClass
     * @param  array<string, array{
     *     label: string,
     *     required?: bool,
     *     aliases?: list<string>,
     *     rules?: list<string>|string,
     *     unique?: bool,
     *     transform?: callable
     * }>  $columnSpecs
     * @return array{
     *     success: bool,
     *     summary: array{
     *         total: int,
     *         successful: int,
     *         failed: int,
     *         skipped: int
     *     },
     *     errors: list<array{
     *         row: int,
     *         field: string,
     *         value: string,
     *         message: string
     *     }>,
     *     message: string
     * }
     */
    public function process(
        UploadedFile $file,
        string $modelClass,
        array $columnSpecs,
        ?callable $beforeSave = null,
        ?callable $rowValidator = null,
        ?callable $saveHandler = null
    ): array {
        $path = $file->getRealPath();
        if (! $path || ! file_exists($path)) {
            return [
                'success' => false,
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0, 'skipped' => 0],
                'errors' => [
                    [
                        'row' => 1,
                        'field' => 'File',
                        'value' => $file->getClientOriginalName(),
                        'message' => 'Uploaded file could not be read or is empty.',
                    ],
                ],
                'message' => 'Failed to read uploaded file.',
            ];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
            return [
                'success' => false,
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0, 'skipped' => 0],
                'errors' => [
                    [
                        'row' => 1,
                        'field' => 'File',
                        'value' => $file->getClientOriginalName(),
                        'message' => 'Unsupported file format. Please upload a CSV (.csv) or Excel (.xlsx, .xls) file.',
                    ],
                ],
                'message' => 'Unsupported file format. Please upload a CSV or Excel file.',
            ];
        }

        $sheetData = $this->readSheet($path, $extension);
        if (! $sheetData || empty($sheetData['headers'])) {
            return [
                'success' => false,
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0, 'skipped' => 0],
                'errors' => [
                    [
                        'row' => 1,
                        'field' => 'Header',
                        'value' => '',
                        'message' => 'The uploaded file is empty or does not contain a valid header row.',
                    ],
                ],
                'message' => 'The uploaded file does not contain valid headers.',
            ];
        }

        $rawHeaders = $sheetData['headers'];
        $rows = $sheetData['rows'];

        $headerMap = $this->mapHeaders($rawHeaders, $columnSpecs);

        // Verify that all required columns are present in the header
        $missingRequired = [];
        foreach ($columnSpecs as $field => $spec) {
            if (! empty($spec['required']) && ! isset($headerMap[$field])) {
                $missingRequired[] = $spec['label'] ?? $field;
            }
        }

        if (! empty($missingRequired)) {
            return [
                'success' => false,
                'summary' => ['total' => 0, 'successful' => 0, 'failed' => 0, 'skipped' => 0],
                'errors' => [
                    [
                        'row' => 1,
                        'field' => 'Header',
                        'value' => implode(', ', $rawHeaders),
                        'message' => 'Missing required column(s): '.implode(', ', $missingRequired).'. Please use the downloaded template.',
                    ],
                ],
                'message' => 'Missing required column(s): '.implode(', ', $missingRequired).'.',
            ];
        }

        $total = 0;
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];
        $validRecords = [];

        // Track seen unique values within this file batch
        $seenInBatch = [];
        foreach ($columnSpecs as $field => $spec) {
            if (! empty($spec['unique'])) {
                $seenInBatch[$field] = [];
            }
        }

        $rowNumber = 1; // 1 is header row

        foreach ($rows as $row) {
            $rowNumber++;

            // Check if entire row is empty (whitespace only or empty cells)
            if ($this->isRowEmpty($row)) {
                $skipped++;

                continue;
            }

            $total++;
            $rowErrors = [];
            $rowData = [];

            // Extract mapped fields
            foreach ($columnSpecs as $field => $spec) {
                $colIndex = $headerMap[$field] ?? null;
                $rawVal = ($colIndex !== null && isset($row[$colIndex])) ? trim((string) $row[$colIndex]) : '';

                if (isset($spec['transform']) && is_callable($spec['transform'])) {
                    $val = ($spec['transform'])($rawVal);
                } else {
                    $val = $rawVal;
                }

                $rowData[$field] = $val;

                // Validation rules
                if (! empty($spec['rules'])) {
                    $validator = Validator::make(
                        [$field => $val],
                        [$field => $spec['rules']],
                        [],
                        [$field => $spec['label'] ?? $field]
                    );

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $msg) {
                            $rowErrors[] = [
                                'row' => $rowNumber,
                                'field' => $spec['label'] ?? $field,
                                'value' => $rawVal,
                                'message' => $msg,
                            ];
                        }
                    }
                }

                // Check uniqueness in file batch
                if (! empty($spec['unique']) && $val !== '') {
                    $normalizedVal = mb_strtolower($val, 'UTF-8');
                    if (isset($seenInBatch[$field][$normalizedVal])) {
                        $prevRow = $seenInBatch[$field][$normalizedVal];
                        $rowErrors[] = [
                            'row' => $rowNumber,
                            'field' => $spec['label'] ?? $field,
                            'value' => $rawVal,
                            'message' => sprintf(
                                'Duplicate %s "%s" in uploaded file (first seen on row %d).',
                                $spec['label'] ?? $field,
                                $val,
                                $prevRow
                            ),
                        ];
                    } else {
                        $seenInBatch[$field][$normalizedVal] = $rowNumber;
                    }

                    // Check uniqueness in database (scoped to active tenant owner by model traits)
                    if (empty($rowErrors)) {
                        $exists = $modelClass::query()->where($field, $val)->exists();
                        if ($exists) {
                            $rowErrors[] = [
                                'row' => $rowNumber,
                                'field' => $spec['label'] ?? $field,
                                'value' => $rawVal,
                                'message' => sprintf(
                                    '%s "%s" already exists in records.',
                                    $spec['label'] ?? $field,
                                    $val
                                ),
                            ];
                        }
                    }
                }
            }

            // Custom row-level validator/hook
            if (empty($rowErrors) && $rowValidator !== null) {
                $customErrors = $rowValidator($rowData, $rowNumber);
                if (! empty($customErrors) && is_array($customErrors)) {
                    foreach ($customErrors as $err) {
                        if (is_array($err)) {
                            $rowErrors[] = $err;
                        } else {
                            $rowErrors[] = [
                                'row' => $rowNumber,
                                'field' => 'Row',
                                'value' => '',
                                'message' => (string) $err,
                            ];
                        }
                    }
                }
            }

            if (! empty($rowErrors)) {
                $failed++;
                foreach ($rowErrors as $err) {
                    $errors[] = $err;
                }
            } else {
                $validRecords[] = [
                    'row' => $rowNumber,
                    'data' => $rowData,
                ];
            }
        }

        // Persist valid records in database
        if (! empty($validRecords)) {
            DB::transaction(function () use ($validRecords, $modelClass, $beforeSave, $saveHandler, &$successful, &$failed, &$errors) {
                foreach ($validRecords as $item) {
                    try {
                        $data = $item['data'];
                        if ($beforeSave) {
                            $data = $beforeSave($data);
                        }
                        if ($saveHandler) {
                            $saveHandler($data, $item['row']);
                        } else {
                            $modelClass::create($data);
                        }
                        $successful++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $errors[] = [
                            'row' => $item['row'],
                            'field' => 'Database',
                            'value' => json_encode($item['data']),
                            'message' => 'Database save error: '.$e->getMessage(),
                        ];
                    }
                }
            });
        }

        $message = sprintf(
            'Processed %d row%s: %d imported successfully, %d failed, %d empty row%s skipped.',
            $total,
            $total === 1 ? '' : 's',
            $successful,
            $failed,
            $skipped,
            $skipped === 1 ? '' : 's'
        );

        return [
            'success' => $failed === 0,
            'summary' => [
                'total' => $total,
                'successful' => $successful,
                'failed' => $failed,
                'skipped' => $skipped,
            ],
            'errors' => $errors,
            'message' => $message,
        ];
    }

    /**
     * Generate a downloadable CSV template response.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $sampleRows
     */
    public function downloadTemplate(array $headers, array $sampleRows, string $fileName): StreamedResponse
    {
        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($headers, $sampleRows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, $headers);

            foreach ($sampleRows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $responseHeaders);
    }

    /**
     * Detect CSV delimiter from comma, semicolon, or tab.
     */
    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ',';
        }

        $line = fgets($handle);
        fclose($handle);

        if (! $line) {
            return ',';
        }

        $delimiters = [',', ';', "\t"];
        $counts = [];

        foreach ($delimiters as $del) {
            $counts[$del] = substr_count($line, $del);
        }

        arsort($counts);
        $best = key($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    /**
     * Map raw header columns to specified column keys using aliases and fuzzy matching.
     *
     * @param  list<string>  $rawHeaders
     * @param  array<string, array{label: string, aliases?: list<string>}>  $specs
     * @return array<string, int>
     */
    private function mapHeaders(array $rawHeaders, array $specs): array
    {
        $map = [];
        $normalizedRaw = [];

        foreach ($rawHeaders as $idx => $header) {
            $normalizedRaw[$idx] = $this->normalizeColumnName($header);
        }

        foreach ($specs as $field => $spec) {
            $candidates = [$this->normalizeColumnName($field)];

            if (! empty($spec['label'])) {
                $candidates[] = $this->normalizeColumnName($spec['label']);
            }

            if (! empty($spec['aliases'])) {
                foreach ($spec['aliases'] as $alias) {
                    $candidates[] = $this->normalizeColumnName($alias);
                }
            }

            foreach ($normalizedRaw as $idx => $rawName) {
                if (in_array($rawName, $candidates, true)) {
                    $map[$field] = $idx;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Normalize a column name for comparison (lowercase, trimmed, strip asterisks and symbols).
     */
    private function normalizeColumnName(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}]+/u', '_', $name);

        return trim(mb_strtolower((string) $name, 'UTF-8'), '_');
    }

    /**
     * Determine if a CSV row is completely empty.
     *
     * @param  list<string|null>  $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Read headers and rows from CSV or Excel file.
     *
     * @return array{headers: list<string>, rows: iterable<int, array<int, mixed>>}|null
     */
    private function readSheet(string $path, string $extension): ?array
    {
        if ($extension === 'xlsx' || $extension === 'xls') {
            try {
                $reader = $extension === 'xlsx'
                    ? new Xlsx
                    : new Xls;

                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $worksheet = $spreadsheet->getActiveSheet();
                $allRows = $worksheet->toArray(null, true, false, false);

                if (empty($allRows)) {
                    return null;
                }

                $rawHeaders = array_shift($allRows);
                while (! empty($rawHeaders) && (end($rawHeaders) === null || trim((string) end($rawHeaders)) === '')) {
                    array_pop($rawHeaders);
                }

                if (empty($rawHeaders)) {
                    return null;
                }

                $headers = array_map(fn ($h) => trim((string) $h), $rawHeaders);

                return [
                    'headers' => $headers,
                    'rows' => $allRows,
                ];
            } catch (\Throwable) {
                return null;
            }
        }

        // CSV parsing
        $delimiter = $this->detectDelimiter($path);
        $handle = fopen($path, 'r');
        if (! $handle) {
            return null;
        }

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if ($rawHeaders === false || empty($rawHeaders)) {
            fclose($handle);

            return null;
        }

        // Strip UTF-8 BOM from first header column if present
        if (isset($rawHeaders[0])) {
            $rawHeaders[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rawHeaders[0]);
        }

        while (! empty($rawHeaders) && (end($rawHeaders) === null || trim((string) end($rawHeaders)) === '')) {
            array_pop($rawHeaders);
        }

        if (empty($rawHeaders)) {
            fclose($handle);

            return null;
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rawHeaders);

        $rowGenerator = function () use ($handle, $delimiter) {
            try {
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    yield $row;
                }
            } finally {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        };

        return [
            'headers' => $headers,
            'rows' => $rowGenerator(),
        ];
    }
}
