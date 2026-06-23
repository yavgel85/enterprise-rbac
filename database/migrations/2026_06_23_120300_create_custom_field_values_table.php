<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definition_id')->constrained('custom_field_definitions')->cascadeOnDelete();
            $table->morphs('owner');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['definition_id', 'owner_type', 'owner_id'], 'cfv_definition_owner_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
