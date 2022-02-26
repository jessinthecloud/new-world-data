<?php

namespace App;

use App\Models\DataFile;
use App\Models\Localization;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TableBuilder
{
    public function __construct(protected ?SchemaBuilder $Schema=null) {
        // YOLO
        set_time_limit(0);
    }

    /******************
     * Definitions
     ******************
     * Column Size: determine max existing value for each table's (file's) key
     * 
     * Column Type: determine is_numeric() for value of each table's (file's) key
     *                  - if numeric, detect decimal or not
     * 
     * Primary/unique Key: First key name of array
     * 
     * Foreign Keys:
     *      - Localization FK: localization_id
     *      - DataFile FK: data_file_id
     *      - Dynamic FK: determine matching key names when file name does not match
     * 
     ******************
     * Values
     ******************
     * Primary/unique Key: First value of array
     * 
     * Foreign Keys:
     *      - Localization FK: localizations.id_key where looped key's value matches the localizations.text value 
     *      - DataFile FK: datafiles.id where table name matches the data_files.filename value
     *      - Dynamic FK: <other_table.key_name> where matches <this_table.key_name>
     *                      and <other_table.key_value> where matches <this_table.key_value>
     *                      when file name does not match
     */

    public function createTableInfo(array $data_array) : array
    {
        $tables_data = [];
       
        foreach($data_array as $data_index => $data){
            $dir = $data['dir'];
            $file = $data['file'];
            $table_name = $data['table']['name'];
            $column_names = $data['table']['columns'];
            $values = $data['values'];
            
        // CREATE TABLES DEFINITIONS
            $tables_data [$table_name]['values']=$values;
            $tables_data [$table_name]['columns']=[];
//            $tables_data [$table_name]['foreign_keys']=[];

            foreach($column_names as $index => $column_name) {
                // remove spaces from keys                
                $column_name = $this->ensureValidColumnName($column_name);
                
            //-- find column info; type, size
                $column_data = match(strtolower($column_name)){
                    // The localized text of these columns will be much 
                    // longer than the values here. Make sure they
                    // are long enough to fit the real text
                    'description' => ['type' => 'text', 'size'=>null],
                    'displayname' => ['type' => 'string', 'size'=>255],
                    'itemtypedisplayname' => ['type' => 'string', 'size'=>255],
                    'name' => ['type' => 'string', 'size'=>255],
                    'appliedsuffix' => ['type' => 'string', 'size'=>100],
                    'primary_hand','primary hand' => ['type' => 'string', 'size'=>5],
                    default => $this->findColumnInfo($column_name, $values) 
                };
                
                
                (strtolower($column_name) == 'description') 
                    ? ['type' => 'text', 'size'=>null] 
                    : $this->findColumnInfo($column_name, $values);
                
                // first key is the unique index (probably...)
                $column_data['unique'] = ($index === 0) 
                    ? 'uni_'.$table_name.'_'.Str::limit($column_name, 20, '')
                    : null; 

                $tables_data [$table_name]['columns'][$column_name]= $column_data;
                
            } // end foreach column names

            // localizations FK column
            $tables_data [$table_name]['columns']['localization_id']= [
                'name' => 'localization_id',
                'type' => 'unsignedBigInteger',
                'size' => null,
                'unique' => null,
            ];
            // data_files FK column
            $tables_data [$table_name]['columns']['data_file_id']= [
                'name' => 'data_file_id',
                'type' => 'unsignedBigInteger',
                'size' => null,
                'unique' => null,
            ];
            
            /*// find dynamic foreign key definitions (columns already exist)
            $tables_data = $this->findForeignKeys($table_name, $tables_data, $values, $data_array);
            
            /*
             * add known foreign key definitions
             *
            // localizations FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.$table_name.'_localization_id',
                'column_name' => 'localization_id',
                'references_column' => 'id',
                'on' => 'localizations',
            ];
            // data_files FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.$table_name.'_file_id',
                'column_name' => 'data_file_id',
                'references_column' => 'id',
                'on' => 'data_files',
            ];*/
//dd($tables_data);
        } // end foreach data
        
        return $tables_data;
    }
    
    /**
     * @param array $tables_data
     * @param bool  $dropIfExists - drops existing tables when true, skips existing when false
     *
     * @return void
     */
    public function createTables(array $tables_data, bool $dropIfExists=true){
        // TODO: loop to create table first, then loop for updating table to add foreign keys
        //       to ensure the referenced tables/columns exist

        // create the tables
        foreach($tables_data as $table_name => $table_data){
            $this->createTable($table_name, $table_data, $dropIfExists);
        }

        /*
        // add any foreign keys to the tables
        foreach($tables_data as $table_name => $table_data){
            $this->addForeignKeysToTable($table_name, $table_data);
        }
        */
    }
    
    protected function createTable(string $table_name, array $table_data, bool $dropIfExists=true)
    {        
        dump("Creating table {$table_name}..."/*, $table_data['columns']*/);

        if($this->shouldSkipTable($table_name, $dropIfExists)){
            dump("{$table_name} table already exists.");
            return;
        }
        
        Schema::create($table_name, function (Blueprint $table) use ($table_name, $table_data) {
        
            $columns_data = $table_data['columns'];
                            
            // still auto inc primary key
            $table->id();
            
            foreach ( $columns_data as $column_name => $column_data ) {

                $column_type = $column_data['type'];
                $column_size = $column_data['size'];
                $column_unique_name = $column_data['unique'];
            
                if (Schema::hasColumn($table_name, $column_name) ) {
                    continue;
                }

                // define column
                $table = $this->createColumn($table, $column_name, $column_type, $column_size, $column_unique_name);
                
            } // end foreach column
            
            // created/updated
            $table->timestamps();
        });
        dump("{$table_name} table created.");
    }
    
    protected function ensureValidColumnName(string $name) : string
    {
        return Str::replace(' ', '_', $name);
    }
    
    protected function findColumnInfo(string $column_name, array $values) : array
    {
        $is_numeric = false;
        // remove empty values
        $column_values = array_filter(array_column($values, $column_name));
        
        // check for numbers
        $numerics = array_filter($column_values, function($value){
            return is_numeric($value);
        });
        
        // remaining values must all be numeric in order for
        // the column to not be set to a string type
        if(sizeof($numerics) === sizeof($column_values)){
            $is_numeric = true;
        }

        return $is_numeric ? $this->findNumericColumnInfo($column_values) : $this->findTextColumnInfo($column_values);
    }
    
    protected function findNumericColumnInfo(array $column_values) : array
    {
        if(empty(array_filter($column_values))){
            // no values
            return [
                'type'=>'tinyInteger',
                // tiny int must not have size so that size arg passed
                // to tinyInteger() doesn't trigger auto_increment being set
                'size'=>false,
            ];
        }
        
        // check for decimal
        $has_decimal = !empty(array_filter($column_values, function($value){
            return str_contains($value, '.');
        }));

        // find the largest number by numeric value (not string value) 
        $max = max(array_map(function($val) use ($has_decimal) {
            return $has_decimal ? floatval($val) : intval($val);
        }, $column_values));

        // check for negative sign
        $unsigned = empty(array_filter($column_values, function($val) use ($has_decimal) {
            return str_contains(($has_decimal ? floatval($val) : intval($val)), '-');
        }));

        $type = match(true){
            // mysql float is smaller than double
            $has_decimal => 'float',
            $max <= 127 || ($unsigned && $max <= 255) => 'tinyInteger',
            $max <= 32767 || ($unsigned && $max <= 65535) => 'smallInteger',
            $max <= 8388607 || ($unsigned && $max <= 16777215) => 'mediumInteger',
            $max <= 2147483647 || ($unsigned && $max <= 4294967295) => 'integer',
            $max > 2147483647 || ($unsigned && $max > 4294967295) => 'bigInteger'
        };
        
        if($unsigned){
            $type = 'unsigned'.ucFirst($type);
        }
        // # of digits 
        // not including sign, commas, or decimal points
        $size = strlen(str_replace(['-','.',','], '', $max));
        
        return [
            'type'=>$type,
            // # of digits 
            // float must be at least 2 (M must be >= D in column definition)
            'size'=> $has_decimal ? max($size, 2) : $size,
        ];
    }

    protected function findTextColumnInfo(array $column_values) : array
    {
        if(empty(array_filter($column_values))){
            // no values
            return [
                'type'=>'tinyInteger',
                'size'=>1,
            ];
        }
    
        // longest string
        $max = max(array_map('strlen', $column_values));
        
        $type = match(true){
            $max <= 255 => 'string',
            $max > 255 && $max < 65536 => 'text',
            $max >= 65536 && $max < 16777216 => 'mediumText',
            $max >= 16777216 => 'longText'
        };
        
        return [
            'type'=>$type,
            // # of chars 
            'size'=>$max,
        ];
    }
    
    protected function createColumn($table, $column_name, $column_type, $column_size, $column_unique_name=null)
    {
        if(str_contains(strtolower($column_type), 'integer')) {
            // prevent int with size setting auto_increment=true
            $column = $table->$column_type($column_name)->nullable();
        }
        else{
            
            if(str_contains(strtolower($column_type), 'float')) {
                // size of float col is the TOTAL precision, must account for decimal places
                $column_size = $column_size+2;
            }
            $column = $table->$column_type($column_name, $column_size)->nullable();
        }
        
        if(isset($column_unique_name)){
            // this column is the ID field for the JSON file
            $column->unique($column_unique_name);
        }
        
        return $table;
    }
    
    private function shouldSkipTable(string $table_name, bool $dropIfExists) : bool
    {
        if($dropIfExists){
            // drop table if exists to avoid foreign key collisions
            //       since we can't check for them
            Schema::dropIfExists($table_name);
            return false;
        }
        
        return Schema::hasTable($table_name);
    }

    public function createForeignKeysInfo(array $data_array, array $tables_data) : array
    {
        dump("Creating Foreign key info...");
        
        if(!Schema::hasTable('foreign_key_map')){
            Schema::create('foreign_key_map', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('table_name')->index();
                $table->string('column_name');
                $table->string('references_column');
                $table->string('on_table');
                $table->text('value')->nullable();
                // created/updated
                $table->timestamps();
            });
        }
        
        foreach($data_array as $data_index => $data){
        
            $table_name = $data['table']['name'];
            $values = $data['values'];

            if(DB::table('foreign_key_map')
                ->where('table_name', $table_name)
                ->count() > 0){
                dump('skipping '.$table_name.'...');
                // we already found foreign keys for this table
                continue;
            }
            
            dump("Finding foreign keys for $table_name...");

            $tables_data [$table_name]['foreign_keys']=[];
            
            // find dynamic foreign key definitions (columns already exist)
            // TEMP DISABLE
            // TODO: re-enable and fix/run
//            $tables_data [$table_name]['foreign_keys'] = $this->findForeignKeys($table_name, $tables_data, $values, $data_array);
            
            /*
             * add known foreign key definitions
             */
            // localizations FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.$table_name.'_localization_id',
                'table_name' => $table_name,
                'column_name' => 'localization_id',
                'references_column' => 'id',
                'on_table' => 'localizations',
                'value' => DataFile::where('filename', $table_name.'.json')->first()?->id,
            ];
            // data_files FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.$table_name.'_file_id',
                'table_name' => $table_name,
                'column_name' => 'data_file_id',
                'references_column' => 'id',
                'on_table' => 'data_files',
                'value' => Localization::where(
                    'id_key',
                    'like',
                    '%' . array_key_first($data['values']) . '%'
                )->first()?->id,
            ];
            
            // save data
            foreach(array_chunk($tables_data[$table_name]['foreign_keys'], 5000) as $foreign_keys){
                DB::table('foreign_key_map')
                    ->upsert(
                        $foreign_keys,
                        ['name']
                    )
                ;
            }
            
        } // end foreach data
        
        return $tables_data;
    }
    
    public function addForeignKeysToTables(array $tables_data){
        // add any foreign keys to the tables
        foreach($tables_data as $table_name => $table_data){
            $this->addForeignKeysToTable($table_name, $table_data);
        }
    }
    
     protected function findForeignKeys(string $table_name, array $tables_data, array $values, array $data_array) : array
     {
        // exclude current table columns that are not unique indexes
        $columns_data = array_filter($tables_data[$table_name]['columns'], function($value, $key){
            return isset($value['unique']);
        }, ARRAY_FILTER_USE_BOTH);
        
        // exclude current table values that are not unique indexes' column values
        $values = array_map(function($val) use ($columns_data) {
            return array_intersect_key($val, array_flip(array_column($columns_data, 'name')));
        }, $values);
        
        // exclude current table from tables we will check for matches
        $data_array = array_filter($data_array, function($val) use ($table_name) {
            return $val['table']['name'] != $table_name;
        });

        return $this->findForeignKeyData($table_name, $values, $data_array);
     }

    /**
     * @param string $table_name
     * @param array  $values
     * @param array  $data_array
     *
     * @return array
     */
     protected function findForeignKeyData(string $table_name, array $values, array $data_array) : array
     {        
        $foreign_keys = [];
        
        // remove numeric values from searchable values
        $values = array_filter($values, function($value){
            return !is_numeric(key(array_flip($value)));
        });
              
        foreach($values as $column_name => $value_array){
            
            // search column's values for matching values in other tables:
            foreach($data_array as $data){
                $other_table = $data['table']['name'];
                $other_values = $data['values'];

                // get only the other values arrays where a value matches
                $other_values = array_filter($other_values, function($other_value_array) use ($value_array) {
                    return !empty(array_intersect($value_array, $other_value_array));
                });

                if(!empty(array_filter($other_values))){
                    // narrow down the matching values array to only the
                    // key=>value pair that matches the currently searched values
                    collect($value_array)->each(function($value, $key) use ($table_name, $other_table, $other_values, &$foreign_keys) {
                        
                        foreach($other_values as $other_value_array){
                        
                            $found_key = array_search($value, $other_value_array);
                        
                            if(empty($found_key)){
                                continue;
                            }
                            
                            $foreign_keys []= [
                               'name' =>  'fk_'.$table_name.'_'.$found_key, // this table + related column
                               'table_name' => $table_name, // this table
                               'column_name' => $key, //key($value), // this table column
                               'references_column' => $found_key, // related column
                               'on_table' => $other_table, // related table
                               'value' => $value, 
                            ];
                        }                        
                    })->all();
//dd('-- end --', $foreign_keys);
                }
            }
        }
//if(!empty(array_filter($foreign_keys))){ dump('FK\'s:', $foreign_keys); }
        return array_filter($foreign_keys);    
    }
     
    /**
     * Add foreign key constraints to an existing database table
     * 
     * @param string $table_name
     * @param array  $table_data
     *
     * @return void
     */
    protected function addForeignKeysToTable(string $table_name, array $table_data)
    {
        Schema::table($table_name, function (Blueprint $table) use ($table_name, $table_data) {
        
            $foreign_keys_data = $table_data['foreign_keys'];
            
            foreach ( $foreign_keys_data as $index => $foreign_key_data ) {
                $this->addForeignKeyToTable($table, $table_name, $foreign_key_data);                
            } // end foreach foreign key
        });
        
        dump("{$table_name} table foreign keys added.");
    }

    protected function addForeignKeyToTable(Blueprint $table, string $table_name, array $foreign_key_data) : void
    {
        dump("Adding foreign key {$foreign_key_data['name']} ({$foreign_key_data['column_name']}) to table {$table_name}...");

        $fk_name = $foreign_key_data['name'];
        $column_name = $foreign_key_data['column_name'];
        $fk_references = $foreign_key_data['references_column'];
        $fk_on = $foreign_key_data['on_table'];
        
        // sail user does not have select permissions on information_schema in docker
        /*if(Schema::hasColumn($table_name, $column_name) 
            && $this->hasForeignKey($table_name, $fk_name)){
            $table->dropForeign($fk_name);
        }*/
        
        /*
        // doesn't catch before laravel handles it
        try {
            // drop if exists
            $table->dropForeign($fk_name);
        }
        catch (QueryException $e){
            dump(
                "Exception encountered; Foreign key probably exists: "
                . substr($e->getMessage(), 0, 300),
                'Error code: ' . $e->getCode()
                . ' -- on line: ' . $e->getLine()
                . ' -- in file: ' . $e->getFile()
            );
        }*/
        
        // define constraint
        $table->foreign($column_name, $fk_name)->references($fk_references)->on($fk_on);
    }
    
    /*// sail user does not have select permissions on information_schema in docker...
    protected function getForeignKeys(string $table_name)
    {
        // SELECT CONSTRAINT_NAME FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `constraint_schema` = SCHEMA() AND `table_name` = ? AND `referenced_column_name` IS NOT NULL;
        return DB::table("information_schema.KEY_COLUMN_USAGE")
            ->select("CONSTRAINT_NAME")
            ->whereRaw("constraint_schema = SCHEMA() AND table_name = ? AND referenced_column_name IS NOT NULL", $table_name)
            ->get()->all();
    }
    
    protected function hasForeignKey(string $table_name, string $fk_name) : bool
    {
        return in_array($fk_name, $this->getForeignKeys($table_name));
    }*/

    /**
     * @param array $tables_data
     *
     * @return void
     */
    public function upsertTablesValues(array $tables_data)
    {
        foreach ( $tables_data as $table_name => $table_data ) {
        
            dump("Inserting data for $table_name...");
        
            foreach ( $table_data['values'] as $index => $db_values ) {

                // first key should be the uniqueID column, i.e., ItemID, WeaponID
                $unique_key = array_key_first($db_values);
                
                // map to dir/file entry in data_files
                $file_id = DataFile::where('filename', $table_name.'json')->first()?->id;
                $db_values ['data_file_id']= $file_id;

                // map to localization files
                $localization_id = Localization::where(
                    'id_key',
                    'like',
                    '%' . $unique_key . '%'
                )->first()?->id;
                
                $db_values ['localization_id']= $localization_id;
//dump($db_values);
                foreach($db_values as $colname => $value){
                
                    // make sure name is valid
                    // replace invalid key => value pairs
                    $oldcol = $colname;
                    $colname = $this->ensureValidColumnName($colname);
                    if($oldcol != $colname){
                        unset($db_values[$oldcol]);
                        $db_values[$colname]= $value;
                    }
//dump("$colname :: $value --> ".$db_values[$colname]);
                    // account for weird values
                    // i.e., StatusEffects_Bow.RemoveUnappliedStacks is normally a number but also "FALSE"
                    if((str_contains(strtolower($table_data['columns'][$colname]['type']), 'integer')
                        || str_contains(strtolower($table_data['columns'][$colname]['type']), 'float'))
                        && !is_numeric($value) && !is_bool($value) 
                        && (strtolower($value) === "false" || strtolower($value) === "true")
                    ){
                        $db_values[$colname] = null;
                    }
                    
                    if(!empty($value)){
                        continue;
                    }
                    
                    // make sure values are null instead of empty string if value is empty and a number
                    if(str_contains(strtolower($table_data['columns'][$colname]['type']), 'integer')
                        || str_contains(strtolower($table_data['columns'][$colname]['type']), 'float')){
                        $db_values[$colname] = null;
                    }
                }
                
//                try {
                    DB::table($table_name)->upsert($db_values, [$unique_key]);
                /*} catch ( \Throwable $throwable ) {
                    dump(
                        'ERROR OCCURRED: ' 
                            . $throwable->getMessage(),
                            'Error code: ' . $throwable->getCode()
                            . ' -- on line: ' . $throwable->getLine()
                            . ' -- in file: ' . $throwable->getFile()
                    );
                    die;
                } // end try/catch*/
            } // end foreach table values
        } // end foreach tables data
    } // end upsertTableValues()
}