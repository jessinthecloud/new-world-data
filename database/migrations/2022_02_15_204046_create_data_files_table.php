<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataFilesTable extends Migration
{
    public function up()
    {
        Schema::create('data_files', function (Blueprint $table) {
            $table->id();
            $table->string('directory')->unique();
            $table->string('filename')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_files');
    }
}