<?php

namespace ChrisComposer\Alibaba\Ding\Controllers;

use ChrisComposer\Alibaba\Ding\DingServer;
use ChrisComposer\Alibaba\Ding\Controllers\ComLoginDingController;
use Illuminate\Http\Request;

class LoginDingController extends ComLoginDingController
{
    protected $is_get_millisecond = false;
    
    /**
     * 根据扫码获取的 loginTmpCode 请求阿里云获取个人信息码
     * @param Request $request
     *
     * @return mixed
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'loginTmpCode' => 'required|string',
        ]);

        $response = DingServer::ding_login($request->loginTmpCode);

        return $response;
    }

    /**
     * 获取本系统 token
     * @param Request $request
     *
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_token(Request $request)
    {
        # validate
        $this->validate($request, [
            'code' => 'required|string',
            'state' => 'required|string|in:STATE',
        ]);

        # 获取用户签名
        $accessKey = config('alibaba_ding.login.appKey');
        $appSecret = config('alibaba_ding.login.appSecret');
        $timestamp = $this->is_get_millisecond ? $this->get_millisecond() : time() * 1000;
        $signature = DingServer::get_signature($timestamp, $appSecret);

        # 获取钉钉上的用户信息
        $response = DingServer::get_user_info_by_code(
            ['accessKey' => $accessKey, 'timestamp' => $timestamp, 'signature' => $signature],
            $request->code
        );

        # 执行登入
        ## 获取用户信息
        $config_user = config('alibaba_ding.user');
        $user = $config_user['table']::query()
            ->where($config_user['column'], $response['unionid'])->first();

        ## 检验是否为本系统成员
        if (! $user) {
            return response(['code' => 404, 'message' => $config_user['message_404']], 404);
        }

        # 颁发 token
        $token = auth('api')->login($user);

        # 记录登入日志
        LogLoginServer::record(['entrance' => 'ding', 'type' => 'in', 'error_code' => 0, 'message' => '登入成功']);

        # return
        return [
            'code' => 200,
            'data' => ['_token' => $token],
            'message' => '登入成功'
        ];
    }

    public function get_millisecond($type = 'string')
    {
        if ($type === 'string') {
            $time = explode (" ", microtime () );
            $time = $time [1] . ($time [0] * 1000);
            $time2 = explode ( ".", $time );
            $time = $time2 [0];
            return $time;
        }
        elseif ($type === 'float') {
            list($s1, $s2) = explode(' ', microtime());
            return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
        }
    }
}
