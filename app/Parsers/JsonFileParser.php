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
        dump("Parsing {$dir}...");
        
        // get all files from dir and subdirectories
        $files = File::allFiles($dir);
        $data_files = [];
        $combo = [];
        $columns = [];
        
        foreach ( $files as $file ) {
            $filepath = $file->getPathname();
            $filename = $file->getFilename();
            $dir = basename(dirname($filepath));
            
            $data_files []= [
                'directory' => $dir,
                'filename' => $filename,
            ];
            
            dump("Parsing {$filename}...");
            $values = $this->parseFile($filepath);
            
            // array keys are database columns to create the table with
            $columns = array_unique(Arr::flatten(array_merge($columns, array_map(function($value_array){
                return array_keys($value_array);
            }, $values))));

            $combo []= [
                'dir' => $dir,
                'file' => $filename,
                'values' => $values,
            ];


        } // end foreach file
        
        return [
            'data_files'=>$data_files,
            'combo'=> $combo,
            'columns'=> $columns,
        ];
    }

    public function parseFiles(array $filepaths)
    {
        // TODO: Implement parseFiles() method.
    }
}