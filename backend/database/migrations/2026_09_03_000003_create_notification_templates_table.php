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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->comment('通知模板');
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('code', 100)->comment('Template Code');
            $table->string('channel', 30)->index()->comment('通知管道：email / webhook');
            $table->string('name', 100)->comment('模板名稱');
            $table->string('subject', 255)->nullable()->comment('Email 主旨');
            $table->longText('content')->comment('模板內容');
            $table->string('status', 20)->default('active')->index()->comment('狀態：active / inactive');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id','code','channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};

