<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocalizationsTable extends Migration
{
    public function up()
    {
        Schema::create( 'localization_types', function ( Blueprint $table ) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        } );
        
        Schema::create( 'localization_languages', function ( Blueprint $table ) {
            $table->id();
            $table->string('name');
            $table->string('code', 10);
            $table->timestamps();
        } );
        
        Schema::create( 'localizations', function ( Blueprint $table ) {
            $table->id();
            $table->string('key');
            $table->text('text');
            $table->foreignId('localization_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('localization_language_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        } );
    }

    public function down()
    {
        Schema::dropIfExists( 'localizations' );
        Schema::dropIfExists( 'localization_types' );
        Schema::dropIfExists( 'localization_languages' );
    }
}