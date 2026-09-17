<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_inspection_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('safety_inspection_id')
                ->constrained('safety_inspections')
                ->cascadeOnDelete();

            $table->foreignId('checklist_item_id')
                ->nullable()
                ->constrained('checklist_items')
                ->nullOnDelete();

            $table->text('question');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_required')->default(true);

            $table->string('result')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_inspection_items');
    }
};