<?php

namespace Tests\Feature\Ops;

use App\Models\Bilty;
use App\Models\BiltyDetail;
use App\Models\Good;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\PackageType;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Accounting\Concerns\BuildsAccountingContext;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use BuildsAccountingContext;
    use RefreshDatabase;

    private const ALL_PERMISSIONS = [
        'view invoices',
        'create invoices',
        'edit invoices',
        'delete invoices',
    ];

    public function test_index_lists_invoices_with_default_filters_and_delete_flags(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $older = $this->makeInvoice($context, 'INV-OLD-1');
        $latest = $this->makeInvoice($context, 'INV-NEW-2');

        $this->actingAs($context['user'])
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Index')
                ->has('invoices.data', 2)
                ->where('invoices.data.0.id', $latest->id)
                ->where('invoices.data.0.can_delete', true)
                ->where('invoices.data.1.id', $older->id)
                ->where('invoices.data.1.can_delete', false)
                ->where('invoices.data.0.can_edit', true)
                ->where('filters.search', '')
                ->where('filters.from_date', now()->startOfMonth()->toDateString())
                ->where('filters.to_date', now()->endOfMonth()->toDateString())
                ->where('filters.party_id', '')
                ->where('filters.status', '')
                ->has('parties')
            );
    }

    public function test_index_filters_by_search_party_status_and_date_range(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $matching = $this->makeInvoice($context, 'INV-FIND-1');
        $this->makeInvoice($context, 'INV-OTHER-2', ['status' => 'Paid']);

        $otherParty = $this->makeParty($context, 'Other Party');
        $this->makeInvoice($context, 'INV-FIND-3', ['party_id' => $otherParty->id]);
        $this->makeInvoice($context, 'INV-FIND-OLD', ['date' => now()->subMonths(3)->toDateString()]);

        $this->actingAs($context['user'])
            ->get(route('invoices.index', ['search' => 'FIND']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoices.data', 2)
            );

        $this->actingAs($context['user'])
            ->get(route('invoices.index', [
                'search' => 'FIND',
                'party_id' => $matching->party_id,
                'status' => 'Pending',
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.invoice_no', 'INV-FIND-1')
                ->where('filters.party_id', (string) $matching->party_id)
                ->where('filters.status', 'Pending')
            );

        $this->actingAs($context['user'])
            ->get(route('invoices.index', [
                'status' => 'Paid',
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.invoice_no', 'INV-OTHER-2')
            );

        $this->actingAs($context['user'])
            ->get(route('invoices.index', [
                'from_date' => now()->subMonths(4)->toDateString(),
                'to_date' => now()->subMonths(2)->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.invoice_no', 'INV-FIND-OLD')
            );
    }

    public function test_index_rejects_invalid_filters(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->from(route('invoices.index'))
            ->get(route('invoices.index', [
                'from_date' => '2026-05-10',
                'to_date' => '2026-05-01',
                'party_id' => 999999,
            ]))
            ->assertSessionHasErrors(['to_date', 'party_id']);
    }

    public function test_index_only_shows_invoices_of_own_company(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $this->makeInvoice($context, 'INV-MINE-1');

        $other = $this->invoiceContext([], 'B');
        $this->makeInvoice($other, 'INV-THEIRS-1');

        $this->actingAs($context['user'])
            ->get(route('invoices.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.invoice_no', 'INV-MINE-1')
            );
    }

    public function test_create_page_renders_with_next_invoice_number(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $this->makeParty($context, 'Create Party');

        $this->actingAs($context['user'])
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Create')
                ->has('parties', 3)
                ->has('next_invoice_no')
            );
    }

    public function test_pending_bilties_returns_only_uninvoiced_tbb_bilties_for_party(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $free = $this->makeBilty($context, 'BLT-FREE');
        $invoice = $this->makeInvoice($context, 'INV-X');
        $this->makeBilty($context, 'BLT-PAID', ['payment_type' => 'Paid']);

        $response = $this->actingAs($context['user'])
            ->getJson(route('invoices.pending-bilties', ['party_id' => $context['consignor']->id]))
            ->assertOk();

        $ids = collect($response->json('bilties'))->pluck('id')->all();
        $this->assertSame([$free->id], $ids);

        $withCurrent = $this->actingAs($context['user'])
            ->getJson(route('invoices.pending-bilties', [
                'party_id' => $context['consignor']->id,
                'invoice_id' => $invoice->id,
            ]))
            ->assertOk();

        $this->assertCount(2, $withCurrent->json('bilties'));
    }

    public function test_pending_bilties_requires_valid_party(): void
    {
        $context = $this->invoiceContext(['create invoices']);

        $this->actingAs($context['user'])
            ->getJson(route('invoices.pending-bilties'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('party_id');
    }

    public function test_pending_bilties_is_available_with_edit_permission_only(): void
    {
        $context = $this->invoiceContext(['edit invoices']);

        $this->actingAs($context['user'])
            ->getJson(route('invoices.pending-bilties', ['party_id' => $context['consignor']->id]))
            ->assertOk();

        $viewer = $this->invoiceContext(['view invoices'], 'B');

        $this->actingAs($viewer['user'])
            ->getJson(route('invoices.pending-bilties', ['party_id' => $viewer['consignor']->id]))
            ->assertForbidden();
    }

    public function test_store_creates_invoice_and_links_bilties(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1');
        $detail = $bilty->biltyDetails()->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-NEW-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'remark' => 'First invoice',
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    $bilty->id => [
                        ['id' => $detail->id, 'rate_type' => 'Fixed', 'rate' => 1000],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success', 'Invoice created successfully.')
            ->assertSessionMissing('print_url');

        $invoice = Invoice::query()->where('invoice_no', 'INV-NEW-1')->firstOrFail();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'company_id' => $context['company']->id,
            'party_id' => $context['consignor']->id,
            'status' => 'Pending',
            'remark' => 'First invoice',
        ]);
        $this->assertSame(1000.0, (float) $invoice->total_amount);
        $this->assertSame(1000.0, (float) $invoice->net_amount);
        $this->assertSame(0.0, (float) $invoice->gst_amount);

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $invoice->id,
            'bilty_id' => $bilty->id,
        ]);
        $this->assertDatabaseHas('bilties', [
            'id' => $bilty->id,
            'invoice_number' => 'INV-NEW-1',
        ]);
        $this->assertDatabaseHas('bilty_details', [
            'id' => $detail->id,
            'rate_type' => 'Fixed',
        ]);
    }

    public function test_store_with_print_after_save_flashes_print_url(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1');

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-PRINT-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'remark' => null,
                'bilty_ids' => [$bilty->id],
                'print_after_save' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_no', 'INV-PRINT-1')->firstOrFail();

        $this->assertSame(route('invoices.print', $invoice), session('print_url'));
    }

    public function test_store_calculates_gst_and_charges_per_package_rate(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-GST', [
            'bilty_charge' => 50,
            'gst' => 12,
            'gst_liability_by' => 'Transporter',
        ], ['no_of_packages' => 10]);
        $detail = $bilty->biltyDetails()->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-GST-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'remark' => null,
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    $bilty->id => [
                        ['id' => $detail->id, 'rate_type' => 'Per Package', 'rate' => 100],
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::query()->where('invoice_no', 'INV-GST-1')->firstOrFail();

        $this->assertSame(1050.0, (float) $invoice->total_amount);
        $this->assertSame(126.0, (float) $invoice->gst_amount);
        $this->assertSame(1176.0, (float) $invoice->net_amount);
    }

    public function test_store_validates_required_fields(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);

        $this->actingAs($context['user'])
            ->from(route('invoices.create'))
            ->post(route('invoices.store'), [])
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors(['invoice_no', 'date', 'party_id', 'bilty_ids']);

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_store_rejects_duplicate_invoice_number(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $this->makeInvoice($context, 'INV-DUP-1');
        $bilty = $this->makeBilty($context, 'BLT-1');

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-DUP-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
            ])
            ->assertSessionHasErrors('invoice_no');
    }

    public function test_store_rejects_unknown_bilty_and_bad_detail_rows(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1');

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-BAD-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [999999],
            ])
            ->assertSessionHasErrors('bilty_ids.0');

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-BAD-2',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    $bilty->id => [
                        ['id' => 1, 'rate_type' => 'Bogus', 'rate' => 0],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'bilty_detail_freight.'.$bilty->id.'.0.rate_type',
                'bilty_detail_freight.'.$bilty->id.'.0.rate',
            ]);

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_store_rejects_detail_row_belonging_to_another_bilty(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1');
        $otherBilty = $this->makeBilty($context, 'BLT-2');
        $foreignDetail = $otherBilty->biltyDetails()->firstOrFail();

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-FOREIGN-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    $bilty->id => [
                        ['id' => $foreignDetail->id, 'rate_type' => 'Fixed', 'rate' => 500],
                    ],
                ],
            ])
            ->assertSessionHasErrors('bilty_detail_freight');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_store_rejects_already_invoiced_bilty(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1', ['invoice_number' => 'INV-EXISTING']);

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-NEW-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
            ])
            ->assertSessionHasErrors('bilty_ids');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_store_rejects_non_tbb_bilty(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1', ['payment_type' => 'Paid']);

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-NEW-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
            ])
            ->assertSessionHasErrors(['bilty_ids' => 'Only TBB bilties can be added to an invoice.']);
    }

    public function test_store_rejects_bilty_of_a_different_consignor(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $otherParty = $this->makeParty($context, 'Other Party');
        $bilty = $this->makeBilty($context, 'BLT-1');

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-NEW-1',
                'date' => now()->toDateString(),
                'party_id' => $otherParty->id,
                'bilty_ids' => [$bilty->id],
            ])
            ->assertSessionHasErrors(['bilty_ids' => 'Selected bilties must belong to the chosen party (consignor).']);
    }

    public function test_store_rejects_bilty_without_freight(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $bilty = $this->makeBilty($context, 'BLT-1', [], ['rate' => null, 'rate_type' => null]);

        $this->actingAs($context['user'])
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-NEW-1',
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
            ])
            ->assertSessionHasErrors('bilty_detail_freight');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_show_renders_invoice(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-SHOW-1');

        $this->actingAs($context['user'])
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Show')
                ->where('invoice.id', $invoice->id)
                ->where('invoice.invoice_no', 'INV-SHOW-1')
                ->has('accounting')
            );
    }

    public function test_edit_page_renders_invoice_with_details(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-EDIT-1');

        $this->actingAs($context['user'])
            ->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Edit')
                ->where('invoice.id', $invoice->id)
                ->has('invoice.details', 1)
                ->has('invoice.details.0.bilty.bilty_details', 1)
                ->has('parties')
            );
    }

    public function test_update_changes_invoice_and_swaps_bilties(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');
        $oldBilty = Bilty::query()->where('invoice_number', 'INV-UPD-1')->firstOrFail();
        $newBilty = $this->makeBilty($context, 'BLT-NEW');
        $newDetail = $newBilty->biltyDetails()->firstOrFail();

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'remark' => 'Updated remark',
                'bilty_ids' => [$newBilty->id],
                'bilty_detail_freight' => [
                    $newBilty->id => [
                        ['id' => $newDetail->id, 'rate_type' => 'Fixed', 'rate' => 2500],
                    ],
                ],
                'print_after_save' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success', 'Invoice updated successfully.')
            ->assertSessionHas('print_url', route('invoices.print', $invoice));

        $invoice->refresh();
        $this->assertSame('Updated remark', $invoice->remark);
        $this->assertSame('INV-UPD-1', $invoice->invoice_no);
        $this->assertSame(2500.0, (float) $invoice->net_amount);

        $this->assertNull($oldBilty->fresh()->invoice_number);
        $this->assertSame('INV-UPD-1', $newBilty->fresh()->invoice_number);
        $this->assertDatabaseCount('invoice_details', 1);
        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $invoice->id,
            'bilty_id' => $newBilty->id,
        ]);
    }

    public function test_update_can_keep_existing_bilty(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');
        $bilty = Bilty::query()->where('invoice_number', 'INV-UPD-1')->firstOrFail();

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'remark' => null,
                'bilty_ids' => [$bilty->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('invoices.index'))
            ->assertSessionMissing('print_url');

        $this->assertSame('INV-UPD-1', $bilty->fresh()->invoice_number);
        $this->assertDatabaseCount('invoice_details', 1);
    }

    public function test_update_validates_input(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');

        $this->actingAs($context['user'])
            ->from(route('invoices.edit', $invoice))
            ->put(route('invoices.update', $invoice), [])
            ->assertRedirect(route('invoices.edit', $invoice))
            ->assertSessionHasErrors(['date', 'party_id', 'bilty_ids']);
    }

    public function test_update_rejects_unknown_bilty(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [999999],
            ])
            ->assertSessionHasErrors('bilty_ids.0');
    }

    public function test_update_rejects_bilty_invoiced_on_another_invoice(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');
        $this->makeInvoice($context, 'INV-UPD-2');
        $otherBilty = Bilty::query()->where('invoice_number', 'INV-UPD-2')->firstOrFail();

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$otherBilty->id],
            ])
            ->assertSessionHasErrors('bilty_ids');

        $this->assertSame('INV-UPD-2', $otherBilty->fresh()->invoice_number);
    }

    public function test_update_rejects_non_tbb_wrong_party_and_missing_freight(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');
        $paidBilty = $this->makeBilty($context, 'BLT-PAID', ['payment_type' => 'Paid']);
        $freeBilty = $this->makeBilty($context, 'BLT-FREE');
        $noFreightBilty = $this->makeBilty($context, 'BLT-NOFRT', [], ['rate' => null, 'rate_type' => null]);
        $otherParty = $this->makeParty($context, 'Other Party');

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$paidBilty->id],
            ])
            ->assertSessionHasErrors(['bilty_ids' => 'Only TBB bilties can be added to an invoice.']);

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $otherParty->id,
                'bilty_ids' => [$freeBilty->id],
            ])
            ->assertSessionHasErrors(['bilty_ids' => 'Selected bilties must belong to the chosen party (consignor).']);

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$noFreightBilty->id],
            ])
            ->assertSessionHasErrors('bilty_detail_freight');
    }

    public function test_update_rejects_foreign_detail_row(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-UPD-1');
        $bilty = $this->makeBilty($context, 'BLT-1');
        $other = $this->makeBilty($context, 'BLT-2');
        $foreignDetail = $other->biltyDetails()->firstOrFail();

        $this->actingAs($context['user'])
            ->put(route('invoices.update', $invoice), [
                'date' => now()->toDateString(),
                'party_id' => $context['consignor']->id,
                'bilty_ids' => [$bilty->id],
                'bilty_detail_freight' => [
                    $bilty->id => [
                        ['id' => $foreignDetail->id, 'rate_type' => 'Fixed', 'rate' => 500],
                    ],
                ],
            ])
            ->assertSessionHasErrors('bilty_detail_freight');
    }

    public function test_destroy_soft_deletes_latest_pending_invoice_and_releases_bilties(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-DEL-1');
        $bilty = Bilty::query()->where('invoice_number', 'INV-DEL-1')->firstOrFail();

        $this->actingAs($context['user'])
            ->delete(route('invoices.destroy', $invoice), ['delete_remark' => 'Entered by mistake'])
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success', 'Invoice deleted successfully.');

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'delete_remark' => 'Entered by mistake',
            'deleted_by' => $context['user']->id,
        ]);
        $this->assertNull($bilty->fresh()->invoice_number);
    }

    public function test_destroy_rejects_non_latest_invoice(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $older = $this->makeInvoice($context, 'INV-DEL-1');
        $this->makeInvoice($context, 'INV-DEL-2');

        $this->actingAs($context['user'])
            ->delete(route('invoices.destroy', $older))
            ->assertSessionHasErrors(['invoice' => 'Only the latest invoice can be deleted.']);

        $this->assertNotSoftDeleted('invoices', ['id' => $older->id]);
    }

    public function test_destroy_rejects_non_pending_invoice(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-DEL-1', ['status' => 'Paid']);

        $this->actingAs($context['user'])
            ->delete(route('invoices.destroy', $invoice))
            ->assertSessionHasErrors(['invoice' => 'Only pending invoices can be deleted.']);

        $this->assertNotSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_print_renders_invoice_view(): void
    {
        $context = $this->invoiceContext(self::ALL_PERMISSIONS);
        $invoice = $this->makeInvoice($context, 'INV-PRINT-9');

        $this->actingAs($context['user'])
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertViewIs('prints.invoice')
            ->assertViewHas('invoice', fn (Invoice $viewInvoice) => $viewInvoice->is($invoice))
            ->assertSee('INV-PRINT-9');
    }

    public function test_routes_require_matching_permissions(): void
    {
        $context = $this->invoiceContext(['view invoices']);
        $invoice = $this->makeInvoice($context, 'INV-PERM-1');
        $user = $context['user'];

        $this->actingAs($user)->get(route('invoices.index'))->assertOk();
        $this->actingAs($user)->get(route('invoices.show', $invoice))->assertOk();
        $this->actingAs($user)->get(route('invoices.print', $invoice))->assertOk();
        $this->actingAs($user)->get(route('invoices.create'))->assertForbidden();
        $this->actingAs($user)->post(route('invoices.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('invoices.edit', $invoice))->assertForbidden();
        $this->actingAs($user)->put(route('invoices.update', $invoice), [])->assertForbidden();
        $this->actingAs($user)->delete(route('invoices.destroy', $invoice))->assertForbidden();
    }

    public function test_user_without_permissions_cannot_list_invoices(): void
    {
        $context = $this->invoiceContext([]);

        $this->actingAs($context['user'])
            ->get(route('invoices.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function invoiceContext(array $permissions, string $suffix = 'A'): array
    {
        $context = $this->accountingContext($permissions, $suffix);

        $context['consignor'] = $this->makeParty($context, "Consignor {$suffix}");
        $context['consignee'] = $this->makeParty($context, "Consignee {$suffix}");
        $context['good'] = Good::create(['owner_id' => $context['owner']->id, 'name' => "Goods {$suffix}"]);
        $context['packageType'] = PackageType::create([
            'owner_id' => $context['owner']->id,
            'name' => "Box {$suffix}",
        ]);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeParty(array $context, string $name): Party
    {
        return Party::create([
            'company_id' => $context['company']->id,
            'name' => $name,
            'state_id' => $context['state']->id,
            'city_id' => $context['city']->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $detailAttributes
     */
    private function makeBilty(array $context, string $number, array $attributes = [], array $detailAttributes = []): Bilty
    {
        $bilty = Bilty::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_number' => $number,
            'date' => now()->toDateString(),
            'from_station_id' => $context['fromStation']->id,
            'to_station_id' => $context['toStation']->id,
            'consignor_id' => $context['consignor']->id,
            'consignee_id' => $context['consignee']->id,
            'payment_type' => 'TBB',
            'freight' => 500,
            'amount' => 500,
            'status' => 'Pending',
        ], $attributes));

        BiltyDetail::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'bilty_id' => $bilty->id,
            'good_id' => $context['good']->id,
            'package_type_id' => $context['packageType']->id,
            'no_of_packages' => 5,
            'gross_weight' => 50,
            'charged_weight' => 50,
            'rate' => 500,
            'rate_type' => 'Fixed',
        ], $detailAttributes));

        return $bilty;
    }

    /**
     * Creates an invoice with one linked TBB bilty (invoice_number set).
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     */
    private function makeInvoice(array $context, string $invoiceNo, array $attributes = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'company_id' => $context['company']->id,
            'branch_id' => $context['branch']->id,
            'invoice_no' => $invoiceNo,
            'date' => now()->toDateString(),
            'party_id' => $context['consignor']->id,
            'status' => 'Pending',
            'total_amount' => 500,
            'gst_amount' => 0,
            'net_amount' => 500,
        ], $attributes));

        $bilty = $this->makeBilty($context, 'BLT-'.$invoiceNo, ['invoice_number' => $invoiceNo]);

        InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'bilty_id' => $bilty->id,
            'amount' => 500,
        ]);

        return $invoice;
    }
}
