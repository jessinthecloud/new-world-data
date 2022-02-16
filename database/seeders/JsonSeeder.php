<?php

namespace Database\Seeders;

use App\Models\DataFile;
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

        foreach($dirs as $dir) {

// TODO: this will become really inefficient very quickly.
 // TODO: probably just build arrays for upserting after the dir loop is done
            
            $parsed_data = $parser->parseDir($dir);
            $data_files = array_merge($data_files, $parsed_data['data_files']);
            $values = array_merge($values, $parsed_data['values']);
            
            $data_file_types []= array_map(function($data){
                // trim off file extension
                return basename($data, '.json');
            }, array_column($data_files, 'filename'));
 
     
   dump($values, $data_files, $data_file_types);

   ////////////////////////////////         
          
            dump("Upserting values...");
        // TODO: create tables based on folder names
        // TODO: array keys are database columns to create the table with
        // TODO: map to localization files
            $column_names = isset($values[0]) ? array_keys($values[0]) : [];
            $table_name = array_column($data_files, 'directory')[0];
            
dd('COLUMN NAMES', $column_names, 'TABLE NAME: '.$table_name,);            
            
            Schema::create($table_name, function (Blueprint $table) use ($column_names) {
                $table->id();
                foreach($column_names as $column_name){
                    $table->string($column_name)->nullable();
                }
                $table->timestamps();
            });
            
            /*// chunk to avoid "too many parameters" SQL error
            foreach ( array_chunk($values, 5000) as $upsert ) {
                try {
                    DB::table('localizations')->upsert($upsert, ['id_key']);
                } catch ( \Throwable $throwable ) {
                    dump(
                        'ERROR OCCURRED: ' . substr($throwable->getMessage(), 0, 300),
                        'Error code: ' . $throwable->getCode()
                        . ' -- on line: ' . $throwable->getLine()
                        . ' -- in file: ' . $throwable->getFile()
                    );
                }
            } // end foreach chunk            */
        } // end foreach dir

        dump("Upserting {$dir} filenames...");
            DB::table('data_files')->upsert(
                $data_files, 
                ['directory', 'filename']
            );
$type_upsert = [];  
            foreach($data_file_types as $type){
dump($type);            
                $file_id = DataFile::where('filename', 'like', $type.'%')->first()?->id;
                if(empty($file_id)){
                    continue;
                }
                $type_upsert []= [
                    'file_id' => DataFile::where('filename', 'like', $type.'%')->first()->id,
                    'name' => $type,
                ];
            }
dd($type_upsert);            
            dump("Upserting {$dir} file types...");
            DB::table('data_file_types')->upsert(
                $type_upsert, 
                ['name']
            );
            


    }
}
