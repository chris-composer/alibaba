<?php

return [
    'user' => [
        'table' => \App\User::class,
        'column' => 'ding_unionid',
        'message_404' => '您不是本系统的成员', 
    ],
    'login' => [
        'appKey' => 'dingoaahhgnlirom2dojsc',
        'appSecret' => 's05PnUJqFYiIgPKcGWhUm-Yv_HIv4ZtEuvZ8Kkm105thAVPChPkxHph-_QDi4rEZ',
        'state' => 'STATE',
        'redirect_uri' => 'http://106.12.12.17:999/api/login/ding/token/'
    ],

    'inner_app' => [
        'appKey' => 'dingybvctji2hkj7z6yz',
        'appSecret' => 'zmCF_CPv2FwKKbjECf7BrYoeyKVFLk5SADb6HvviAzHCWR8H2WNMDUjFDWjQ0Hzx',
    ],
];
