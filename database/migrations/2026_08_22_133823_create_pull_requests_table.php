<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pull_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('github_id');
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('state', 16)->index();
            $table->string('author')->nullable();
            $table->string('base_ref')->nullable();
            $table->string('head_ref')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->json('checks_status')->nullable();
            $table->string('html_url');
            $table->timestamps();

            $table->unique(['repository_id', 'github_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_requests');
    }
};
