<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TableBuilder
{
    public function __construct(protected ?SchemaBuilder $Schema=null) { }

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
            $tables_data [$table_name]['columns']=[];
            $tables_data [$table_name]['foreign_keys']=[];

            foreach($column_names as $index => $column_name) {
            
            //-- find column info; type, size
                $column_data = $this->findColumnInfo($column_name, $values);
                $column_data['name'] = $column_name;
                // first key is the unique index (probably...)
                $column_data['unique'] = ($index === 0) 
                    ? 'uni_'.$table_name.'_'.Str::limit($column_name, 20, '')
                    : null; 

                $tables_data [$table_name]['columns'][]= $column_data;
                
            } // end foreach column names

            // TODO: find & add dynamic foreign key definitions
            //       (columns already exist)
            $tables_data = $this->findForeignKeys($table_name, $tables_data, $values, $data_array);

            // localizations FK column
            $tables_data [$table_name]['columns'][]= [
                'name' => 'localization_id',
                'type' => 'unsignedBigInteger',
                'size' => null,
                'unique' => null,
            ];
            // data_files FK column
            $tables_data [$table_name]['columns'][]= [
                'name' => 'data_file_id',
                'type' => 'unsignedBigInteger',
                'size' => null,
                'unique' => null,
            ];
            
            /*
             * add known foreign key definitions
             */
            // localizations FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.$table_name.'_localization_id',
                'column_name' => 'localization_id',
                'references' => 'id',
                'on' => 'localizations',
            ];
            // data_files FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.$table_name.'_file_id',
                'column_name' => 'data_file_id',
                'references' => 'id',
                'on' => 'data_files',
            ];
            
        } // end foreach data
        
        return $tables_data;
    } 
    
    public function createTables(array $tables_data){
        // TODO: loop to create table first, then loop for updating table to add foreign keys
        //       to ensure the referenced tables/columns exist

        // create the tables
        foreach($tables_data as $table_name => $table_data){
            $this->createTable($table_name, $table_data);
        }

        // add any foreign keys to the tables
        foreach($tables_data as $table_name => $table_data){
            $this->addForeignKeysToTable($table_name, $table_data);
        }
    }
    
    protected function createTable(string $table_name, array $table_data)
    {        
        dump("Creating table {$table_name}..."/*, $table_data['columns']*/);

        // drop table if exists to avoid foreign key collisions
        //       since we can't check for them
        Schema::dropIfExists($table_name);
        
        Schema::create($table_name, function (Blueprint $table) use ($table_name, $table_data) {
        
            $columns_data = $table_data['columns'];
        
            // remove spaces from keys
            $columns_data = $this->ensureValidColumnNames($columns_data);
                            
            // still auto inc primary key
            $table->id();
            
            foreach ( $columns_data as $index => $column_data ) {

                $column_type = $column_data['type'];
                $column_name = $column_data['name'];
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
    
    protected function ensureValidColumnNames(array $columns_data) : array
    {
        array_walk($columns_data, function(&$column_data){
            $column_data['name'] = Str::replace(' ', '_', $column_data['name']);
        });

        // todo: make sure values keys are also converted so that they match when compared
        
        return $columns_data;
    }
    
    protected function findColumnInfo(string $column_name, array $values) : array
    {
        // remove empty values
        $column_values = array_filter(array_column($values, $column_name));
        // pick a value to check
        $check_value = reset($column_values);
        $is_numeric = is_numeric($check_value);

        return $is_numeric ? $this->findNumericColumnInfo($check_value, $column_values) : $this->findTextColumnInfo($check_value, $column_values);
    }
    
    protected function findNumericColumnInfo($check_value, array $column_values) : array
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
    
        $has_decimal = str_contains($check_value, '.');
        
        // find the largest number by numeric value (not string value) 
        $max = max(array_map(function($val) use ($has_decimal) {
            return $has_decimal ? floatval($val) : intval($val);
        }, $column_values));
        
        $unsigned = empty(array_filter($column_values, function($val) use ($has_decimal) {
            // check for negative sign
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

    protected function findTextColumnInfo($check_value, array $column_values) : array
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

    /*
       $tables_data [$table_name]['foreign_keys'][] =[
            'name' => 'fk_'.$table_name.'_localization_id', // this table
            'column_name' => 'localization_id', // this table
            'references' => 'id', // related table
            'on' => 'localizations', // related table
        ];
     */
     protected function findForeignKeys(string $table_name, array $tables_data, array $values, array $data_array){
     
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
dump($table_name); 
        foreach ( $columns_data as $index => $column_data ) {
            $tables_data [$table_name]['foreign_keys'][] = $this->findForeignKey($table_name, $values, $data_array);
        }
die;        
        return [];
     }

    /**
     * @param string $table_name
     * @param array  $values
     * @param array  $data_array
     *
     * @return array
     */
     protected function findForeignKey(string $table_name, array $values, array $data_array) : array
     {        
        /*$table_name = $data['table']['name'];
        $column_names = $data['table']['columns'];
        $values = $data['values'];*/
       
        $foreign_keys = [];
        foreach($values as $column_name => $value_array){
            // search for matching value_array in other tables:

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

                        $found_array = [];
                        
                        foreach($other_values as $other_value_array){
                        
                            $found_key = array_search($value, $other_value_array);
                        
                            if(empty($found_key)){
                                $found_array []= null;
                                continue;
                            }
                        
                            $found_array [$other_table][]= [$found_key => $value];
                            
                            $foreign_keys []= [
                               'name' =>  'fk_'.$table_name.'_'.$found_key, // this table + related column
                               'column_name' => $key, //key($value), // this table
                               'references' => $found_key, // related column
                               'on' => $other_table, // related table
                               'value' => $value, 
                            ];
                        }
                        
                        return $found_array;
                        
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
        $fk_references = $foreign_key_data['references'];
        $fk_on = $foreign_key_data['on'];
        
        // drop if exists
        // sail user does not have select permissions on information_schema in docker
        /*if(Schema::hasColumn($table_name, $column_name) 
            && $this->hasForeignKey($table_name, $fk_name)){
            $table->dropForeign($fk_name);
        }*/
        // define constraint
        $table->foreign($column_name, $fk_name)->references($fk_references)->on($fk_on);
        // $table->foreign('localization_id', 'fk_'.Str::random(8).'_localize_id')->references('id')->on('localizations');
    }

    protected function createColumn($table, $column_name, $column_type, $column_size, $column_unique_name=null)
    {
        if(str_contains(strtolower($column_type), 'integer')) {
            // prevent int with size setting auto_increment=true
            $column = $table->$column_type($column_name)->nullable();
        }
        else{
            $column = $table->$column_type($column_name, $column_size)->nullable();
        }
        
        if(isset($column_unique_name)){
            // this column is the ID field for the JSON file
            $column->unique($column_unique_name);
        }
        
        return $table;
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
}