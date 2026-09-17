<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('finding_id')
                ->constrained('findings')
                ->cascadeOnDelete();

            $table->string('type');
            $table->text('action');

            $table->foreignId('responsible_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('due_date');
            $table->string('status')->default('open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capas');
    }
};