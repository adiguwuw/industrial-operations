<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_photos', function (Blueprint $table) {
            $table->foreignUlid('comment_id')
                ->nullable()
                ->after('incident_id')
                ->constrained('incident_comments')
                ->nullOnDelete();

            $table->foreignUlid('status_history_id')
                ->nullable()
                ->after('comment_id')
                ->constrained('incident_status_histories')
                ->nullOnDelete();

            $table->index('comment_id');
            $table->index('status_history_id');
        });
    }

    public function down(): void
    {
        Schema::table('incident_photos', function (Blueprint $table) {
            $table->dropForeign(['comment_id']);
            $table->dropForeign(['status_history_id']);

            $table->dropIndex(['comment_id']);
            $table->dropIndex(['status_history_id']);

            $table->dropColumn([
                'comment_id',
                'status_history_id',
            ]);
        });
    }
};