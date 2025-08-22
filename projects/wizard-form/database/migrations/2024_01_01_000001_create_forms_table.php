<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->boolean('is_public')->default(false);
            $table->boolean('allow_multiple_submissions')->default(true);
            $table->integer('max_submissions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->string('theme')->default('default');
            $table->string('notification_email')->nullable();
            $table->text('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['slug', 'is_public', 'status']);
            $table->index(['expires_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
