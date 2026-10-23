<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingCommandsMissingCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_fails_when_no_company_matches(): void
    {
        $this->artisan('accounting:audit', ['--company' => 999999])
            ->expectsOutputToContain('No companies found.')
            ->assertFailed();
    }

    public function test_reconcile_fails_when_no_company_matches(): void
    {
        $this->artisan('accounting:reconcile', ['--company' => 999999])
            ->expectsOutputToContain('No companies found.')
            ->assertFailed();
    }
}
