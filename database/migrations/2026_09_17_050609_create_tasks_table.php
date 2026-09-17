<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('capa_id')
                ->constrained('capas')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('assigned_to')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('due_date');
            $table->string('status')->default('open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};