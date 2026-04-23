<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelog_entries', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('title');
            $table->text('body');
            $table->string('category');
            $table->date('published_at');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('published_at');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_entries');
    }
};
