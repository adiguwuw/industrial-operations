<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['capa_id']);

            $table->foreignId('capa_id')
                ->nullable()
                ->change();

            $table->string('source_type')
                ->default('direct')
                ->after('capa_id');

            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'source_type',
                'source_id',
            ]);

            $table->dropForeign(['capa_id']);

            $table->foreignId('capa_id')
                ->nullable(false)
                ->constrained('capas')
                ->cascadeOnDelete();
        });
    }
};