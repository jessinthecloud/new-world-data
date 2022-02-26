<?php

namespace App\Http\Controllers;

use App\Models\Localization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalizationsController extends Controller
{
    public function convert()
    {
        $tables = ['AffixDataTable', 'ItemPerks', 'MasterItemDefinitions_Common', 'MasterItemDefinitions_Crafting', 'MasterItemDefinitions_Faction', 'MasterItemDefinitions_Loot', 'MasterItemDefinitions_Named', 'MasterItemDefinitions_Omega', 'MasterItemDefinitions_Playtest', 'MasterItemDefinitions_Quest', 'MasterItemDefinitions_Store', 'StatusEffects', 'StatusEffects_Bow', 'StatusEffects_Common', 'StatusEffects_Firestaff', 'StatusEffects_Greataxe', 'StatusEffects_Greatsword', 'StatusEffects_Hatchet', 'StatusEffects_IceMagic', 'StatusEffects_Items', 'StatusEffects_Lifestaff', 'StatusEffects_Musket', 'StatusEffects_Perks', 'StatusEffects_Rapier', 'StatusEffects_Spear', 'StatusEffects_Sword', 'StatusEffects_VoidGauntlet', 'StatusEffects_Warhammer', 'AttributeThresholdAbilityTable', 'BowAbilityTable', 'FireMagicAbilityTable', 'GlobalAbilityTable', 'GreatAxeAbilityTable', 'HatchetAbilityTable', 'IceMagicAbilityTable', 'LifeMagicAbilityTable', 'MusketAbilityTable', 'RapierAbilityTable', 'SpearAbilityTable', 'SwordAbilityTable', 'VoidGauntletAbilityTable', 'WarHammerAbilityTable', 'DamageTypes', ];
        // cols to convert
        $column_names = ['Name', 'DisplayName', 'Description', 'ItemTypeDisplayName', 'AppliedSuffix',];
        
        $tables_data = [];
        $keys = [];
        foreach($tables as $table_name){
dump(' ---- TABLE: '.$table_name.' ---- ');

            $tables_data[$table_name] = [];
            
            foreach($column_names as $column_name){
                if(Schema::hasColumn($table_name, $column_name)){
                    $tables_data[$table_name]['columns'][]= $column_name;
                }
            } // end col names

            // get the data that needs converting
            $keys = DB::table($table_name)->select($tables_data[$table_name]['columns'])
                ->get()->flatten(1)->filter()->unique()->all();

            // remove empty and duplicates
            $keys = array_filter($keys);
//if($table_name == 'ItemPerks'){ die; }            
            foreach($keys as $key_array){
                // make sure not stdClass
                $key_array = (array)$key_array;
                if(empty(array_filter($key_array))){
                    continue;
                }
                foreach($key_array as $key) {
                    if(empty($key)){
                        continue;
                    }
                    // find the localized match, with or without @
                    /*$localization = Localization::whereRaw('MATCH(id_key) AGAINST (?,?,?) IN BOOLEAN MODE', 
                        [
                            ltrim($key, '@').', ' 
                            . ltrim($key, '@').'_MasterName, ' 
                            . ltrim($key, '@').'_Description'
                        ]
                    )
                        ->get()->first();*/
                    
                    $localization = Localization::whereIn('id_key', 
                        [
                            ltrim($key, '@'),  
                            ltrim($key, '@').'_MasterName', 
                            ltrim($key, '@').'_Description',
                        ]
                    )
                        ->get()->first();    
                    
                    dump("$key: ".$localization?->text);
                }
            }
        }
    
    }
    
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store( Request $request )
    {
        //
    }

    public function show( Localization $localization )
    {
        //
    }

    public function edit( Localization $localization )
    {
        //
    }

    public function update( Request $request, Localization $localization )
    {
        //
    }

    public function destroy( Localization $localization )
    {
        //
    }
}