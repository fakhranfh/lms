<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing video_embed_url to lesson_materials
        $lessonsWithVideos = DB::table('lessons')
            ->whereNotNull('video_embed_url')
            ->get();

        foreach ($lessonsWithVideos as $lesson) {
            DB::table('lesson_materials')->insert([
                'id' => Str::uuid(),
                'lesson_id' => $lesson->id,
                'type' => 'Video',
                'title' => $lesson->title,
                'description' => null,
                'file_url' => $lesson->video_embed_url,
                'file_path' => null,
                'file_size' => 0, // Unknown size
                'mime_type' => 'video/mp4', // Default mime type for videos
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all Video type materials created during migration
        DB::table('lesson_materials')->where('type', 'Video')->delete();
    }
};
