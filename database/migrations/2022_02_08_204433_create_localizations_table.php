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
            // set the collation to utf8mb4_bin because the *_ci collations are case and accent insensitive
            // some keys have the same text when not accounting for utf8 characters
            // ex) 1hrapier_cremedelacremet5_description vs 1hRapier_CrèmeDeLaCrèmeT5_Description
            $table->string('id_key')->collation('utf8mb4_bin')->unique();
            $table->longText('text');
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