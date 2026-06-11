<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'has_gst')) {
                $table->boolean('has_gst')->default(false)->after('pin_code');
            }
            if (! Schema::hasColumn('branches', 'gst_number')) {
                $table->string('gst_number')->nullable()->after('has_gst');
            }
            if (! Schema::hasColumn('branches', 'gst_type')) {
                $table->string('gst_type')->nullable()->after('gst_number');
            }
            if (! Schema::hasColumn('branches', 'gst_scheme')) {
                $table->string('gst_scheme')->nullable()->after('gst_type');
            }
            if (! Schema::hasColumn('branches', 'gst_slab')) {
                $table->string('gst_slab')->nullable()->after('gst_scheme');
            }
            if (! Schema::hasColumn('branches', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('gst_slab');
            }
            if (! Schema::hasColumn('branches', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('branches', 'ifsc_code')) {
                $table->string('ifsc_code')->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('branches', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('ifsc_code');
            }
            if (! Schema::hasColumn('branches', 'contact_number')) {
                $table->string('contact_number')->nullable()->after('contact_person');
            }
            if (! Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable()->after('contact_number');
            }
            if (! Schema::hasColumn('branches', 'bilty_initial')) {
                $table->string('bilty_initial')->nullable()->after('email');
            }
            if (! Schema::hasColumn('branches', 'challan_initial')) {
                $table->string('challan_initial')->nullable()->after('bilty_initial');
            }
            if (! Schema::hasColumn('branches', 'main_challan_initial')) {
                $table->string('main_challan_initial')->nullable()->after('challan_initial');
            }
            if (! Schema::hasColumn('branches', 'invoice_initial')) {
                $table->string('invoice_initial')->nullable()->after('main_challan_initial');
            }
            if (! Schema::hasColumn('branches', 'inward_register_initial')) {
                $table->string('inward_register_initial')->nullable()->after('invoice_initial');
            }
            if (! Schema::hasColumn('branches', 'use_company_logo')) {
                $table->boolean('use_company_logo')->default(true)->after('inward_register_initial');
            }
            if (! Schema::hasColumn('branches', 'branch_logo')) {
                $table->string('branch_logo')->nullable()->after('use_company_logo');
            }
        });

        $indiaId = DB::table('countries')->where('name', 'India')->value('id');

        if ($indiaId) {
            DB::table('companies')->whereNull('country_id')->update(['country_id' => $indiaId]);
            DB::table('branches')->whereNull('country_id')->update(['country_id' => $indiaId]);
        }

        if (Schema::hasTable('settings')) {
            $branches = DB::table('branches')->get(['id', 'company_id']);

            foreach ($branches as $branch) {
                $settings = DB::table('settings')
                    ->where('company_id', $branch->company_id)
                    ->where('branch_id', $branch->id)
                    ->pluck('value', 'key');

                $updates = [
                    'bilty_initial' => $settings['bilty_initial'] ?? 'BLT-',
                    'challan_initial' => $settings['challan_initial'] ?? 'CHL-',
                    'main_challan_initial' => $settings['main_challan_initial'] ?? 'MCH-',
                    'invoice_initial' => $settings['invoice_prefix'] ?? 'INV-',
                    'inward_register_initial' => $settings['inward_register_initial'] ?? 'IR-',
                ];

                if (isset($settings['default_gst_percent']) && $settings['default_gst_percent'] !== '') {
                    $updates['has_gst'] = true;
                    $updates['gst_slab'] = (string) $settings['default_gst_percent'];
                }

                if (! empty($settings['logo'])) {
                    $updates['branch_logo'] = $settings['logo'];
                    $updates['use_company_logo'] = false;
                }

                DB::table('branches')->where('id', $branch->id)->update($updates);
            }
        }

        $companies = DB::table('companies')->get();

        foreach ($companies as $company) {
            $companyUpdates = [];

            if (empty($company->company_logo) && Schema::hasTable('settings')) {
                $logo = DB::table('settings')
                    ->where('company_id', $company->id)
                    ->where('key', 'logo')
                    ->whereNotNull('value')
                    ->value('value');

                if ($logo) {
                    $companyUpdates['company_logo'] = $logo;
                }
            }

            if ($companyUpdates !== []) {
                DB::table('companies')->where('id', $company->id)->update($companyUpdates);
            }

            $headOffice = DB::table('branches')
                ->where('company_id', $company->id)
                ->where('is_head_office', true)
                ->first();

            $targetBranch = $headOffice ?? DB::table('branches')
                ->where('company_id', $company->id)
                ->orderBy('id')
                ->first();

            if (! $targetBranch) {
                continue;
            }

            $branchUpdates = [];

            if (! empty($company->gst_number)) {
                $branchUpdates['has_gst'] = true;
                $branchUpdates['gst_number'] = $company->gst_number;
                $branchUpdates['gst_type'] = $company->gst_type;
                $branchUpdates['gst_scheme'] = $company->gst_scheme;
                if (empty($targetBranch->gst_slab ?? null) && ! empty($company->gst_slab)) {
                    $branchUpdates['gst_slab'] = $company->gst_slab;
                }
            }

            if (! empty($company->bank)) {
                $branchUpdates['bank_name'] = $company->bank;
            }
            if (! empty($company->account_number)) {
                $branchUpdates['account_number'] = $company->account_number;
            }
            if (! empty($company->ifsc)) {
                $branchUpdates['ifsc_code'] = $company->ifsc;
            }
            if (empty($targetBranch->contact_person ?? null) && ! empty($company->contact_person)) {
                $branchUpdates['contact_person'] = $company->contact_person;
            }
            if (empty($targetBranch->contact_number ?? null) && ! empty($company->contact_number)) {
                $branchUpdates['contact_number'] = $company->contact_number;
            }
            if (empty($targetBranch->email ?? null) && ! empty($company->email)) {
                $branchUpdates['email'] = $company->email;
            }

            if ($branchUpdates !== []) {
                DB::table('branches')->where('id', $targetBranch->id)->update($branchUpdates);
            }
        }

        if (Schema::hasTable('settings')) {
            Schema::dropIfExists('settings');
        }

        $companyColumnsToDrop = [
            'short_name',
            'company_type',
            'phone',
            'mobile',
            'gst_number',
            'gst_scheme',
            'gst_type',
            'bank',
            'account_number',
            'ifsc',
            'is_blocked',
            'invoice_template',
            'lr_copy_template',
            'freight_challan_template',
            'gst_slab',
            'status',
        ];

        $existingCompanyColumns = array_filter(
            $companyColumnsToDrop,
            fn (string $column) => Schema::hasColumn('companies', $column)
        );

        if ($existingCompanyColumns !== []) {
            Schema::table('companies', function (Blueprint $table) use ($existingCompanyColumns) {
                $table->dropColumn(array_values($existingCompanyColumns));
            });
        }
    }

    public function down(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'branch_id', 'key']);
            $table->index('company_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('short_name')->nullable();
            $table->string('company_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('gst_scheme')->nullable();
            $table->string('gst_type')->nullable();
            $table->string('bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->text('invoice_template')->nullable();
            $table->text('lr_copy_template')->nullable();
            $table->text('freight_challan_template')->nullable();
            $table->string('gst_slab')->nullable();
            $table->boolean('status')->default(true);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'has_gst',
                'gst_number',
                'gst_type',
                'gst_scheme',
                'gst_slab',
                'bank_name',
                'account_number',
                'ifsc_code',
                'contact_person',
                'contact_number',
                'email',
                'bilty_initial',
                'challan_initial',
                'main_challan_initial',
                'invoice_initial',
                'inward_register_initial',
                'use_company_logo',
                'branch_logo',
            ]);
        });
    }
};
