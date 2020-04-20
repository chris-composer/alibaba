<?php

namespace ChrisComposer\Alibaba\Sms\Controllers;

use ChrisComposer\Alibaba\Sms\Exceptions\LoginException;
use ChrisComposer\Alibaba\Sms\SmsSendServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LoginSmsController extends ComLoginSmsController
{
    /**
     * 发送短信验证码
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sms_send(Request $request)
    {
        # validate
        $this->validate($request, [
            'phone' => 'required|digits:11|exists:users,phone'
        ]);

        # define variate
        $ip = $this->getRealIp(); // 真实 ip
        $phone = $request['phone']; // 输入的 手机号
        $time = $this->reject_input_phone_ttl / 60; // 不允许输入手机号码的时限

        # 检查是否可以发送短信
        $this->can_send($ip, $phone, $time);

        # 发送短信验证码
        $res = SmsSendServer::send_capture($phone, ['type' => 'login'], "login_sms:code_$phone");

        if ($res === true) {
            return $this->response->array(['code' => 200, 'message' => '验证码已发送，请及时查收']);
        }
        else {
            return $this->response->array(['code' => 500, 'message' => $res]);
        }
    }

    /**
     * 短信登录
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function sms_login(Request $request)
    {
        # validate
        $this->validate($request, [
            'phone' => 'required|digits:11|exists:users,phone',
            'code' => 'required|digits:6',
        ]);

        # define variate
        $phone = $request['phone'];
        $code = $request['code'];
        $time = $this->reject_input_code_ttl / 60;

        # 检查：该手机号码是否在禁止登录的名单中
        if (Cache::has("login_sms:reject_phone_$phone")) {
            throw new LoginException("验证码错误超过{$this->allow_input_code_num}次，禁止登录{$time}分钟", 400);
        }

        # 检查是否已获取验证码
        if (! Cache::has("login_sms:code_$phone")) {
            throw new LoginException('请先获取短信验证码', 400);
        }

        # 对比验证码
        // 验证码不正确
        if (Cache::get("login_sms:code_$phone") !== $code) {
            ## 增加次数
            $key = "login_sms:code_error_num_$phone";
            if (! Cache::has($key)) {
                Cache::put($key, 0, $this->allow_input_code_ttl);
            }

            Cache::increment($key);
            ## 若验证码错误到达一定次数，禁止该手机号登录
            if (Cache::get($key) > $this->allow_input_code_num) {
                Cache::put("login_sms:reject_phone_$phone", true, $this->reject_input_code_ttl);

                throw new LoginException("验证码错误超过{$this->allow_input_code_num}次，禁止登录{$time}分钟", 400);
            }

            throw new LoginException('验证码错误', 400);
        }

        # 获取 token
        $user = config('alibaba_sms.user.table')::query()->where('phone', $phone)->first();
        $token = auth('api')->tokenById($user->id);

        # 删除有效验证码
        Cache::forget("login_sms:code_$phone");

        # 记录登入日志
//        $this->login_log();

        return response([
            'code' => 200,
            'message' => '登入成功',
            'data' => ['_token' => $token],
        ]);
    }
}
