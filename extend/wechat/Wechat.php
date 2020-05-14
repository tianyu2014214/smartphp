<?php

namespace wechat;

use wechat\service\RequestService;
use wechat\service\HighLevelService;

class Wechat
{
    // 实例对象
    private static $instance;

    /**
     * 构造函数
     */
    public function __construct(array $conf = [])
    {
        // 定义微信公众号SDK路径
        if (!defined('MP_WEIXIN_PATH')) {
            define('MP_WEIXIN_PATH', __DIR__ . '/');
        }

        // 加载配置文件
        $this->config = array_merge(include MP_WEIXIN_PATH . 'data/config.php', $conf);
    }

    /**
     * 微信请求处理
     */
    public static function processRequest(array $conf = [])
    {
        // 实例化本处理器
        if (is_null(self::$instance)) {
            self::$instance = new self($conf);
        }

        // 调用微信请求处理服务
        $service = new RequestService(self::$instance->config);
        if (!isset($_GET['echostr'])) {
            $service->responseMsg();
        } else {
            $service->valid();
        }
    }

    /**
     * 记载高级接口
     * @return object
     */
    public static function loadHighLevelService(array $conf = [])
    {
        // 实例化本处理器
        if (is_null(self::$instance)) {
            self::$instance = new self($conf);
        }

        // 返回高级接口服务对象
        return new HighLevelService(self::$instance->config);
    }
}
