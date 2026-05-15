<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')
                ->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->after('tenant_id')
                ->constrained('departments')->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_super_admin');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['department_id']);
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropColumn([
                'tenant_id',
                'department_id',
                'is_super_admin',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'deleted_at',
            ]);
        });
    }
};
