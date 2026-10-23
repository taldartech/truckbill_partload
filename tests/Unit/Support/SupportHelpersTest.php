<?php

namespace Tests\Unit\Support;

use App\Enums\ChargeType;
use App\Models\Branch;
use App\Models\User;
use App\Services\SingleDeviceLoginService;
use App\Support\DocumentNumber;
use App\Support\Money;
use App\Support\RolePermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

class SupportHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_helpers_round_format_and_convert(): void
    {
        $this->assertSame(10.13, Money::round('10.125'));
        $this->assertSame('25164.24', Money::format(25164.236));
        $this->assertSame('₹25,164.24', Money::display('25164.24'));
        $this->assertSame(2516424, Money::toPaise('25164.24'));
        $this->assertSame(25164.24, Money::fromPaise(2516424));
    }

    public function test_charge_type_values_and_labels(): void
    {
        $this->assertSame(['fixed', 'percentage'], ChargeType::all());
        $this->assertSame('Fixed', ChargeType::Fixed->label());
        $this->assertSame('% of Freight', ChargeType::Percentage->label());
    }

    public function test_role_permission_sets_are_consistent(): void
    {
        $this->assertContains('view companies', RolePermissions::permissionNames(RolePermissions::modules(), ['view']));
        $this->assertContains('rate_lists', RolePermissions::companyMasterDataModules());
        $this->assertContains('stations', RolePermissions::sharedMasterDataModules());
        $this->assertSame(['companies', 'branches', 'users', 'subscriptions', 'subscription_plans'], RolePermissions::setupModules());

        $admin = RolePermissions::adminPermissions();
        $moderator = RolePermissions::moderatorPermissions();
        $accountant = RolePermissions::accountantPermissions();

        $this->assertContains('delete bilties', $admin);
        $this->assertContains('create bilties', $moderator);
        $this->assertNotContains('edit bilties', $moderator);
        $this->assertContains('view reports', $accountant);
        $this->assertContains('edit accounting_vouchers', $accountant);
        $this->assertContains('delete companies', RolePermissions::superAdminPermissions());
        $this->assertContains('edit roles', RolePermissions::masterAdminPermissions());
        $this->assertNotEmpty(RolePermissions::operationalModules());
    }

    public function test_single_device_login_clears_other_database_sessions_and_remember_token(): void
    {
        config(['session.driver' => 'database', 'session.table' => 'sessions']);
        DB::statement('CREATE TABLE IF NOT EXISTS sessions (id TEXT PRIMARY KEY, user_id INTEGER NULL, payload TEXT, last_activity INTEGER)');

        $user = User::factory()->create(['remember_token' => 'abc']);
        DB::table('sessions')->insert([
            ['id' => 'keep', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'drop', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
        ]);

        (new SingleDeviceLoginService)->enforce($user, 'keep', false);

        $this->assertSame(['keep'], DB::table('sessions')->pluck('id')->all());
        $this->assertNull($user->fresh()->remember_token);

        $user->forceFill(['remember_token' => 'xyz'])->save();
        (new SingleDeviceLoginService)->enforce($user, 'keep', true);
        $this->assertSame('xyz', $user->fresh()->remember_token);
    }

    public function test_document_number_mysql_expressions_are_generated_for_non_sqlite_drivers(): void
    {
        DB::shouldReceive('connection->getDriverName')->andReturn('mysql');

        $reflection = new ReflectionClass(DocumentNumber::class);
        $query = Branch::query();

        $numeric = $reflection->getMethod('whereNumericColumn');
        $numeric->invoke(null, $query, 'number');
        $suffix = $reflection->getMethod('whereNumericSuffix');
        $suffix->invoke(null, $query, 'number', 3);

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertStringContainsString("REGEXP '^[0-9]+$'", $query->toRawSql());
        $this->assertSame(
            'MAX(CAST(number AS UNSIGNED))',
            $reflection->getMethod('maxCastExpression')->invoke(null, 'number')
        );
        $this->assertSame(
            'MAX(CAST(SUBSTRING(number, ?) AS UNSIGNED))',
            $reflection->getMethod('maxSuffixCastExpression')->invoke(null, 'number')
        );
    }
}
