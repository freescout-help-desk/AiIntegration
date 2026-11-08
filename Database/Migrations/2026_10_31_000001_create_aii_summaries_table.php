<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiiSummariesTable extends Migration
{
    public function up()
    {
        Schema::create('aii_summaries', function (Blueprint $table) {
            $table->unsignedInteger('conversation_id')->primary();
            $table->longText('summary');
            $table->string('fingerprint', 32);
            //$table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aii_summaries');
    }
}
