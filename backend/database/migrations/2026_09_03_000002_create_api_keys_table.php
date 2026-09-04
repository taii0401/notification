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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->comment('API 金鑰');
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 100)->comment('API Key 名稱');
            $table->string('key_prefix', 20)->comment('顯示與辨識用前綴');
            $table->string('key_hash', 255)->unique()->comment('API Key Hash');
            $table->string('status', 20)->default('active')->index()->comment('狀態：active / inactive / revoked');
            $table->timestamp('last_used_at')->nullable()->comment('最後使用時間');
            $table->timestamp('expires_at')->nullable()->index()->comment('過期時間');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};

