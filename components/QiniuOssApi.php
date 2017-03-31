<?php
/**
 * Created by PhpStorm.
 * User: whis
 * Date: 2/17/17
 * Time: 10:38 AM
 * 安装sdk composer require qiniu/php-sdk
 */

namespace app\components;

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class QiniuOssApi
{
    public $access_key;
    public $secret_key;
    public $file_url_prefix;

    protected $auth_manager;
    protected $upload_manager;

    const BUCKET_SIMPLE_LIFE = "simplelife";

    const APP_BUCKET_MAPPING = [
        'simplelife' => self::BUCKET_SIMPLE_LIFE
    ];

    public function __construct($access_key, $secret_key, $file_url_prefix)
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->file_url_prefix = $file_url_prefix;
    }

    /**
     * @return Auth  Qiniu\Auth
     */
    public function auth()
    {
        return new Auth($this->access_key, $this->secret_key);
    }

    public function getAuthManager()
    {
        return $this->auth_manager ?: $this->auth_manager = $this->auth();
    }

    public function getUploadManager() {
        return $this->upload_manager ?: $this->upload_manager = new UploadManager();
    }
    
    public function putFile($bucket_name, $path, $filename) {
        $upload_token = $this->getAuthManager()->uploadToken($bucket_name);
        return $this->getUploadManager()->putFile($upload_token, $filename, $path);
    }
    
    public static function getBucket($app)
    {
        $app_list = self::APP_BUCKET_MAPPING;
        if (in_array($app, array_keys($app_list))) {
            return $app_list[$app];
        }

        return null;
    }

    public static function getBucketManager()
    {
        $auth = self::prepare();
        $bucket_manager = new BucketManager($auth);
        return $bucket_manager;
    }

    /**
     * 获取文件信息
     * @param $bucket_name
     * @param $key
     * @return mixed
     */
    public static function getObjectInfo($bucket_name, $key)
    {
        $bucket_manager = self::getBucketManager();
        list($result, $err) = $bucket_manager->stat($bucket_name, $key);
        if ($err != null) {
            dump($err);
            die;
        } else {
            return $result;
        }
    }

    /**
     * @param $bucket_name
     * @param $prefix // 要列取文件的公共前缀
     * @param $marker
     * @param $limit
     */
    public static function getObjectListByBucket($bucket_name, $prefix, $marker, $limit)
    {
        $bucket_manager = self::getBucketManager();

        list($file_list, $marker, $err) = $bucket_manager->listFiles($bucket_name, $prefix, $marker, $limit);
        if ($err != null) {
            dump($err);
            die;
        } else {
            return $file_list;
        }
    }
}
