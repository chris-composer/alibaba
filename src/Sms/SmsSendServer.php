<?php

namespace ChrisComposer\Alibaba\Sms;

use Illuminate\Support\Facades\Cache;

class SmsSendServer
{
    public static function send_capture($phone, $extra, $cache_key_name = '', $expires = 30)
    {
        # 生成验证码
        $code = randStr(6, 'NUMBER');

        # 实例化发送实例
        $alibaba_sms = new SmsServer();

        # 短信模板 id
        $template_code = config('sms.ali.template_code.capture');
        # 模板参数
        $template_param = [
            'code' => $code,
//            'expires' => $expires
        ];

        # 执行发送
        $res = $alibaba_sms->send_sms_code($phone, $template_code, $template_param, $extra);

        # 发送成功，验证码存入缓存待验证
        if ($res === true && $cache_key_name) {
            Cache::put($cache_key_name, $code, $expires * 60);
        }

        return $res;
    }
}
