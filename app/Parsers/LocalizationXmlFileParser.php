<?php

namespace App\Parsers;

use App\Models\LocalizationLanguage;
use App\Models\LocalizationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalizationXmlFileParser implements FileParserContract
{
    public function parseFilepath( string $filepath ) : array
    {
        $pieces = explode(DIRECTORY_SEPARATOR, $filepath);
        $filename = array_pop($pieces);
        $language = array_pop($pieces);
        $type = Str::before(Str::after($filename, '_'), '.');
        
        return [$language, $type];
    }
    
    /**
     * Read XML and insert into tables 
     * 
     * @param string $filepath
     *
     * @return mixed
     */
    public function parseFile( string $filepath )
    {        
        $xmlObj = simplexml_load_file($filepath);
        
        // determine related info
        [$language_code, $type] = $this->parseFilepath($filepath);
        $language_id = LocalizationLanguage::where('code', 'like', $language_code)->first()->id;
        $file_id = DB::table('localization_files')->where('filename', Str::afterLast($filepath, DIRECTORY_SEPARATOR))->first()->id;
        
        $values = [];
        
        foreach($xmlObj->string as $i => $string){
            $key = $string->attributes()['key'];
            
            $values []= [
                'id_key'=>$key->__toString(),
                'text'=>$string->__toString(), 
                'field_type'=>Str::afterLast($key->__toString(), '_'),
                'language_id'=>$language_id,
                'file_id'=>$file_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return $values;
    }

    /**
     * @param string $dir
     *
     * @return mixed
     */
    public function parseDir( string $dir )
    {
        // TODO: Implement parseDir() method.
    }

    /**
     * @param array $filepaths
     *
     * @return 
     */
    public function parseFiles( array $filepaths )
    {
        foreach($filepaths as $filepath){
            $this->parseFile($filepath);
        }
    }
}