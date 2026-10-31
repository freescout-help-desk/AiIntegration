<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiintegrationSummariesTable extends Migration
{
    public function up()
    {
        Schema::create('aiintegration_summaries', function (Blueprint $table) {
            $table->unsignedInteger('conversation_id')->primary();
            $table->longText('summary');
            $table->dateTime('generated_at');
            $table->string('fingerprint', 64);
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aiintegration_summaries');
    }
}
