<?php

namespace wechat\service;

/**
 * 微信高级接口调用类
 */
class HighLevelService
{
    // 开发者ID
    private $appId;

    // 开发者密码
    private $appSecret;

    /**
     * 构造函数
     * @param array $config 配置信息
     */
    public function __construct($config)
    {
        $this->appId     = $config['app_id'];
        $this->appSecret = $config['app_secret'];
    }

    /**
     * 获取微信用户信息
     * @param  string $openid 微信用户编号
     * @return void
     */
    public function getUserInfo($openid)
    {
        // 生成请求地址
        $host  = 'https://api.weixin.qq.com/cgi-bin/user/info';
        $param = [
            'access_token' => $this->getAccessToken(),
            'openid'       => $openid,
            'lang'         => 'zh_CN'
        ];
        $url = url('/', $param, $host);

        // 获取用户信息
        $content = json_decode(curl($url));
        if (isset($content->errcode)) {
            return;
        }
        return $content;
    }

    /**
     * 获取访问令牌
     * @return string
     */
    private function getAccessToken()
    {
        // 判断access_token是否已存在
        $result = db('redis')
                ->select(RDB_DEFAULT)
                ->execute('get', 'weixin_access_token');
        if ($result) {
            return $result;
        }

        // 组装请求地址
        $host  = 'https://api.weixin.qq.com/cgi-bin/token';
        $param = [
            'grant_type' => 'client_credential',
            'appid'      => $this->appId,
            'secret'     => $this->appSecret
        ];
        $url = url('/', $param, $host);

        // 重新获取access_token
        $content = json_decode(curl($url));
        if (isset($content->errcode)) {
            return;
        }
        $result  = db('redis')
                 ->select(RDB_DEFAULT)
                 ->execute('setex', 'weixin_access_token', $content->expires_in, $content->access_token);
        
        // 返回access_token
        return $content->access_token;
    }
}
