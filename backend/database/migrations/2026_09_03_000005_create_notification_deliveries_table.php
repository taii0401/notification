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
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->comment('通知派送');
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->index()->comment('派送服務提供者：ses / smtp / webhook');
            $table->string('status', 30)->default('pending')->index()->comment('狀態：pending / processing / sent / failed');
            $table->unsignedInteger('attempt_count')->default(0)->comment('已重試次數');
            $table->string('provider_message_id', 255)->nullable()->comment('Provider 訊息 ID');
            $table->text('last_error')->nullable()->comment('最新錯誤訊息');
            $table->timestamp('sent_at')->nullable()->comment('成功派送時間');
            $table->timestamp('failed_at')->nullable()->comment('失敗時間');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['notification_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};

