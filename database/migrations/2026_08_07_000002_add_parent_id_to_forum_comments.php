<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->uuid('parent_id')->nullable()->after('thread_id')->index();
            $table->foreign('parent_id')->references('id')->on('forum_comments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
