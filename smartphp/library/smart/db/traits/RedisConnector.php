<?php

namespace smart\db\traits;

trait RedisConnector
{
    // redis读连接
    protected $linkRead;

    // redis写连接
    protected $linkWrite;

    // redis连接集合
    protected $links  = [];

    // redis连接配置
    protected $config = [
        // 服务器地址
        'hostname'    => '',
        // 服务器端口
        'hostport'    => '',
        // 授权访问密码
        'password'    => '',
        // 默认数据库
        'database'    => 0,
        // 有效期
        'expire'      => 3600,
        // 超时时间
        'timeout'     => 0,
        // 是否长连接
        'persistent'  => false,
        // 部署方式：0-集中式，1-分布式
        'deploy'      => 0,
        // 是否读写分离
        'rw_separate' => 0,
        // 主服务器数量（读写分离有效）
        'master_num'  => 1,
        // 指定从服务器序号
        'slave_no'    => ''
    ];

    /**
     * 连接数据库方法
     * @param  array   $config         配置信息
     * @param  integer $linkNo         连接序号
     * @param  boolean $autoConnection 从库连接失败是否自动连接主库
     * @return Redis
     */
    public function connect(array $config = [], $linkNo = 0, $autoConnection = false)
    {
        // 检查连接是否存在
        if (!isset($this->links[$linkNo])) {
            // 连接不存在
            $config = $config ? array_merge($this->config, $config) : $this->config;
            try {
                // redis连接
                $this->links[$linkNo] = new \Redis();
                $config['persistent'] ? $this->links[$linkNo]->pconnect($config['hostname'], $config['hostport'], $config['timeout']) : $this->links[$linkNo]->connect($config['hostname'], $config['hostport'], $config['timeout']);
                if (!empty($config['password'])) {
                    $this->links[$linkNo]->auth($config['password']);
                }
            } catch (\Exception $e) {
                // 检查是否开启自动重连
                if ($autoConnection) {
                    return $this->connect($autoConnection, $linkNo);
                } else {
                    throw $e;
                }   
            }
        }
        return $this->links[$linkNo];
    }

    /**
     * 初始化数据库连接
     * @param  boolean $master 是否主服务器
     * @return void
     */
    protected function initConnect($master = true)
    {
        if ($this->config['deploy'] === 1) {
            // 采用分布式数据库
            if ($master) {
                // 主服务期
                if (!$this->linkWrite) {
                    $this->linkWrite = $this->multiConnect(true);
                }
                $this->linkID = $this->linkWrite;
            } else {
                // 从服务器
                if (!$this->linkRead) {
                    $this->linkRead = $this->multiConnect(false);
                }
                $this->linkID = $this->linkRead;
            }
        } else {
            // 默认单数据库
            $this->linkID = $this->connect();
        }
        return $this->linkID;
    }

    /**
     * 连接分布式服务器
     * @param  boolean $master 主服务器
     * @return Redis
     */
    protected function multiConnect($master = false)
    {
        // 分布式数据库配置解析
        $config = [];
        foreach (['hostname', 'hostport', 'password'] as $name) {
            $config[$name] = explode(',', $this->config[$name]);
        }

        // 生成主服务器序号
        $m = floor(mt_rand(0, $this->config['master_num'] - 1));

        // 检查是否读写分离
        if ($this->config['rw_separate']) {
            // 采用读写分离
            if ($master) {
                // 主服务器写
                $s = $m;
            } elseif (is_numeric($this->config['slave_no'])) {
                // 指定从服务器读
                $s = $this->config['slave_no'];
            } else {
                // 随机选取从服务器读
                $s = floor(mt_rand($this->config['master_num'], count($config['hostname']) - 1));
            }
        } else {
            // 禁用读写分离
            $s = floor(mt_rand(0, count($config['hostname']) - 1));
        }

        // 生成主数据库连接配置
        $mlinkConfig = false;
        if ($m != $s) {
            $mlinkConfig = [];
            foreach (['hostname', 'hostport', 'password'] as $name) {
                $linkConfig[$name] = isset($config[$name][$m]) ? $config[$name][$m] : $config[$name][0];
            }
        }

        // 生成本次数据库连接配置
        foreach (['hostname', 'hostport', 'password'] as $name) {
            $linkConfig[$name] = isset($config[$name][$s]) ? $config[$name][$s] : $config[$name][0];
        }
        return $this->connect($linkConfig, $s, $mlinkConfig);
    }
}