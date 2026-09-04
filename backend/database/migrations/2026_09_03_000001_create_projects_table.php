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
        Schema::create('projects', function (Blueprint $table) {
            $table->comment('專案');
            $table->id();
            $table->uuid('uuid')->unique()->comment('UUID');
            $table->string('name', 100)->comment('專案名稱');
            $table->string('slug', 100)->unique()->comment('專案唯一識別字串');
            $table->string('status', 20)->default('active')->index()->comment('狀態：active / inactive / revoked');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

