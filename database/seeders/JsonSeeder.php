<?php

namespace Database\Seeders;

use App\Models\DataFile;
use App\Models\Localization;
use App\Parsers\JsonFileParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JsonSeeder extends Seeder
{
    public function run()
    {
        $base_dir = __DIR__.'/../../storage/app/json';
        $dirs = [
            $base_dir.DIRECTORY_SEPARATOR.'AbilityData',
            $base_dir.DIRECTORY_SEPARATOR.'AffixData',
            $base_dir.DIRECTORY_SEPARATOR.'AffixStatData',
            $base_dir.DIRECTORY_SEPARATOR.'AfflictionData',
            $base_dir.DIRECTORY_SEPARATOR.'AmmoItemDefinitions',
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
            $base_dir.DIRECTORY_SEPARATOR.'ProgressionPointData',
            $base_dir.DIRECTORY_SEPARATOR.'ProgressionPoolData',
            $base_dir.DIRECTORY_SEPARATOR.'ResourceItemDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'RewardData',
            $base_dir.DIRECTORY_SEPARATOR.'RewardMilestoneData',
            $base_dir.DIRECTORY_SEPARATOR.'RewardModifierData',
            $base_dir.DIRECTORY_SEPARATOR.'SkillData',
            $base_dir.DIRECTORY_SEPARATOR.'SkillExperienceData',
            $base_dir.DIRECTORY_SEPARATOR.'SpecializationDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'SpellData',
            $base_dir.DIRECTORY_SEPARATOR.'StatusEffectCategoryData',
            $base_dir.DIRECTORY_SEPARATOR.'StatusEffectData',
            $base_dir.DIRECTORY_SEPARATOR.'TradeSkillPostCapData',
            $base_dir.DIRECTORY_SEPARATOR.'TradeskillRankData',
            $base_dir.DIRECTORY_SEPARATOR.'WarboardStatDefinitions',
            $base_dir.DIRECTORY_SEPARATOR.'WeaponEffectData',
            $base_dir.DIRECTORY_SEPARATOR.'WeaponItemDefinitions',
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
//dd($tables);
        dump("Upserting ".basename($dir)." filenames...");
        
        DB::table('data_files')->upsert(
            $data_files, 
            ['directory', 'filename']
        );
        
        foreach($tables as $table_name => $column_names){
            dump("Creating {$table_name} table...");

            if (Schema::hasTable($table_name)) {
                continue;
            }
            /*
             *  SQLSTATE[42000]: Syntax error or access violation: 1118 Row size too large (> 8126). Changing some columns to TEXT or BLOB may help. In current row format, BLOB prefix of 0 bytes is stored inline.
             */
            // create tables based on folder names
            Schema::create($table_name, function (Blueprint $table) use ($table_name, $column_names) {
                // make sure column names are valid and not dupes
                array_walk($column_names, function(&$column_name){
                    $column_name = Str::snake($column_name);
                });
                $column_names = array_unique($column_names);
                
                // still auto inc primary key
                $table->id();
                // link back to file it comes from
                $table->foreignId('file_id')->constrained('data_files');
                // link to localization entry
                // need manually done bc auto-gen name is too long
                $table->bigInteger('localization_id')->nullable()->unsigned();
                $table->foreign('localization_id', 'fk_'.substr($table_name,0,10).'_localize_id')->references('id')->on('localizations');
                
                foreach ( $column_names as $index => $column_name ) {
                    if (Schema::hasColumn($table_name, $column_name) ) {
                        continue;
                    }
                    // TODO: determine the unique ID column for each directory?
                    // can probably just use the first array key... 
                    // TODO: mark that col as ->unique() ?
                    if ( $index == 0 ) {
                        $table->string($column_name)->nullable()->unique();
                    } else {
                        $table->text($column_name)->nullable();
                    }

                } // end foreach column
                
                // created/updated
                $table->timestamps();
            });
            dump("{$table_name} table created.");
        } // end foreach table

        // insert values
        dump("Upserting values...");
        /*
         * $combos[]['dir']
         * $combos[]['file']
         * $combos[]['values']
         */
        // chunk to avoid SQL error
        foreach(array_chunk($combos, 5000) as  $combo_array) {
            foreach ( $combo_array as $combo_arr ) {
                foreach ( $combo_arr as $index => $combo ) {
                    foreach ( $combo['values'] as $db_values ) {

                        $file = $combo['file'];
                        $dir = $combo['dir'];
                        $table_name = $combo['table'];
                        
                        // TODO: install doctrine/dbal to modify the matching column
                        //       and set it as ->unique() ?
                        // first key should be the uniqueID column, i.e., ItemID, WeaponID
                        $unique_key = array_key_first($db_values);

                        // map to dir/file entry in data_files
                        $file_id = DataFile::where('filename', $file)->first()?->id;
                        $db_values ['file_id'] = $file_id;

                        // map to localization files
                        $localization_id = Localization::where(
                            'id_key',
                            'like',
                            '%' . basename($unique_key, '.json') . '%'
                        )->first()?->id;
                        $db_values ['localization_id'] = $localization_id;

                        try {
                            // dir is table name
                            //                    $columns = Schema::getColumnListing($dir);
                            DB::table($table_name)->upsert($db_values, [$unique_key]);
                        } catch ( \Throwable $throwable ) {
                            dump(
                                'ERROR OCCURRED: ' . substr($throwable->getMessage(), 0, 300),
                                'Error code: ' . $throwable->getCode()
                                . ' -- on line: ' . $throwable->getLine()
                                . ' -- in file: ' . $throwable->getFile()
                            );
                        }
                    } // foreach values
                } // foreach combo
            }
        } // foreach combos
    }
}
