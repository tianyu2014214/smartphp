<?php

namespace wechat\message;

class ImageMessage
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
        // 生成图片类型消息模版
        $imageTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[image]]></MsgType>
                        <Image><MediaId><![CDATA[%s]]></MediaId></Image>
					 </xml>";
		$result = sprintf($imageTpl, $postObj->FromUserName, $postObj->ToUserName, time(), $content['MediaId']);
        
        // 对数据进行加密
        if (is_object($cryptObj)) {
            $errCode = $cryptObj->encryptMsg($result, $encryptMsg);
            $result  = $encryptMsg;
        }
        return $result;
    }
}
