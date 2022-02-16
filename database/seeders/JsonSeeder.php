<?php

namespace Database\Seeders;

use App\Models\DataFile;
use App\Models\Localization;
use App\Parsers\JsonFileParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JsonSeeder extends Seeder
{
    public function run()
    {
        $base_dir = __DIR__.'/../../storage/app/json';
        $dirs = [
            $base_dir.DIRECTORY_SEPARATOR.'AbilityData',
            $base_dir.DIRECTORY_SEPARATOR.'AchievementData',
            $base_dir.DIRECTORY_SEPARATOR.'AffixData',
            $base_dir.DIRECTORY_SEPARATOR.'AffixStatData',
            $base_dir.DIRECTORY_SEPARATOR.'AfflictionData',
            $base_dir.DIRECTORY_SEPARATOR.'AmmoItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'ArmorAppearanceDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'ArmorItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'AttributeDefinition',
            $base_dir.DIRECTORY_SEPARATOR.'BlueprintItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'CategoricalProgressionData',
            $base_dir.DIRECTORY_SEPARATOR.'CategoricalProgressionRankData',
            $base_dir.DIRECTORY_SEPARATOR.'ConsumableItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'ContributionData',
            $base_dir.DIRECTORY_SEPARATOR.'CraftingCategoryData',
            $base_dir.DIRECTORY_SEPARATOR.'CraftingRecipeData',
            $base_dir.DIRECTORY_SEPARATOR.'DamageData',
            $base_dir.DIRECTORY_SEPARATOR.'DamageTypeData',
            $base_dir.DIRECTORY_SEPARATOR.'DivertedLootData',
            $base_dir.DIRECTORY_SEPARATOR.'EncumbranceData',
            $base_dir.DIRECTORY_SEPARATOR.'EntitlementData',
            $base_dir.DIRECTORY_SEPARATOR.'ExperienceData',
            $base_dir.DIRECTORY_SEPARATOR.'FactionControlBuffDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'FactionData',
            $base_dir.DIRECTORY_SEPARATOR.'FactionStatusEffectData',
            $base_dir.DIRECTORY_SEPARATOR.'GameEventData',
            $base_dir.DIRECTORY_SEPARATOR.'GameModeData',
            $base_dir.DIRECTORY_SEPARATOR.'GatherableData',
            $base_dir.DIRECTORY_SEPARATOR.'GearScoreUpgradeDefinition',
            $base_dir.DIRECTORY_SEPARATOR.'ItemCurrencyConversionData',
            $base_dir.DIRECTORY_SEPARATOR.'ItemSkinData',
            $base_dir.DIRECTORY_SEPARATOR.'ItemSoundEvents',
            $base_dir.DIRECTORY_SEPARATOR.'ItemTooltipLayout',
            $base_dir.DIRECTORY_SEPARATOR.'ItemTransform',
            $base_dir.DIRECTORY_SEPARATOR.'KitItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'LevelDisparityData',
            $base_dir.DIRECTORY_SEPARATOR.'LootBucketData',
            $base_dir.DIRECTORY_SEPARATOR.'LootLimitData',
            $base_dir.DIRECTORY_SEPARATOR.'LootTablesData',
            $base_dir.DIRECTORY_SEPARATOR.'LoreItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'MasterItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'PerkBucketData',
            $base_dir.DIRECTORY_SEPARATOR.'PerkData',
            $base_dir.DIRECTORY_SEPARATOR.'PlayerTitleData',
            $base_dir.DIRECTORY_SEPARATOR.'ProgressionPointData',
            $base_dir.DIRECTORY_SEPARATOR.'ProgressionPoolData',
            $base_dir.DIRECTORY_SEPARATOR.'PromotionMutationStaticData',
            $base_dir.DIRECTORY_SEPARATOR.'ResourceItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'RewardData',
            $base_dir.DIRECTORY_SEPARATOR.'RewardMilestoneData',
            $base_dir.DIRECTORY_SEPARATOR.'RewardModifierData',
            $base_dir.DIRECTORY_SEPARATOR.'SimpleTreeCategoryData',
            $base_dir.DIRECTORY_SEPARATOR.'SkillData',
            $base_dir.DIRECTORY_SEPARATOR.'SkillExperienceData',
            $base_dir.DIRECTORY_SEPARATOR.'SpecializationDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'SpellData',
            $base_dir.DIRECTORY_SEPARATOR.'StatusEffectCategoryData',
            $base_dir.DIRECTORY_SEPARATOR.'StatusEffectData',
            $base_dir.DIRECTORY_SEPARATOR.'TerritoryDefinition',
            $base_dir.DIRECTORY_SEPARATOR.'TerritoryProgressionData',
            $base_dir.DIRECTORY_SEPARATOR.'TerritoryUpkeepDefinition',
            $base_dir.DIRECTORY_SEPARATOR.'TradeSkillPostCapData',
            $base_dir.DIRECTORY_SEPARATOR.'TradeskillRankData',
            $base_dir.DIRECTORY_SEPARATOR.'VitalsCategoryData',
            $base_dir.DIRECTORY_SEPARATOR.'VitalsData',
            $base_dir.DIRECTORY_SEPARATOR.'VitalsLevelData',
            $base_dir.DIRECTORY_SEPARATOR.'VitalsModifierData',
            $base_dir.DIRECTORY_SEPARATOR.'WarboardStatDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'WeaponEffectData',
            $base_dir.DIRECTORY_SEPARATOR.'WeaponItemDefinitions',
        ];
        
        $parser = new JsonFileParser();
        $values = [];
        $data_files =[];
        $data_file_types =[];
        $tables =[];

        foreach($dirs as $dir) {
            
            $parsed_data = $parser->parseDir($dir);
            $data_files = array_merge($data_files, $parsed_data['data_files']);
            $values = array_merge($values, $parsed_data['values']); 
            
            // array keys are database columns to create the table with
            $column_names = isset($values[0]) ? array_keys($values[0]) : [];
            // dir name is the table name to create
            $table_name = array_column($data_files, 'directory')[0];
            $tables [$table_name]=$column_names;
        } // end foreach dir
        
//dd('VALUES: ',$values, 'DATA FILES: ', $data_files, 'TABLES INFO:', $tables);

        dump("Upserting {$dir} filenames...");
        //  SQLSTATE[42000]: Syntax error or access violation: 1118 Row size too large.
        // The maximum row size for the used table type, not counting BLOBs, is 65535.
        // This includes storage overhead, check the manual.
        // You have to change some columns to TEXT or BLOBs (SQL: create table `AbilityData` 
        DB::table('data_files')->upsert(
            $data_files, 
            ['directory', 'filename']
        );

        foreach($tables as $table){
            // create tables based on folder names
            Schema::create($table_name, function (Blueprint $table) use ($column_names) {
                $table->id();
                foreach($column_names as $column_name){
                    // TODO: determine the unique ID column for each directory?
                        // can probably just use the first array key... 
                        // TODO: mark that col as ->unique() ?
                    $table->text($column_name)->nullable();
                }
                // link back to file it comes from
                $table->foreignId('file_id')->constrained('data_files');
                // link to localization entry
                $table->foreignId('localization_id')->nullable()->constrained();
                $table->timestamps();
            });
        }
            
        // insert values
        dump("Upserting values...");
        // chunk to avoid "too many parameters" SQL error
        foreach(array_chunk($values, 5000) as $dir => $value_array){
            foreach($value_array as $file => $db_values){
                // first key should be the uniqueID column, i.e., ItemID, WeaponID
                // TODO: install doctrine/dbal to modify the matching column and set it as ->unique() ?
                $unique_key = array_key_first($db_values);
                
                // map to dir/file entry in data_files
                $file_id = DataFile::where('filename', $file)->first()?->id;
                $db_values ['file_id']= $file_id;
//if(is_array($db_values[$unique_key])) { dd($db_values[$unique_key], $unique_key); }
                // map to localization files
                $localization_id = Localization::where('id_key', 'like', '%'.basename($unique_key, '.json').'%')->first()?->id;
                $db_values ['localization_id']= $localization_id;

                try {
                    // dir is table name
//                    $columns = Schema::getColumnListing($dir);
                    DB::table($dir)->upsert($db_values, [$unique_key]);
                } catch ( \Throwable $throwable ) {
                    dump(
                        'ERROR OCCURRED: ' . substr($throwable->getMessage(), 0, 300),
                        'Error code: ' . $throwable->getCode()
                        . ' -- on line: ' . $throwable->getLine()
                        . ' -- in file: ' . $throwable->getFile()
                    );
                }
            }
        }
    }
}
