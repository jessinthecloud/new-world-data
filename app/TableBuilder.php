<?php

namespace App;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\ForeignKeyDefinition;
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

    public function createTableInfo(array $data_array)
    {
        $tables_data = [];
        
        foreach($data_array as $index => $data){
            $dir = $data['dir'];
            $file = $data['file'];
            $table_name = $data['table']['name'];
            $column_names = $data['table']['columns'];
            $values = $data['values'];
            
//            dump($table_name, $column_names);
            
        // CREATE TABLES DEFINITIONS 
            $tables_data [$table_name]['columns']=[];
            $tables_data [$table_name]['foreign_keys']=[];

            foreach($column_names as $column_name) {
//dump('NAME: '.$column_name, array_column($values, $column_name));

            //-- find column info; type, size
                $column_data = $this->findColumnInfo($column_name, $values);
                $column_data['name'] = $column_name;
                // first key is the unique index (probably...)
                $column_data['unique'] = array_key_first($column_names) == $column_name 
                    ? Str::random(8).'_uni'
                    : null; 
//dump($column_data);

                $tables_data [$table_name]['columns'][]= $column_data;

                // TODO: find & add dynamic foreign key column definitions
                // TODO: find & add dynamic foreign key definitions

            } // end foreach column names
            
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
             * 
             * named with random strings because using
             * table+col name makes them too long
             */
            // localizations FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.Str::random(8).'_localization_id',
                'column_name' => 'localization_id',
                'references' => 'id',
                'on' => 'localizations',
            ];
            // data_files FK
            $tables_data [$table_name]['foreign_keys'][] =[
                'name' => 'fk_'.Str::random(8).'_file_id',
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
    
    /* EXAMPLE: 
     * $tables_data [$table_name]['columns'][]= [
        'name' => 'data_file_id',
        'type' => 'unsignedBigInteger',
        'size' => null,
    ];
    $tables_data [$table_name]['foreign_keys'][] =[
        'name' => 'fk_'.Str::random(8).'_file_id',
        'column_name' => 'data_file_id',
        'references' => 'id',
        'on' => 'data_files',
    ];
     */
    protected function createTable(string $table_name, array $table_data)
    {
        dump("Creating table {$table_name}...");
        
        Schema::create($table_name, function (Blueprint $table) use ($table_name, $table_data) {
        
            $columns_data = $table_data['columns'];
        
            // remove spaces from keys
            $columns_data = $this->ensureValidColumnNames($columns_data);
            
            // todo: make sure column names are not dupes?
                            
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
                $column = $table->$column_type($column_name, $column_size)->nullable(); 

                if(isset($column_unique_name)){
                    // this column is the ID field for the JSON file
                    $column->unique($column_unique_name);
                }
            } // end foreach column
            
            // created/updated
            $table->timestamps();
        });
        dump("{$table_name} table created.");
    }
    
    protected function ensureValidColumnNames(array $columns_data) : array
    {
    dump($columns_data);
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
                'size'=>1,
            ];
        }
    
        $has_decimal = str_contains($check_value, '.');
        
        // find the largest number by numeric value (not string value) 
        $max = max(array_map(function($val) use ($has_decimal) {
            return $has_decimal ? floatval($val) : intval($val);
        }, $column_values));
//dump('MAX: '.$max);
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
        
        return [
            'type'=>$type,
            // # of digits 
            // not including sign, commas, or decimal points
            'size'=>strlen(str_replace(['-','.',','], '', $max)),
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
//dump('MAX LENGTH: '.$max);        
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
            /* example: 
                $tables_data [$table_name]['foreign_keys'][] =[
                    'name' => 'fk_'.Str::random(8).'_file_id',
                    'column_name' => 'data_file_id',
                    'references' => 'id',
                    'on' => 'data_files',
                ];
             */
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

        // define constraint
        $table->foreign($column_name, $fk_name)->references($fk_references)->on($fk_on);
        // $table->foreign('localization_id', 'fk_'.Str::random(8).'_localize_id')->references('id')->on('localizations');
    }
}