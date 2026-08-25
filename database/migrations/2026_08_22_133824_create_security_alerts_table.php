<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('github_id');
            $table->string('type', 32)->index();
            $table->string('severity', 16)->index();
            $table->string('package_name')->nullable();
            $table->text('summary')->nullable();
            $table->string('advisory_url')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('fixed_at')->nullable();
            $table->string('html_url');
            $table->timestamps();

            $table->unique(['repository_id', 'type', 'github_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
