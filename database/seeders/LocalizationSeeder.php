<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalizationSeeder extends Seeder
{
    public function run()
    {
        DB::table('localization_languages')->insert(['name'=>'English', 'code'=>'en-us']);
        DB::table('localization_types')->insert([['name'=>'item'], ['name'=>'perk']]);
        
        
    }
}
