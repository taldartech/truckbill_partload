<?php

namespace Tests\Unit\Support;

use App\Models\Invoice;
use App\Models\Party;
use App\Support\DocumentNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_next_numeric_document_pads_from_default_length(): void
    {
        $context = $this->createTenantContext();

        $next = DocumentNumber::next(
            Invoice::withoutGlobalScopes()->where('company_id', $context['company']->id),
            'invoice_no'
        );

        $this->assertSame('0001', $next);
    }

    public function test_next_increments_existing_numeric_sequence(): void
    {
        $context = $this->createTenantContext(suffix: 'B');
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Doc Party',
        ]);

        Invoice::withoutGlobalScopes()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'party_id' => $party->id,
            'invoice_no' => '0007',
            'date' => now()->toDateString(),
        ]);

        $next = DocumentNumber::next(
            Invoice::withoutGlobalScopes()->where('company_id', $context['company']->id),
            'invoice_no'
        );

        $this->assertSame('0008', $next);
    }

    public function test_prefixed_sequence_increments(): void
    {
        $context = $this->createTenantContext(suffix: 'C');
        $party = Party::create([
            'company_id' => $context['company']->id,
            'name' => 'Doc Party C',
        ]);

        Invoice::withoutGlobalScopes()->create([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'party_id' => $party->id,
            'invoice_no' => 'INV-0003',
            'date' => now()->toDateString(),
        ]);

        $next = DocumentNumber::next(
            Invoice::withoutGlobalScopes()->where('company_id', $context['company']->id),
            'invoice_no',
            'INV-'
        );

        $this->assertSame('INV-0004', $next);
    }
}
