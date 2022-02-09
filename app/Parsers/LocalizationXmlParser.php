<?php

namespace App\Parsers;

use Illuminate\Support\Facades\DB;

class LocalizationXmlParser implements ParserContract
{

    /**
     * @param string $filepath
     *
     * @return mixed
     */
    public function parseFile( string $filepath )
    {
        // TODO: determine language from file path and find it in localization_languages table to set id
        $language_id = 1;
        // TODO: determine type and use it to find localization_types id
        $type_id = 1;
        
        $xmlObj = simplexml_load_file($filepath);

        $upsert = [];
        
        foreach($xmlObj->string as $i => $string){
            $key = $string->attributes()['key'];
// $upsert = []; // TEMP
            $upsert []= [
                'id_key'=>$key->__toString(),
                'text'=>$string->__toString(), 
                'localization_type_id'=>$type_id, 
                'localization_language_id'=>$language_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
//$result = DB::table('localizations')->upsert($upsert, ['id_key']); // TEMP
        }
        $result = DB::table('localizations')->upsert($upsert, ['key']);
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