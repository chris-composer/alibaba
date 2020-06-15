<?php

namespace ChrisComposer\Alibaba\Sms\Controllers;

use ChrisComposer\Alibaba\Sms\Exceptions\LoginException;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Cache;

class ComLoginSmsController extends Controller
{
    use AuthenticatesUsers;

    protected $allow_input_phone_ttl = 5 * 60; // 允许手机号码输入有效时间
    protected $allow_input_phone_num = 5; // 允许手机号码输入次数
    protected $reject_input_phone_ttl = 5 * 60; // 不允许输入手机号码的时限

    protected $allow_input_code_ttl = 5 * 60; // 验证码错误次数持续时间
    protected $allow_input_code_num = 5; // 允许验证码输入次数
    protected $reject_input_code_ttl = 5 * 60; // 不允许输入验证码时限

    protected $login_sms_reject_phone_ttl = 5 * 60; // 禁止登录时间
    protected $login_sms_code_ttl = 30 * 60; // 验证码使用有效时间

    /**
     * 检查是否可以发送短信
     * @param $ip
     * @param $phone
     * @param $time
     */
    protected function can_send($ip, $phone, $time)
    {
        # 检查：该 ip 是否在禁止登录的名单中
        if (Cache::has("reject_login_ip_$ip")) {
            throw new LoginException("手机号错误超过{$this->allow_input_phone_num}次，禁止登录{$time}分钟", 400);
        }

        # 检查：该手机号是否存在
        $res = config('alibaba_sms.user.table')::where('phone', $phone)->first();

        if (! $res) {
            ## 若超过一定次数输入失败，限制 ip 登入几分钟
            $cache_key = "login_num_ip_$ip";

            ### 增加一次输入失败次数
            if (! Cache::has($cache_key)) {
                Cache::put($cache_key, 0, $this->allow_input_phone_ttl);
            }
            Cache::increment($cache_key);

            ### 若超过一定次数输入失败，限制 ip 登入几分钟
            if (Cache::get($cache_key) > $this->allow_input_phone_num) {
                Cache::put("reject_login_ip_$ip", true, $this->reject_input_phone_ttl);

                throw new LoginException("手机号错误超过{$this->allow_input_phone_num}次，禁止登录{$time}分钟", 400);
            }

            throw new LoginException('该手机号不存在，请重新输入', 404);
        }
    }

    /**
     * 获取真实 ip
     *
     * @return null|string
     */
    protected function getRealIp()
    {
        if (isset($_SERVER['HEADER_X_FORWARDED_FOR'])) {
            request()->setTrustedProxies(request()->getClientIps, \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR);
            $ip = request()->getClientIp();
        }
        else {
            $ip = request()->getClientIp();
        }

        return $ip;
    }

    /**
     * 登录日志
     */
    protected function login_log()
    {
//        LogLoginServer::record(['entrance' => 'sms', 'type' => 'in', 'error_code' => 0, 'message' => '登入成功']);
    }
}
