<?php

namespace App\Parsers;

interface FileParserContract
{
    public function parseFile(string $filepath);
    public function parseDir(string $dir);
    public function parseFiles(array $filepaths);
}