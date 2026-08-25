<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('github_id');
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('state', 16)->index();
            $table->json('labels')->nullable();
            $table->string('assignee')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('html_url');
            $table->timestamps();

            $table->unique(['repository_id', 'github_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
