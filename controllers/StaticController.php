<?php

namespace app\controllers;

use app\components\DXConst;
use app\components\DXUtil;
use app\components\QiniuOssApi;
use dix\base\controller\BaseController;
use dix\base\exception\ServiceErrorSaveException;
use dix\base\exception\ServiceErrorWrongParamException;
use OSS\OssClient;
use Upload\File;
use Upload\Storage\FileSystem;


class StaticController extends BaseController
{
    const DEFAULT_UPLOAD_METHOD = "oss";
    
    public function beforeAction($action)
    {
        $ok = parent::beforeAction($action);

        DXUtil::cors(['localhost', '*', 'static.upload'], true);
        
        return $ok;
    }
    
    private function getUploadMethod($type)
    {
        $list = [
            'oss' => [
                'func' => self::className() . "::uploadFileOfOss",
                'bucket' => 'whis-static',
                'img_url' => DXConst::ALIYUN_OSS_URL_IMG_PREFIX,
                'file_url' => DXConst::ALIYUN_OSS_URL_FILE_PREFIX
            ],
            
            'qiniu' => [
                'func' => self::className() . "::uploadFileOfQiniu",
                'bucket' => 'simplelife',
                'img_url' => DXConst::QINIU_OSS_URL_PREFIX,
                'file_url' => DXConst::QINIU_OSS_URL_PREFIX
            ],
            
            'local' => [
                'func' => self::className() . "::uploadFileOfLocal",
                'bucket' => 'static',
                'img_url' => "http://" . $_SERVER['HTTP_HOST'] . "/static/img/",
                'file_url' => "http://" . $_SERVER['HTTP_HOST'] . "/static/file/"
            ]
        ];
        
        return $list[$type];
    }

    public function actionImgUpload()
    {
        $path_prefix = "img";
        $mime_types = ['image/png', 'image/jpg', 'image/jpeg'];

        $method = $this->getUploadMethod(self::DEFAULT_UPLOAD_METHOD);
        
        $file_url = $method['img_url'];
        $bucket = $method['bucket'];
        $func = $method['func'];
        
        $filename = call_user_func_array($func, [$bucket, $path_prefix, $mime_types]);

        $this->finishSuccess([
            'file' => [
                'name' => $filename,
                'url' => $file_url . $filename
            ]
        ]);
    }

    public function actionFileUpload()
    {
        $path_prefix = "file";
        $method = $this->getUploadMethod(self::DEFAULT_UPLOAD_METHOD);

        $file_url = $method['file_url'];
        $bucket = $method['bucket'];
        $func = $method['func'];

        $filename = call_user_func_array($func, [$bucket, $path_prefix]);

        $this->finishSuccess([
            'file' => [
                'name' => $filename,
                'url' => $file_url . $filename
            ]
        ]);
    }

    // qi niu
    public static function uploadFileOfQiniu($bucket_name, $path_prefix = "file", $mime_types = [])
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
        $filename = self::makeFileName($path, $name);
        
        $qiniu_params = DXUtil::param('qiniu-params');
        $qiniu_oss_api = new QiniuOssApi($qiniu_params['access_key'], $qiniu_params['secret_key'], $qiniu_params['file_url_prefix']);
        list($response, $error) = $qiniu_oss_api->putFile($bucket_name, $path, $filename);
        if ($error !== null) {
            dump($error);
            die;
        }
        
        return $filename;
    }
    
    // Ali Oss
    public static function uploadFileOfOss($bucket, $path_prefix = 'file', $mime_types = [])
    {
        $key = 'file';
        list($code, $error) = DXUtil::validateUploadFile($key, $mime_types, 1024 * 1024 * 50);
        if ($code !== 0)
        {
            throw new ServiceErrorWrongParamException('invalid file: ' . $error . ' ' . DXUtil::jsonEncode($_FILES));
        }

        $path = $_FILES[$key]['tmp_name'];
        $name = $_FILES[$key]['name'];
        $mime_type = DXUtil::getFileMimeType($path);
        $filename = self::makeFileName($path, $name);

        try
        {
            $object = "$path_prefix/$filename";
            $client = new OssClient(DXConst::ALIYUN_OSS_ACCESS_KEY_ID, DXConst::ALIYUN_OSS_ACCESS_KEY_SECRET, DXConst::ALIYUN_OSS_END_POINT);
            $options = [
                OssClient::OSS_CONTENT_DISPOSTION => $name
            ];
            if ($mime_type != null)
            {
                $options[OssClient::OSS_CONTENT_TYPE] = $mime_type;
            }
            $client->uploadFile($bucket, $object, $path, $options);
        }
        catch (\Exception $e)
        {
            $error = 'an exception occurred while uploading file: ' . $e->getMessage();
            throw new ServiceErrorSaveException(['error' => $error]);
        }
        
        return $filename;
    }
    
    // 上传到本地
    public static function uploadFileOfLocal($bucket_name, $path_prefix = 'file', $mime_types = [])
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
        $filename = self::makeFileName($path, $name);
      
        $storage = new FileSystem("$bucket_name/$path_prefix", true);
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

    public static function makeFileName($path, $name)
    {
        $ext = '';
        if (strrpos($name, '.') !== false)
        {
            $ext = strtolower(substr($name, strrpos($name, '.')));
        }

        return hash_file('sha256', $path) . $ext;
    }
}