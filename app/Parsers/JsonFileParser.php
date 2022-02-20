<?php

namespace App\Parsers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class JsonFileParser implements FileParserContract
{
    public function getFilename( string $filepath ) : string
    {
        $pieces = explode(DIRECTORY_SEPARATOR, $filepath);
        return array_pop($pieces);
    }
    
    public function parseFile(string $filepath)
    {
        return json_decode(file_get_contents($filepath), true);
    }

    public function parseDir(string $dir)
    {
        dump("Parsing ".basename($dir)."/...");
        
        // get all files from dir and subdirectories
        $files = File::allFiles($dir);
        
        if(empty(array_filter($files))){
            // skip empty dirs
            return [];
        }
        
        $data_files = [];
        $combo = [];
        $columns = [];
        $tables = [];
        
        foreach ( $files as $file ) {
            $filepath = $file->getPathname();
            $filename = $file->getFilename();
            $dir = basename(dirname($filepath));
            $table_name = basename($filename, '.json');
            
            $data_files []= [
                'directory' => $dir,
                'filename' => $filename,
            ];
            
            dump("Parsing {$filename}...");
            $values = array_filter($this->parseFile($filepath));
            
            if(empty($values)){
                // skip empty files
                continue;
            }

            foreach($values as $index => $value_array){
                // combine with existing columns so that files with
                // extra/unusual columns don't get left out
                $columns = array_unique(array_merge($columns, array_keys($value_array)));
            }
           
            $tables [$table_name]= $columns;

            $combo []= [
                'dir' => $dir,
                'file' => $filename,
                'table' => [
                    'name' => $table_name, 
                    'columns'=>$columns
                ],
                'values' => $values,
            ];

        } // end foreach file
      
        return [
            'data_files'=>$data_files,
            'combo'=> $combo,
            'tables'=> $tables,
        ];
    }

    public function parseFiles(array $filepaths)
    {
        // TODO: Implement parseFiles() method.
    }
}