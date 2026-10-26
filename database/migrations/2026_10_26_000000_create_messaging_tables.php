<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('type');
            $table->string('subject');
            $table->jsonb('envelope');
            $table->jsonb('headers');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('published_at')->nullable();

            $table->index(['published_at', 'id']);
        });

        Schema::create('processed_messages', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('type');
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_messages');
        Schema::dropIfExists('outbox_messages');
    }
};
