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
        $tables = ['AffixDataTable', 'ItemPerks', 'MasterItemDefinitions_Common', 'MasterItemDefinitions_Crafting', 'MasterItemDefinitions_Faction', 'MasterItemDefinitions_Loot', 'MasterItemDefinitions_Named', 'MasterItemDefinitions_Omega', 'MasterItemDefinitions_Playtest', 'MasterItemDefinitions_Quest', 'MasterItemDefinitions_Store',  'StatusEffects', 'StatusEffects_Bow', 'StatusEffects_Common', 'StatusEffects_Firestaff', 'StatusEffects_Greataxe', 'StatusEffects_Greatsword', 'StatusEffects_Hatchet', 'StatusEffects_IceMagic', 'StatusEffects_Items', 'StatusEffects_Lifestaff', 'StatusEffects_Musket', 'StatusEffects_Perks', 'StatusEffects_Rapier', 'StatusEffects_Spear', 'StatusEffects_Sword', 'StatusEffects_VoidGauntlet', 'StatusEffects_Warhammer', 'AttributeThresholdAbilityTable', 'BowAbilityTable', 'FireMagicAbilityTable', 'GlobalAbilityTable', 'GreatAxeAbilityTable', 'HatchetAbilityTable', 'IceMagicAbilityTable', 'LifeMagicAbilityTable', 'MusketAbilityTable', 'RapierAbilityTable', 'SpearAbilityTable', 'SwordAbilityTable', 'VoidGauntletAbilityTable', 'WarHammerAbilityTable', 'DamageTypes', ];
        // cols to convert
        $column_names = ['Name', 'DisplayName', 'Description', 'ItemTypeDisplayName', 'AppliedSuffix', 'AppliedPrefix',];
        
        $tables_data = [];
        $keys = [];
        foreach($tables as $table_name){
dump(' ---- TABLE: '.$table_name.' ---- ');

            $tables_data[$table_name] = [];
            $tables_data[$table_name]['columns'] = [];
            
            foreach($column_names as $column_name){
                if(Schema::hasColumn($table_name, $column_name)){
                    $tables_data[$table_name]['columns'][]= $column_name;
                }
            } // end col names

            if(empty($tables_data[$table_name]['columns'])){
                // no data, skip
                continue;
            }
            
            // get the data that needs converting
            $id_keys = DB::table($table_name)->select($tables_data[$table_name]['columns'])
                // remove empty and duplicates
                ->get()->flatten(1)->filter()->unique()->all();
 
//if($table_name == 'ItemPerks'){ die; }
            
            foreach($id_keys as $id_key_array){
                // make sure not stdClass
                $id_key_array = (array)$id_key_array;
                if(empty(array_filter($id_key_array))){
                    continue;
                }
                foreach($id_key_array as $column_name => $id_key) {
//dd("$table_name.$column_name --> $id_key");
                    if(empty($id_key)){
                        continue;
                    }
                    // find the localized match, with or without @
                    /*$localization = Localization::whereRaw('MATCH(id_key) AGAINST (?,?,?) IN BOOLEAN MODE', 
                        [
                            ltrim($id_key, '@').', ' 
                            . ltrim($id_key, '@').'_MasterName, ' 
                            . ltrim($id_key, '@').'_Description'
                        ]
                    )
                        ->get()->first();*/
                    
                    $localization = Localization::where('id_key', 
//                        [
                            ltrim($id_key, '@'),  
                            /*ltrim($id_key, '@').'_MasterName', 
                            ltrim($id_key, '@').'_Description',*/
//                        ]
                    )
                        ->get()->first();    
                    $tables_data[$table_name][$column_name][$id_key] = strip_tags($localization?->text);
                    if(!empty(strip_tags($localization?->text))){
//                        dump("$id_key: ".$localization?->text);
                        DB::table($table_name)->where($column_name, $id_key)->update([$column_name => strip_tags($localization?->text)]);
                    }
                } // end each id_key
            } // end each idkey array
        } // end each table    
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