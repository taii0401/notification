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
        Schema::create('notification_attempts', function (Blueprint $table) {
            $table->comment('通知派送嘗試紀錄');
            $table->id();
            $table->foreignId('delivery_id')->constrained('notification_deliveries')->cascadeOnDelete(); //Attempt 完全依附 Delivery
            $table->unsignedInteger('attempt_no')->comment('第幾次嘗試');
            $table->string('status', 30)->default('processing')->index()->comment('狀態：processing / success / failed');
            $table->json('request_payload')->nullable()->comment('本次請求快照');
            $table->integer('response_code')->nullable()->comment('HTTP / Provider 回應碼');
            $table->text('response_body')->nullable()->comment('Provider 回應內容');
            $table->string('error_type', 100)->nullable()->comment('錯誤分類');
            $table->text('error_message')->nullable()->comment('錯誤訊息');
            $table->timestamp('started_at')->nullable()->comment('嘗試開始時間');
            $table->timestamp('finished_at')->nullable()->comment('嘗試完成時間');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['delivery_id','attempt_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_attempts');
    }
};

