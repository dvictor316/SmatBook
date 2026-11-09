<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug')->unique();
            $table->string('category', 100);
            $table->string('summary', 500);
            $table->longText('content');
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('body');
            $table->boolean('approved')->default(false);
            $table->timestamps();
            $table->index(['blog_post_id', 'approved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('blog_posts');
    }
};
