<?php

namespace App;

class SchemaBuilder
{
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
        $table_data = [];
        foreach($data_array as $index => $data){
            $dir = $data['dir'];
            $file = $data['file'];
            $table_name = $data['table']['name'];
            $column_names = $data['table']['columns'];
            $values = $data['values'];
            
            dump($table_name, $column_names);
            
        // CREATE TABLES DEFINITIONS 
            $table_data [$table_name]=[];

            foreach($column_names as $column_name) {
dump('NAME: '.$column_name, array_column($values, $column_name));
            //-- find column type per key (based on value)
                $column_data = $this->findColumnInfo($column_name, $values);
                // column type (method name)
                $column_type = $column_data['type'];
                // max column size
                $column_size = $column_data['size'];
dump($column_data);
                // TODO: add known foreign key definitions
                
                // TODO: find dynamic foreign key definitions
                
                // TODO: loop for create table first, then loop for updating table to add foreign keys
                //       to ensure the referenced tables/columns exist
                 
            } // end foreach column names
        }
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
dump('MAX: '.$max);
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
dump('MAX LENGTH: '.$max);        
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
}