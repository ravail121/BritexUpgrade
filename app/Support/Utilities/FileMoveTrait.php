<?php

namespace App\Support\Utilities;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

trait FileMoveTrait
{
    public function moveOneFile($fileLocation, $file)
    {
        $name = str_random(30).".{$file->getClientOriginalExtension()}";
        $path = $fileLocation.DIRECTORY_SEPARATOR.$name;

        try {
            if(!File::isDirectory($fileLocation)) File::makeDirectory($fileLocation, 0755, true);
            $file->move($fileLocation, $name);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return false;
        }

        return collect(compact('name', 'path') + [
            'size' => File::size($path),
            'hash' => md5_file($path)
        ]);
    }

    public function moveMultipleFiles($fileLocation, $files)
    {
        $filesUploaded = [];

        foreach ($files as $k => $file){
            $filesUploaded[$k] = self::moveOneFile($fileLocation, $file);
        }

        return collect($filesUploaded);

    }
}