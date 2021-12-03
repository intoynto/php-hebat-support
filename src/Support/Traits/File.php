<?php

namespace Intoy\HebatSupport\Traits;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Stream as StreamFactory;

trait File {
    static function getAllowDocumentExtension():Array
    {
        return [
            "txt",
            "pdf",
            "xls",
            "xlsx",
            "doc",
            "docx",
            "bmp",
            "jpg",
            "jpeg",
            "png",
            "rar",
            "zip",
        ];
    }

    static function getAllowImageExtension():Array 
    {
        return [           
            "bmp",
            "jpg",
            "jpeg",
            "png",           
            "webp",           
        ];
    }

    static function sanitize_filename(string $filename, $platform='Unix')
    {
        if (in_array(strtolower($platform), array('unix', 'linux'))) 
        {
			// our list of "dangerous characters", add/remove characters if necessary
  			$dangerous_characters = array(" ", '"', "'", "&", "/", "\\", "?", "#");
  		}
  	  	else {
			// no OS matched? return the original filename then...
  	  	  	return $filename;
  	  	}
  	
		// every forbidden character is replace by an underscore
		return str_replace($dangerous_characters, '_', $filename);
    }


    static function isUploadedFile($request, string $postName)
    {
        if($request instanceof ServerRequestInterface){
            $uploadedFiles=$request->getUploadedFiles();
        }
        elseif(is_array($request)) {
            $uploadedFiles=$request;
        }

        if(isset($uploadedFiles[$postName])){
            $uploadFile=$uploadedFiles[$postName];
            return $uploadFile->getError()===UPLOAD_ERR_OK;
        }
        return false;
    }

    static function moveUploadedFiles($params, string $post_name, string $folder, array $allowExtension=[]):string
    {
        if($params instanceof ServerRequestInterface){
            $uploadedFiles=$params->getUploadedFiles();
        }
        elseif(is_array($params))
        {
            $uploadedFiles=$params;
        }
        else {
            throw new Exception('Invalid params. Use params instance of ServerRequestInface or UploadedFileInstarface.');
        }

        if(!isset($uploadedFiles[$post_name]))
        {
            throw new Exception('\''.$post_name.'\' not exists in params as ServerRequestInterface|UploadedFileInterface');
        }

        if(!is_dir($folder)){
            throw new Exception("Direktory untuk upload file '".$folder."' tidak ditemukan.");
            exit;
        } 

        if(!isset($allowExtension) || !is_array($allowExtension) || count($allowExtension)<1)
        {
            $allowExtension=static::getAllowDocumentExtension();
        }

        $upload=$uploadedFiles[$post_name];
        $extension=pathinfo($upload->getClientFilename(),PATHINFO_EXTENSION);
        $extension=strtolower($extension);

        if(!in_array($extension,$allowExtension)){
            throw new Exception("Tidak dibenarkan mengupload file berextensi (".$extension."). File yang diperbolehkan hanya : ".implode(", ",$allowExtension).".");
            exit;
        }

        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $upload->moveTo($folder . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }

    static function removeFile(string $directory, string $filename=""):bool
    {
        $filename=trim($filename);
        $fullfilename=$directory.$filename;
        if(strlen($filename)>0 && file_exists($fullfilename)){
            unlink($fullfilename);
            return true;
        }
        return false;
    }
}