<?php

namespace Database\Seeders;

use App\Parsers\LocalizationXmlFileParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalizationSeeder extends Seeder
{
    public function run()
    {
        DB::table('localization_languages')->upsert([['name'=>'English', 'code'=>'en-us']], ['code']);
        DB::table('localization_types')->upsert([['name'=>'itemdefinitions_master'],['name'=>'craftingnames'], ['name'=>'perks']], ['name']);
        
        $parser = new LocalizationXmlFileParser();
        $parser->parseFile(__DIR__.'/../../storage/app/localization/en-us/javelindata_itemdefinitions_master.loc.xml');
        $parser->parseFile(__DIR__.'/../../storage/app/localization/en-us/javelindata_craftingnames.loc.xml');
        $parser->parseFile(__DIR__.'/../../storage/app/localization/en-us/javelindata_perks.loc.xml');
        // __DIR__.'/../../storage/app/localization/en-us/javelindata_itemdefinitions_master.loc.xml'
        
    }
}
