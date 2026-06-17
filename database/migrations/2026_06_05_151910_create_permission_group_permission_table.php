<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_group_permission', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();

            $table->unique(['permission_group_id', 'permission_id'], 'perm_group_perm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_group_permission');
    }
};
