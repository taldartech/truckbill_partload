<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $renameMap = [
        'MasterAdmin' => 'master_admin',
        'Admin' => 'admin',
        'Moderator' => 'moderator',
        'Accountant' => 'accountant',
    ];

    public function up(): void
    {
        $managerRoleId = DB::table('roles')->where('name', 'Manager')->value('id');
        $staffRoleId = DB::table('roles')->where('name', 'Staff')->value('id');
        $moderatorRoleId = DB::table('roles')->where('name', 'Moderator')->value('id');
        $accountantRoleId = DB::table('roles')->where('name', 'Accountant')->value('id');

        if ($managerRoleId && $moderatorRoleId) {
            DB::table('model_has_roles')
                ->where('role_id', $managerRoleId)
                ->update(['role_id' => $moderatorRoleId]);
        }

        if ($staffRoleId && $accountantRoleId) {
            DB::table('model_has_roles')
                ->where('role_id', $staffRoleId)
                ->update(['role_id' => $accountantRoleId]);
        }

        DB::table('roles')->whereIn('name', ['Manager', 'Staff'])->delete();

        foreach ($this->renameMap as $oldName => $newName) {
            DB::table('roles')->where('name', $oldName)->update(['name' => $newName]);
        }

        if (! DB::table('roles')->where('name', 'super_admin')->exists()) {
            DB::table('roles')->insert([
                'name' => 'super_admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'super_admin')->delete();

        foreach (array_reverse($this->renameMap, true) as $newName => $oldName) {
            DB::table('roles')->where('name', $newName)->update(['name' => $oldName]);
        }
    }
};
