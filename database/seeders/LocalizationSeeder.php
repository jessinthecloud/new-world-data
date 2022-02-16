<?php

namespace Database\Seeders;

use App\Parsers\LocalizationXmlFileParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalizationSeeder extends Seeder
{
    public function run()
    {
        DB::table('localization_languages')->upsert([
            ['name'=>'English', 'code'=>'en-us']
        ], ['code']);
        
        DB::table('localization_types')->upsert([
            ['name'=>'itemdefinitions_master'],
            ['name'=>'craftingnames'], 
            ['name'=>'perks'],
            ['name'=>'affixdefinitions'],
            ['name'=>'statuseffects'],
            ['name'=>'tradeskills'],
            ['name'=>'damagetypes'],
            ['name'=>'warboard'],
            ['name'=>'weaponabilities'],
        ], ['name']);
        
        $parser = new LocalizationXmlFileParser();
        $files = [
            __DIR__.'/../../storage/app/localization/en-us/javelindata_itemdefinitions_master.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_craftingnames.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_perks.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_affixdefinitions.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_statuseffects.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/warboard.loc.xml',
//            __DIR__.'/../../storage/app/localization/en-us/weaponabilities.loc.xml',
//            __DIR__.'/../../storage/app/localization/en-us/javelindata_damagetypes.loc.xml',
//            __DIR__.'/../../storage/app/localization/en-us/javelindata_tradeskills.loc.xml',
        ];
        
        foreach($files as $filepath){
            dump("Parsing {$filepath}...");
            $values = $parser->parseFile($filepath);
            dump("Inserting from {$filepath}...");
            
            try {
                $result = DB::table('localizations')->upsert($values, ['id_key', 'localization_language_id']);
            } catch (\Throwable $throwable){
                dump('ERROR OCCURRED: './*$throwable->getMessage(),*/
                    'Error code: '.$throwable->getCode()
                    .' -- on line: '.$throwable->getLine()
                    .' -- in file: '.$throwable->getFile()
                );
            }
        }
    }
}
