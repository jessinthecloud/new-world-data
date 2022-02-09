<?php

namespace Database\Seeders;

use App\Parsers\LocalizationXmlParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalizationSeeder extends Seeder
{
    public function run()
    {
        DB::table('localization_languages')->upsert(['name'=>'English', 'code'=>'en-us'], ['code']);
        DB::table('localization_types')->upsert([['name'=>'item'], ['name'=>'perk']], ['name']);
        
        $parser = new LocalizationXmlParser();
        $parser->parseFile(__DIR__.'/../../storage/app/localization/en-us/javelindata_itemdefinitions_master.loc.xml');
        // __DIR__.'/../../storage/app/localization/en-us/javelindata_itemdefinitions_master.loc.xml'
        
    }
}
