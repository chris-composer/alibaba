<?php

namespace ChrisComposer\Alibaba\Sms;

use Illuminate\Support\Facades\Cache;

class SmsSendServer
{
    public static function send_capture($phone, $extra, $cache_key_name = '', $expires = 30)
    {
        # 生成验证码
        $code = self::randStr(6, 'NUMBER');

        # 实例化发送实例
        $server = new SmsServer();

        # 短信模板 code
        $template_code = config('alibaba_sms.template_code.capture');
        # 模板参数
        $template_param = [
            'code' => $code,
//            'expires' => $expires
        ];

        # 执行发送
        $res = $server->send_sms_code($phone, $template_code, $template_param, $extra);

        # 发送成功，验证码存入缓存待验证
        if ($res === true && $cache_key_name) {
            Cache::put($cache_key_name, $code, $expires * 60);
        }

        return $res;
    }

    protected static function randStr($len = 6, $format = 'ALL')
    {
        switch ($format) {
            case 'ALL':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
                break;
            case 'CHAR':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~';
                break;
            case 'NUMBER':
                $chars = '0123456789';
                break;
            default :
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
                break;
        }

        mt_srand((double)microtime() * 1000000 * getmypid());
        $password = "";

        while (strlen($password) < $len)
            $password .= substr($chars, (mt_rand() % strlen($chars)), 1);

        return $password;
    }
}
