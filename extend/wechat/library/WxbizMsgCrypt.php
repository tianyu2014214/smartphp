<?php

namespace wechat\library;

use wechat\library\crypt\Prpcrypt;
use wechat\library\crypt\Sha1;
use wechat\library\crypt\XmlParse;
use wechat\library\crypt\ErrorCode;

/**
 * 微信消息加解密类
 */
class WxbizMsgCrypt
{
    private $token;
    private $aesKey;
    private $appId;

    /**
     * 构造函数
     */
    public function __construct($token, $aesKey, $appId)
    {
        $this->token  = $token;
        $this->aesKey = $aesKey;
        $this->appId  = $appId;
    }

    /**
     * 消息加密接口
     * @param  string $replyMsg    待回复的消息
     * @param  string &$encryptMsg 加密后的密文
     * @return void
     */
    public function encryptMsg($replyMsg, &$encryptMsg)
    {
        // 加密操作
        $pc        = new Prpcrypt($this->aesKey);
        $array     = $pc->encrypt($replyMsg, $this->appId);
        $timestamp = time();
        $nonce     = mt_rand(1000000000, 9999999999);
        if ($array[0] != 0) {
            return $array[0];
        }
        $encrypt = $array[1];

        // 生成安全签名
        $sha1    = new Sha1();
        $array   = $sha1->getSHA1($this->token, $timestamp, $nonce, $encrypt);
        if ($array[0] != 0) {
            return $array[0];
        }
        $signature = $array[1];

        // 生成发送的xml
        $xmlparse = new XmlParse();
        $encryptMsg = $xmlparse->generate($encrypt, $signature, $timestamp, $nonce);
        return ErrorCode::$OK;
    }

    /**
     * 检查消息真实性，并获取解密后的明文
     * @param  string $msgSignature 签名串
     * @param  string $timestamp    时间戳
     * @param  string $nonce        随机串
     * @param  string $postData     密文
     * @param  string &$msg         明文
     * @return void
     */
    public function decryptMsg($msgSignature, $timestamp, $nonce, $postData, &$msg)
    {
        if (strlen($this->aesKey) != 43) {
            return ErrorCode::$IllegalAesKey;
        }   

        $pc = new Prpcrypt($this->aesKey);

        // 提取密文
        $xmlparse = new XmlParse();
        $array    = $xmlparse->extract($postData);
        if ($array[0] != 0) {
            return $array[0];
        }
        if ($timestamp == null) {
            $timestamp = time();
        }

        $encrypt    = $array[1];
        $touserName = $array[2];

        // 验证安全名称
        $sha1  = new Sha1();
        $array = $sha1->getSHA1($this->token, $timestamp, $nonce, $encrypt);
        if ($array[0] != 0) {
            return $array[0];
        }

        $signature = $array[1];
        if ($signature != $msgSignature) {
            return ErrorCode::$ValidateSignatureError;
        }

        $result = $pc->decrypt($encrypt, $this->appId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $msg = $result[1];

        return ErrorCode::$OK;
    }
}