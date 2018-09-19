<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/6/16
 * Time: 下午2:19
 */

namespace App\Http\Controllers\v1;
use Illuminate\Support\Facades\File;


class ImageResController extends Controller
{
    public function getImage($pName ,$fileName){
        $file = app()->basePath()."/public/img/{$pName}/{$fileName}";
        $fileType = "jpg";
        $file_path = $file.".".$fileType;
        if(file_exists($file_path)){
            return response(File::get($file_path))->header('Content-Type', 'image/' . $fileType);
        }
        $fileType = "gif";
        $file_path = $file.".".$fileType;
        if(file_exists($file_path)){
            return response(File::get($file_path))->header('Content-Type', 'image/' . $fileType);
        }
        $fileType = "png";
        $file_path = $file.".".$fileType;
        if(file_exists($file_path)){
            return response(File::get($file_path))->header('Content-Type', 'image/' . $fileType);
        }
        return null;
    }
}