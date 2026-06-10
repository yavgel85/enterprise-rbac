<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'resource_type', 'resource_id']);
            $table->unique(
                ['user_id', 'permission_id', 'resource_type', 'resource_id'],
                'resource_permissions_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_permissions');
    }
};
