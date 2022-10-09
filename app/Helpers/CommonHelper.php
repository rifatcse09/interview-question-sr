<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Exception;

class CommonHelper
{

    public static function fileUpload($fileName, $file, $path)
    {
        try {
            $location = null;
            if (!empty($file)) {

                $mainDir = storage_path("app/public/" . $path);

                if (!file_exists($mainDir)) {
                    mkdir($mainDir, 0777, true);
                }
                Storage::putFileAs("public/" . $path, $file, $fileName);

                $location = $path . $fileName;
            }
            return $location;
        } catch (Exception $ex) {
            return response(['error' => "Image upload Error"], 404);
        }
    }

    public static function createVariant($productVariant)
    {
        $variantData = [];
        foreach ($productVariant as $variants) {
            foreach ($variants['tags'] as $tag) {
                $data['variant_id'] = $variants['option'];
                $data['variant'] = $tag;
                $variantData[] = $data;
            }
        }
        return  $variantData;
    }
}
