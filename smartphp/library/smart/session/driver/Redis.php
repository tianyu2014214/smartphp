<?php

namespace smart\session\driver;

use smart\Config;
use smart\Exception;

Class Redis extends \SessionHandler
{
    // redis操作句柄
    protected $handler = null;

    // 配置文件
    protected $config  = [
        // redis主机
        'host'          => '127.0.0.1',
        // redis端口
        'port'          => 6379,
        // redis密码
        'password'      => '',
        // 默认存储库
        'select'        => 0,
        // 有效期（秒）
        'expire'        => 3600,
        // 超时时间
        'timeout'       => 0,
        // 是否长连接
        'persistent'    => true,
        // SESSIN KEY前缀
        'session_name'  => 'sess_'
    ];

    /**
     * 构造函数
     * @param  array $config 配置文件
     */
    public function __construct(array $config = [])
    {
        // 获取配置信息
        $this->config = array_merge($this->config, $config);

        $this->config = array_merge($this->config, \smart\Config::get('', 'session'));
    }

    /**
     * 打开SESSION
     * @param  string $savePath    存储路径
     * @param  string $sessionName SESSION名称
     * @return bool
     */
    public function open($savePath, $sessName)
    {
        // 检测当前环境是否支持redis
        if (!extension_loaded('redis')) {
            throw new \Exception('current php environment no support redis');
        }
        // 建立连接
        $this->handler = new \Redis;
        $func = $this->config['persistent'] ? 'pconnect' : 'connect';
        $this->handler->$func($this->config['host'], $this->config['port'], $this->config['timeout']);
        // 授权登录
        if ($this->config['password'] != '') {
            $this->handler->auth($this->config['password']);
        }
        // 选择数据库
        if ($this->config['select'] != 0) {
            $this->handler->select($this->config['select']);
        }
        return true;
    }

    /**
     * 关闭session
     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler->close();
        $this->handler = null;
        return true;
    }

    /**
     * 读取Session
     * @param string $sessID
     * @return string
     */
    public function read($sessID)
    {
        return (string) $this->handler->get($this->config['session_name'] . $sessID);
    }

    /**
     * 写入Session
     * @param string $sessID
     * @param string $sessData
     * @return bool
     */
    public function write($sessID, $sessData)
    {
        if ($this->config['expire'] > 0) {
            return (bool) $this->handler->setex($this->config['session_name'] . $sessID, $this->config['expire'], $sessData);
        } else {
            return (bool) $this->handler->set($this->config['session_name'] . $sessID, $sessData);
        }
    }

    /**
     * 删除Session
     * @param string $sessID
     * @return bool
     */
    public function destroy($sessID)
    {
        return $this->handler->delete($this->config['session_name'] . $sessID) > 0;
    }

    /**
     * Session 垃圾回收
     * @param string $sessMaxLifeTime
     * @return bool
     */
    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
