<?php

namespace Database\Seeders;

use App\Models\DataFile;
use App\Models\Localization;
use App\Parsers\JsonFileParser;
use App\TableBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JsonSeeder extends Seeder
{
    public function run()
    {
        $base_dir = __DIR__.'/../../storage/app/json';
        $dirs = [

//            $base_dir.DIRECTORY_SEPARATOR.'CategoricalProgressionData',
//            $base_dir.DIRECTORY_SEPARATOR.'CategoricalProgressionRankData',
//            $base_dir.DIRECTORY_SEPARATOR.'DamageData',
//            $base_dir.DIRECTORY_SEPARATOR.'EncumbranceData',
//            $base_dir.DIRECTORY_SEPARATOR.'EntitlementData',
//            $base_dir.DIRECTORY_SEPARATOR.'ExperienceData',
//            $base_dir.DIRECTORY_SEPARATOR.'FactionControlBuffDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'FactionData',
//            $base_dir.DIRECTORY_SEPARATOR.'FactionStatusEffectData',
//            $base_dir.DIRECTORY_SEPARATOR.'GameEventData', // literal events in engine
//            $base_dir.DIRECTORY_SEPARATOR.'GatherableData',
//            $base_dir.DIRECTORY_SEPARATOR.'KitItemDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'LevelDisparityData',
//            $base_dir.DIRECTORY_SEPARATOR.'LootLimitData',
//            $base_dir.DIRECTORY_SEPARATOR.'LootTablesData',
//            $base_dir.DIRECTORY_SEPARATOR.'LoreItemDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'ProgressionPointData',
//            $base_dir.DIRECTORY_SEPARATOR.'ProgressionPoolData',
//            $base_dir.DIRECTORY_SEPARATOR.'RewardMilestoneData', // not items
//            $base_dir.DIRECTORY_SEPARATOR.'RewardData', // not items
//            $base_dir.DIRECTORY_SEPARATOR.'RewardModifierData',
//            $base_dir.DIRECTORY_SEPARATOR.'SpecializationDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'SpellData',
            
        // -- items
            $base_dir.DIRECTORY_SEPARATOR.'AbilityData',
            $base_dir.DIRECTORY_SEPARATOR.'AffixData',
            $base_dir.DIRECTORY_SEPARATOR.'AffixStatData',
//            $base_dir.DIRECTORY_SEPARATOR.'AfflictionData',
//            $base_dir.DIRECTORY_SEPARATOR.'AmmoItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'ArmorItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'AttributeDefinition',
//            $base_dir.DIRECTORY_SEPARATOR.'BlueprintItemDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'ConsumableItemDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'CraftingCategoryData',
//            $base_dir.DIRECTORY_SEPARATOR.'CraftingRecipeData',
            $base_dir.DIRECTORY_SEPARATOR.'DamageTypeData',
//            $base_dir.DIRECTORY_SEPARATOR.'GearScoreUpgradeDefinition',
//            $base_dir.DIRECTORY_SEPARATOR.'ItemTransform',
            $base_dir.DIRECTORY_SEPARATOR.'MasterItemDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'PerkBucketData', // row size too large
            $base_dir.DIRECTORY_SEPARATOR.'PerkData',
//            $base_dir.DIRECTORY_SEPARATOR.'ResourceItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'SkillData',
//            $base_dir.DIRECTORY_SEPARATOR.'SkillExperienceData', // level up values
            $base_dir.DIRECTORY_SEPARATOR.'StatusEffectCategoryData',
            $base_dir.DIRECTORY_SEPARATOR.'StatusEffectData',
            $base_dir.DIRECTORY_SEPARATOR.'WeaponItemDefinitions',
            
            // tradeskills
//            $base_dir.DIRECTORY_SEPARATOR.'TradeSkillPostCapData',
//            $base_dir.DIRECTORY_SEPARATOR.'TradeskillRankData',
            
            // war board 
//            $base_dir.DIRECTORY_SEPARATOR.'ContributionData',
//            $base_dir.DIRECTORY_SEPARATOR.'WarboardStatDefinitions',
//            $base_dir.DIRECTORY_SEPARATOR.'GameModeData',
        ];
        
        $parser = new JsonFileParser();
        $values = [];
        $data_files =[];
        $combos =[];
        $tables =[];
        $columns =[];
        
        foreach($dirs as $dir) {
            
            $parsed_data = $parser->parseDir($dir);

            $data_files = array_merge($data_files, $parsed_data['data_files']);
            $combos []= $parsed_data['combo'];
                       
            // dir name is the table name to create
            $tables = array_merge($tables, $parsed_data['tables']);
        } // end foreach dir
        
        $combos = collect($combos)->flatten(1)->all();
        
        $TableBuilder = new TableBuilder();
        $tables_data = $TableBuilder->createTableInfo($combos);
        $TableBuilder->createTables($tables_data, false);
        dump("Tables created.");
        $tables_data = $TableBuilder->createForeignKeysInfo($combos, $tables_data);
        $TableBuilder->addForeignKeysToTables($tables_data);
        $TableBuilder->upsertTablesValues($tables_data);
/////////////////////////////////////
die;        
        dump("Upserting ".basename($dir)." filenames...");
        
        foreach($combos as $index => $combo) {
            foreach ( $combo['values'] as $db_values ) {
                
                $dir = $combo['dir'];
                $file = $combo['file'];
                $table_name = $combo['table']['name'];
                $columns = $combo['table']['columns'];

                // first key should be the uniqueID column, i.e., ItemID, WeaponID
                $unique_key = array_key_first($db_values);

                // map to dir/file entry in data_files
                $file_id = DataFile::where('filename', $file)->first()?->id;
                $db_values ['file_id']= $file_id;

                // map to localization files
                $localization_id = Localization::where(
                    'id_key',
                    'like',
                    '%' . basename($unique_key, '.json') . '%'
                )->first()?->id;
                
                $db_values ['localization_id']= $localization_id;

                try {
                    DB::table($table_name)->upsert($db_values, [$unique_key]);
                } catch ( \Throwable $throwable ) {
                    dump(
                        'ERROR OCCURRED: ' 
                            . substr($throwable->getMessage(), 0, 300),
                            'Error code: ' . $throwable->getCode()
                            . ' -- on line: ' . $throwable->getLine()
                            . ' -- in file: ' . $throwable->getFile()
                    );
                    die;
                }
            } // foreach values
        } // foreach combo
    } // foreach combos    
}
