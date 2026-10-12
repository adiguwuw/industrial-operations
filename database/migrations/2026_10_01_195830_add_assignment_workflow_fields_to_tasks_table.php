<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assigned_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('task_photo_path')
                ->nullable()
                ->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn([
                'assigned_by',
                'task_photo_path',
            ]);
        });
    }
};