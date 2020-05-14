<?php

namespace wechat\service;

use wechat\library\WxbizMsgCrypt;

/**
 * 微信请求服务处理类
 */
class RequestService
{
    // 开发者ID
    private $appId;

    // 开发者密码
    private $appSecret;

    // 令牌
    private $token;

    // 消息加解密密钥
    private $aesKey;

    /**
     * 构造函数
     */
    public function __construct($config)
    {
        $this->appId     = $config['app_id'];
        $this->appSecret = $config['app_secret'];
        $this->aesKey    = $config['aes_key'];
        $this->token     = $config['token'];
    }

    /**
     * 响应信息到微信客户端
     * @return void
     */
    public function responseMsg()
    {
        // 接收微信服务器返回的信息
        $timestamp    = isset($_GET['timestamp']) ? $_GET['timestamp'] : time();
        $nonce        = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        $msgSignature = isset($_GET['msg_signature']) ? $_GET['msg_signature'] : '';
        $encryptType  = isset($_GET['encrypt_type']) ? $_GET['encrypt_type'] : 'raw';
        $postStr      = file_get_contents("php://input");

        // 响应消息
        if (!empty($postStr)) {
            // 安全模式
            if ($encryptType == 'aes') {
                
                $pc       = new WxbizMsgCrypt($this->token, $this->aesKey, $this->appId);
                $errCode  = $pc->decryptMsg($msgSignature, $timestamp, $nonce, $postStr, $decryptMsg);
                if ($errCode !== 0) {
                    echo $errCode;
                    exit;
                }
                $postStr  = $decryptMsg;
            }
            // 明文模式
            else {
                $signature = isset($_GET['signature']) ? $_GET['signature'] : '';
                $timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : '';
                $nonce     = isset($_GET['nonce']) ? $_GET['nonce'] : '';
                $tmpArr    = array($this->token, $timestamp, $nonce);
                sort($tmpArr, SORT_STRING);
                $tmpStr    = sha1(implode($tmpArr));
                
                if (empty($signature) || $tmpStr != $signature) {
                    echo 40035;
                    exit;
                }
            }
            $postObj  = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $cryptObj = $encryptType == 'aes' ? $pc : false;
            $handler = 'wechat\handler\\' . ucfirst($postObj->MsgType) . 'Handler';
            $handler = new $handler();
            $result  = call_user_func_array([$handler, 'handleRequest'], [$postObj, $cryptObj]);
            echo empty($result) ? 'success' : $result;
        }
    }

    /**
     * 服务器接入有效性验证（首次交互时调用）
     * @return void
     */
    public function valid()
    {
        $echoStr   = isset($_GET['echostr']) ? $_GET['echostr'] : '';
        $signature = isset($_GET['signature']) ? $_GET['signature'] : '';
        $timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : '';
        $nonce     = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        $tmpArr    = array($this->token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr    = sha1(implode($tmpArr));
        if (!empty($signature) && $tmpStr == $signature) {
            echo $echoStr;
            exit;
        }
    }
}
