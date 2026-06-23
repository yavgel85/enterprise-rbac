<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->morphs('attachable');
            $table->string('disk');
            $table->string('path');
            $table->string('name');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'attachable_type', 'attachable_id'], 'attachments_tenant_attachable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
