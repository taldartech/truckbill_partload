<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankAccountShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_view_permission_can_open_bank_account_show(): void
    {
        $context = $this->createShowContext(['view bank_accounts']);

        $response = $this->actingAs($context['user'])
            ->get(route('bank-accounts.show', $context['bankAccount']));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('BankAccounts/Show')
            ->where('bankAccount.id', $context['bankAccount']->id)
            ->where('bankAccount.name', 'Show Bank Account')
            ->where('bankAccount.bank_name', 'State Bank')
            ->where('bankAccount.company.name', 'Show Co')
            ->etc()
        );
    }

    public function test_user_without_view_permission_cannot_open_bank_account_show(): void
    {
        $context = $this->createShowContext([]);

        $response = $this->actingAs($context['user'])
            ->get(route('bank-accounts.show', $context['bankAccount']));

        $response->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     * @return array{user: User, bankAccount: BankAccount}
     */
    private function createShowContext(array $permissions): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = User::factory()->create();
        $company = Company::create([
            'name' => 'Show Co',
            'user_id' => $owner->id,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 1000,
            'included_trucks' => 50,
            'lr_credits_remaining' => 1000,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $bankAccount = BankAccount::create([
            'company_id' => $company->id,
            'name' => 'Show Bank Account',
            'bank_name' => 'State Bank',
            'account_number' => '1234567890',
            'ifsc_code' => 'SBIN0000123',
            'is_active' => true,
        ]);

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return [
            'user' => $user,
            'bankAccount' => $bankAccount,
        ];
    }
}
