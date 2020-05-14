<?php

namespace smart;

class Db
{
    // 数据库连接实例
    private static $instance = [];

    /**
     * 数据库初始化，并取得数据库类实例
     * @param  mixed $config 连接配置
     * @param  bool  $name   连接标识 true强制重连接
     * @return object
     */
    public static function connect($config = [], $name = false)
    {
        if ($name === false) {
            $name = md5(serialize($config));
        }

        if ($name === true || !isset(self::$instance[$name])) {
            // 解析连接参数（支持数组和字符串）
            $options = self::parseConfig($config);

            if (empty($options['type'])) {
                throw new \Exception('[database][connect]未定义数据库类型');
            }

            $class = (strpos($options['type'], '\\') !== false) ? $options['type'] : '\\smart\\db\\driver\\' . ucwords($options['type']);
            if ($name === true) {
                $name = md5(serialize($config));
            }
            self::$instance[$name] = new $class($options);
        }
        return self::$instance[$name];
    }

    /**
     * 数据库连接参数解析
     * @param  mixed $config 连接参数
     * @return array
     */
    private static function parseConfig($config)
    {
        if (empty($config)) {
            $config = Config::has('pdo', 'database') ? (Config::get('pdo', 'database')) : Config::load('pdo', 'pdo', 'database');
        } elseif (is_string($config) && strpos($config, '/') === false) {
            $config = Config::has($config, 'database') ? (Config::get($config, 'database')) : Config::load($config, $config, 'database');
        }
        return is_string($config) ? (self::parseDsn($config)) : $config;
    }

    /**
     * DSN 解析
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @param  string $dsnStr 数据库 DSN 字符串解析
     * @return array
     */
    private static function parseDsn($dsnStr)
    {
        $info = parse_url($dsnStr);

        if (!$info) {
            return [];
        }

        $dsn = [
            'type' => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => !empty($info['path']) ? ltrim($info['path'], '/') : '',
            'charset'  => isset($info['fragment']) ? $info['fragment'] : 'utf8'
        ];
        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = [];
        }
        return $dsn;
    }

    /**
     * 调用驱动类的方法
     * @param  string $method 方法名
     * @param  array  $params 参数
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([self::connect(), $method], $params);
    }
}
