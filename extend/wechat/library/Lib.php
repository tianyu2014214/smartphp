<?php

namespace wechat\library;

class Lib
{
    /**
     * 记录日志信息
     * @param  string $content 日志内容
     * @return void
     */
    public static function log($content)
    {
        $file = MP_WEIXIN_PATH . 'data/weixinlog';
        
		file_put_contents($file, $content);
    }
}
