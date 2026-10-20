<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the actual read paths, not speculative ones:
 *
 * - staff_service(service_id): AvailabilityService::eligibleStaffIds() looks
 *   up the pivot by service; the existing unique key starts with staff_id.
 * - time_offs(tenant_id, staff_id, starts_at): busyIntervals() filters by all
 *   three; the old index started with staff_id only.
 * - users(tenant_id): the platform tenant list counts users per tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_service', function (Blueprint $table): void {
            $table->index('service_id');
        });

        Schema::table('time_offs', function (Blueprint $table): void {
            $table->index(['tenant_id', 'staff_id', 'starts_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('staff_service', function (Blueprint $table): void {
            $table->dropIndex(['service_id']);
        });

        Schema::table('time_offs', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'staff_id', 'starts_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id']);
        });
    }
};
