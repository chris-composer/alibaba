<?php

namespace ChrisComposer\Alibaba\Ding;

use ChrisComposer\Alibaba\Ding\Exceptions\ComException;
use ChrisComposer\GuzzleHttp\GuzzleHttpServer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DingServer
{
    /**
     * 根据 timestamp, appSecret 计算签名值
     * @param $timestamp 毫秒级时间戳
     * @param $appSecret 阿里云的 app_secret
     *
     * @return string
     */
    public static function get_signature($timestamp, $appSecret)
    {
        $s = hash_hmac('sha256', $timestamp, $appSecret, true);
        $signature = base64_encode($s);
        $urlencode_signature = urlencode($signature);

        return $urlencode_signature;
    }

    /**
     * 通用请求方法
     * @param       $uri
     * @param       $method
     * @param array $post_data
     *
     * @return mixed
     */
    public static function request_common($uri, $method, $post_data = [])
    {
        $client = new Client(['timeout' => 10.0]);

        try {
            $response = $client->request($method, $uri, $post_data);

            $data = json_decode((string)$response->getBody(), true);
            if ($data['errcode'] === 0) {
                return $data;
            }
            else {
                throw new ComException($data['errmsg'], $data['errcode']);
            }
        } catch (RequestException $e) {
            throw new ComException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 钉钉登入：扫码获取 loginTmpCode 后登入
     * @param $loginTmpCode
     *
     * @return mixed|string
     */
    public static function ding_login($loginTmpCode)
    {
        # 获取配置
        $config = config('alibaba_ding.login');
        
        $uri = "https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid={$config['appKey']}&response_type=code&scope=snsapi_login&state={$config['state']}&redirect_uri={$config['redirect_uri']}&loginTmpCode={$loginTmpCode}";

        $data = GuzzleHttpServer::request_http_get($uri, ['verify' => true]);

        return $data;
    }

    /**
     * 创建 post_data
     * @param        $body_content params 的 body 参数
     * @param array  $params_other params 的其他参数
     * @param string $body_type body 类型
     *
     * @return array
     */
    public static function create_post_data($body_content, $params_other = [], $body_type = 'json')
    {
        if ($body_type === 'body') {
            $post_data = [
                'body' => json_encode($body_content, JSON_UNESCAPED_UNICODE)
            ];
        }
        elseif($body_type === 'json') {
            $post_data = [
                'json' => $body_content
            ];
        }

        if ($params_other) {
            $post_data = array_merge($post_data, $params_other);
        }

        return $post_data;
    }

    /**
     * 通过扫码登录获取用户信息
     * @param $auth_params
     * @param $code
     *
     * @return mixed
     */
    public static function get_user_info_by_code($auth_params, $code)
    {
        $post_data = self::create_post_data(['tmp_auth_code' => $code]);

        $data = self::request_common(
            "https://oapi.dingtalk.com/sns/getuserinfo_bycode?accessKey={$auth_params['accessKey']}&timestamp={$auth_params['timestamp']}&signature={$auth_params['signature']}",
            'post',
            $post_data
        );

        return $data['user_info'];
    }

    /**
     * 获取 access_token
     * @return mixed
     */
    public static function get_access_token()
    {
        $accessKey = config('ding.inner_app.appKey');
        $appSecret = config('ding.inner_app.appSecret');

        $uri = "https://oapi.dingtalk.com/gettoken?appkey={$accessKey}&appsecret={$appSecret}";

        $data = self::request_common(
            $uri,
            'get',
            ['verify' => false]
        );

        return $data['access_token'];
    }

    /**
     * 获取用户详情
     * @param $access_token
     * @param $userid
     *
     * @return mixed
     */
    public static function get_user_info($access_token, $userid)
    {
        $data = self::request_common(
            "https://oapi.dingtalk.com/user/get?access_token={$access_token}&userid={$userid}",
            'get',
            ['verify' => false]
        );

        return $data;
    }

    /**
     * 获取在职员工
     * @param $access_token
     * @param $params
     *
     * @return mixed
     */
    public static function get_user_on_job($access_token, $params)
    {
        $post_data = self::create_post_data($params, ['verify' => false]);

        $data = self::request_common(
            "https://oapi.dingtalk.com/topapi/smartwork/hrm/employee/queryonjob?access_token={$access_token}",
            'post',
            $post_data
        );

        return $data['result'];
    }

    /**
     * 获取员工列表
     * @param $access_token
     * @param $params
     *
     * @return mixed
     */
    public static function get_employee_list($access_token, $params)
    {
        $post_data = self::create_post_data($params, ['verify' => false]);

        $data = self::request_common(
            "https://oapi.dingtalk.com/topapi/smartwork/hrm/employee/list?access_token={$access_token}",
            'post',
            $post_data
        );

        return $data['result'];
    }
}