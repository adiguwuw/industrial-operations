<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('checklist_id')
                ->constrained('checklists')
                ->restrictOnDelete();

            $table->string('location');
            $table->date('inspection_date');
            $table->string('status')->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_inspections');
    }
};