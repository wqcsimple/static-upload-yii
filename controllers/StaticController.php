<?php

namespace app\controllers;

use app\components\DXUtil;
use dix\base\controller\BaseController;
use dix\base\exception\ServiceErrorSaveException;
use dix\base\exception\ServiceErrorWrongParamException;
use Upload\File;
use Upload\Storage\FileSystem;

class StaticController extends BaseController
{
    public function beforeAction($action)
    {
        $ok = parent::beforeAction($action);

        DXUtil::cors(['localhost', '*', 'static.upload'], true);
        
        return $ok;
    }
    
    protected function makeFileName($path, $name)
    {
        $ext = '';
        if (strrpos($name, '.') !== false)
        {
            $ext = strtolower(substr($name, strrpos($name, '.')));
        }

//        return hash_file('sha256', $path). $ext;
        return hash_file('sha256', $path) . $ext;
    }

    public function actionImgUpload()
    {
        $filename = $this->uploadFile('disc01', 'img', ['image/png', 'image/jpg', 'image/jpeg']);

        $this->finishSuccess([
            'file' => [
                'name' => $filename
            ]
        ]);
    }

    public function actionFileUpload()
    {
        $filename = $this->uploadFile('disc01', 'file');

        $this->finishSuccess([
            'file' => [
                'name' => $filename
            ]
        ]);
    }
    
    private function uploadFile($bucket_name, $path_prefix = 'file', $mime_types = [])
    {
        $key = 'file';
        
        list($code, $error) = DXUtil::validateUploadFile($key, $mime_types, 1024 * 1024 * 20);
        if ($code !== 0)
        {
            throw new ServiceErrorWrongParamException('invalid file');
        }

        $path = $_FILES[$key]['tmp_name'];
        $name = $_FILES[$key]['name'];
        $mime_type = DXUtil::getFileMimeType($path);
        $filename = $this->makeFileName($path, $name);
      
        $storage = new FileSystem('static/' . $path_prefix, true);
        $file = new File('file', $storage);
        
        $file->setName($name);
        
        try
        {
            $file->upload($filename);
        }
        catch (\Exception $e)
        {
            $error = 'an exception occurred while uploading file: ' . $e->getMessage();
            throw new ServiceErrorSaveException(['error' => $error]);
        }
        
        return $filename;
    }
    
}