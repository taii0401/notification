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
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->comment('冪等鍵');
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('notification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 255)->comment('第幾次嘗試');
            $table->string('request_hash', 64)->comment('Request SHA-256');
            $table->timestamp('expires_at')->nullable()->index()->comment('到期時間');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id','idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};

