<?php

return [
    'user' => [
        'table' => \App\User::class,
    ],
    'access_key' => env('ALI_ACCESS_KEY'),
    'access_secret' => env('ALI_ACCESS_SECRET'),
    'sign_name' => env('ALIYUN_SMS_SIGN_NAME'), // 签名
    // 短信模板代码
    'template_code' => [
        'capture' => 'SMS_184115112' // 短信验证码
    ],
    'table' => [
        'log' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table_name' => 'log_sms'
        ]
    ]
];
