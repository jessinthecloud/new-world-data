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
        
        Schema::create('data_file_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('data_files');
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_file_types');
        Schema::dropIfExists('data_files');
    }
}