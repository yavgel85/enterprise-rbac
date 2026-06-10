<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_conditions', function (Blueprint $table): void {
            $table->id();
            // null tenant_id = a global condition that applies in every tenant.
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            // null role_id = applies to every role; otherwise scoped to that role.
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->json('conditions');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_conditions');
    }
};
