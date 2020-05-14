<?php

namespace wechat\message;

class TextMessage
{
    /**
     * 生成回复消息（xml类型）
     * @param  string $content  回复内容
     * @param  object $postObj  接收数据对象
     * @param  object $cryptObj 加解密对象
     * @return string XML数据
     */
    public static function generateContent($content, $postObj, $cryptObj)
    {
        // 生成文字类型模版
        $xmlTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                   </xml>";
        $result = sprintf($xmlTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $content);

        // 对数据进行加密
        if (is_object($cryptObj)) {
            $errCode = $cryptObj->encryptMsg($result, $encryptMsg);
            $result  = $encryptMsg;
        }
        return $result;
    }
}