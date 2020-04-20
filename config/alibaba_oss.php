<?php

return [
    'bucket' => env('OSS_BUCKET'),
    'accessKeyId' => env('OSS_ACCESS_KEY'),
    'accessKeySecret' => env('OSS_ACCESS_KEY_SECRET'),
    'endpoint' => 'http://' . env('OSS_END_POINT'),

    'is_duplicate_upload' => false, // 是否去重上传，默认：不要
    
    'table' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'table_name' => 'oss_url'
    ]
];
