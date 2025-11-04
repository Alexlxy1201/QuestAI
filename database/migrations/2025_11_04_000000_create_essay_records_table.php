<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('essay_records', function (Blueprint $table) {
            $table->id();
            $table->string('origin', 16); // ocr | grade
            $table->string('title')->nullable();
            $table->string('rubric', 32)->nullable();
            $table->longText('text')->nullable();
            $table->longText('ocr_text')->nullable();
            $table->json('scores_json')->nullable();
            $table->json('suggestions_json')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_mime', 64)->nullable();
            $table->unsignedInteger('file_pages')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // 评分记录可关联某次 OCR
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essay_records');
    }
};
