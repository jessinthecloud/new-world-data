<?php

namespace App\Parsers;

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
        $values = [];
        $data_files = [];
        
        foreach ( $files as $file ) {
            $filepath = $file->getPathname();
            $filename = $file->getFilename();
            
            $data_files []= [
                'directory' => basename(dirname($filepath)),
                'filename' => $filename,
            ];
            
            dump("Parsing {$filename}...");
            $values = $this->parseFile($filepath);
            
            $values []= $values;
/*dd(
    'values',$values,
    'data_files',$data_files,
//    isset($values[0]) ? array_keys($values[0]) : [],
//'directories: ',collect($data_files)->pluck('directory')->all(),
//'filenames: ',collect($data_files)->pluck('filename')->all(),
);*/            
        } // end foreach file
        
        return ['data_files'=>$data_files, 'values'=>$values];
    }

    public function parseFiles(array $filepaths)
    {
        // TODO: Implement parseFiles() method.
    }
}