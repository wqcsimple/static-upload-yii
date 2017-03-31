<?php

return [
    'params' => [
        'redis-param' => [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
            'password' => null,
            'read_write_timeout' => 0,
            'database' => 0,
        ],
        
        'qiniu-params' => [
            'access_key' => "xqupAU168xYM7YaYKmbeK1T83jQTZGYSm_ZKFwBa",
            'secret_key' => "5w4R06-MMu7V2wsMoyjO8fXMHQjDlFXnVDmAqhQt",
            "file_url_prefix" => "http://whisper.qiniudn.com/",
        ]
    ]

];
