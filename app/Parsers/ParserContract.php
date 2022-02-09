<?php

namespace App\Parsers;

interface ParserContract
{
    public function parseFile(string $filepath);
    public function parseDir(string $dir);
    public function parseFiles(array $filepaths);
}