<?php
namespace app\components;

class DXConst extends \dix\base\component\DXConst
{
    const WEIGHT_NORMAL = 0;             //正常情况
    const WEIGHT_DELETED = -1;           //用户已删除该数据
    
    
    const ALIYUN_OSS_ACCESS_KEY_ID = "";
    const ALIYUN_OSS_ACCESS_KEY_SECRET = "";
    const ALIYUN_OSS_END_POINT = "oss-cn-shanghai.aliyuncs.com";                    
//    const ALIYUN_OSS_END_POINT = "oss-cn-shanghai-internal.aliyuncs.com";
    
    const ALIYUN_OSS_URL_IMG_PREFIX = "http://whis-static.oss-cn-shanghai.aliyuncs.com/img/";
    const ALIYUN_OSS_URL_FILE_PREFIX = "http://whis-static.oss-cn-shanghai.aliyuncs.com/file/";
    
    const QINIU_OSS_URL_PREFIX = "http://whisper.qiniudn.com/";
}


