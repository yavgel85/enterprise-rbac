<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permission resolve filters direct grants by (user_id, type, expires_at);
        // a single composite index covers the whole predicate.
        Schema::table('permission_user', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'expires_at'], 'permission_user_user_type_expires_idx');
        });

        // Role resolve filters active roles by (user_id, expires_at).
        Schema::table('role_user', function (Blueprint $table) {
            $table->index(['user_id', 'expires_at'], 'role_user_user_expires_idx');
        });

        // Pipeline / funnel queries filter deals by (tenant_id, stage, status).
        Schema::table('deals', function (Blueprint $table) {
            $table->index(['tenant_id', 'stage', 'status'], 'deals_tenant_stage_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('permission_user', function (Blueprint $table) {
            $table->dropIndex('permission_user_user_type_expires_idx');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropIndex('role_user_user_expires_idx');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('deals_tenant_stage_status_idx');
        });
    }
};
