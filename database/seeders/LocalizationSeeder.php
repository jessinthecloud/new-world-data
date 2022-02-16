<?php

namespace Database\Seeders;

use App\Parsers\LocalizationXmlFileParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalizationSeeder extends Seeder
{
    public function run()
    {
        DB::table('localization_languages')->upsert([
            ['name'=>'English', 'code'=>'en-us', 'created_at' => now(), 'updated_at' => now(),]
        ], 'code');
        
        $parser = new LocalizationXmlFileParser();
        $files = [
            __DIR__.'/../../storage/app/localization/en-us/javelindata_itemdefinitions_master.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_craftingnames.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_perks.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_affixdefinitions.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_statuseffects.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/warboard.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/weaponabilities.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_damagetypes.loc.xml',
            __DIR__.'/../../storage/app/localization/en-us/javelindata_tradeskills.loc.xml',
        ];
        
        foreach($files as $filepath){
            // insert all file names
            DB::table('localization_files')->upsert([
                ['filename' => Str::afterLast($filepath, DIRECTORY_SEPARATOR)]
            ], ['filename']);
            
            dump("Parsing {$filepath}...");
            $values = $parser->parseFile($filepath);
            
            dump("Upserting from {$filepath}...");
            // chunk to avoid "too many parameters" SQL error
            foreach(array_chunk($values, 5000) as $upsert){
                try {
                    DB::table('localizations')->upsert($upsert, ['id_key']);
                } catch (\Throwable $throwable){
                    dump('ERROR OCCURRED: '.substr($throwable->getMessage(), 0, 300),
                        'Error code: '.$throwable->getCode()
                        .' -- on line: '.$throwable->getLine()
                        .' -- in file: '.$throwable->getFile()
                    );
                }
            }
            
        }
    }
}
