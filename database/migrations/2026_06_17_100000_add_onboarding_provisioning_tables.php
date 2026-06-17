<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'policy_accepted_at')) {
                $table->timestamp('policy_accepted_at')->nullable()->after('email_verified_at');
            }

            if (! Schema::hasColumn('users', 'policy_version')) {
                $table->string('policy_version')->nullable()->after('policy_accepted_at');
            }
        });

        Schema::create('onboarding_provisioning_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->string('action')->default('create_user');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_provisioning_logs');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'policy_version')) {
                $table->dropColumn('policy_version');
            }

            if (Schema::hasColumn('users', 'policy_accepted_at')) {
                $table->dropColumn('policy_accepted_at');
            }
        });
    }
};
