<?php

namespace wechat\handler;

use wechat\message\TextMessage;

class TextHandler
{
    /**
     * 响应数据
     * @param  object $postObj  接收的数据
     * @param  mixed  $cryptObj 加解密对象
     * @return string
     */
    public function handleRequest($postObj, $cryptObj)
    {
        $keyword = trim($postObj->Content);
        if (strstr($keyword, '测试')) {
            $content = TextMessage::generateContent('这是测试消息', $postObj, $cryptObj);
        }

        // 默认回复消息
        else {
            $content = TextMessage::generateContent('这是默认回复消息', $postObj, $cryptObj);
        }
        return $content;
    }
}
