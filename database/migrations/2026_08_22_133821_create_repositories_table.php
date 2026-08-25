<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('github_id')->unique();
            $table->string('name');
            $table->string('full_name')->index();
            $table->string('owner');
            $table->boolean('private')->default(false);
            $table->string('html_url');
            $table->unsignedInteger('stale_threshold_days')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->boolean('archived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
