<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocalizationsTable extends Migration
{
    public function up()
    {
        Schema::create( 'localization_languages', function ( Blueprint $table ) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 10)->unique();
            $table->timestamps();
        } );
        
        Schema::create( 'localization_files', function ( Blueprint $table ) {
            $table->id();
            $table->string('filename')->unique();
            $table->timestamps();
        } );
        
        // ALTER TABLE localizations ADD FULLTEXT(id_key);
        Schema::create( 'localizations', function ( Blueprint $table ) {
            $table->id();
            // set the collation to utf8mb4_bin because the *_ci collations are case and accent insensitive
            // some keys have the same text when not accounting for utf8 characters
            // ex) 1hrapier_cremedelacremet5_description vs 1hRapier_CrèmeDeLaCrèmeT5_Description
            $table->string('id_key')->collation('utf8mb4_bin')->unique();
            $table->string('field_type')->nullable();
            $table->longText('text');
            $table->foreignId('language_id')->constrained('localization_languages')->cascadeOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('localization_files')->nullOnDelete();
            $table->timestamps();
        } );
    }

    public function down()
    {
        Schema::dropIfExists( 'localization_files' );
        Schema::dropIfExists( 'localizations' );
        Schema::dropIfExists( 'localization_languages' );
    }
}