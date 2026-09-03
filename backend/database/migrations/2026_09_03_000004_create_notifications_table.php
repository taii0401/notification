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
        Schema::create('notifications', function (Blueprint $table) {
            $table->comment('通知');
            $table->id();
            $table->uuid('uuid')->unique()->comment('UUID');
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('event_type', 100)->index()->comment('事件類型');
            $table->string('channel', 30)->index()->comment('通知管道：email / webhook');
            $table->string('recipient', 500)->comment('收件目標：email / webhook');
            $table->json('payload')->nullable()->comment('模板變數');
            $table->json('metadata')->nullable()->comment('額外中繼資料');
            $table->string('status', 30)->default('pending')->index()->comment('狀態：pending / queued / processing / sent / failed');
            $table->timestamp('scheduled_at')->nullable()->index()->comment('排定派送時間');
            $table->timestamp('processed_at')->nullable()->comment('開始處理時間');
            $table->timestamp('sent_at')->nullable()->comment('成功派送時間');
            $table->timestamp('failed_at')->nullable()->comment('最終失敗時間');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id','status']);
            $table->index(['project_id','created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

