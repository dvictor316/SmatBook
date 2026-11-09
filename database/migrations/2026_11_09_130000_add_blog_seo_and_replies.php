<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 500)->nullable();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE blog_comments ADD COLUMN parent_id INTEGER REFERENCES blog_comments(id) ON DELETE CASCADE');
        } else {
            Schema::table('blog_comments', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->cascadeOnDelete();
            });
        }

        Schema::table('blog_comments', function (Blueprint $table) {
            $table->index(['blog_post_id', 'parent_id', 'approved', 'created_at'], 'blog_comments_thread_index');
        });
    }

    public function down(): void
    {
        Schema::table('blog_comments', function (Blueprint $table) {
            $table->dropIndex('blog_comments_thread_index');
            if (DB::getDriverName() === 'sqlite') {
                $table->dropColumn('parent_id');
            } else {
                $table->dropConstrainedForeignId('parent_id');
            }
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords']);
        });
    }
};
