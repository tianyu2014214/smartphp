<?php

namespace smart;

use smart\exception\ClassNotFoundException;

class Session
{
    // 初始化SESSION
    protected static $init = null;

    // 作用域
    protected static $prefix = '';

    /**
     * 设置或者获取作用域
     * @param  string $prefix 作用域
     * @return mixed
     */
    public static function prefix($prefix = '')
    {
        empty(self::$init) && self::boot();
        if ($prefix === '') {
            // 获取作用域
            return self::$prefix;
        } else {
            // 设置作用域
            self::$prefix = $prefix;
        }
    }

    /**
     * 初始化SESSION
     * @param  array $config 配置信息
     * @return void
     */
    public static function init(array $config = [])
    {
        // 获取配置信息
        if (empty($config)) {
            $config = Config::get('session');
        }
        // 启动session
        $isDoStart = false;
        if (!empty($config['auto_start']) && PHP_SESSION_ACTIVE != session_status()) {
            ini_set('session.auto_start', 0);
            $isDoStart = true;
        }
        // 设置保存路径
        if (isset($config['path']) && !empty($config['path'])) {
            session_save_path($config['path']);
        }
        // 设置有效期
        if (isset($config['expire'])) {
            ini_set('session.gc_maxlifetime', $config['expire']);
            ini_set('session.cookie_lifetime', $config['expire']);
        }
        // COOKIE安全设置
        if (isset($config['secure'])) {
            ini_set('session.cookie_secure', $config['secure']);
        }
        // 仅支持HTTP
        if (isset($config['httponly'])) {
            ini_set('session.cookie_httponly', $config['httponly']);
        }
        // 选择驱动程序
        if (!empty($config['type'])) {
            // 读取并检测驱动程序
            $class = strpos($config['type'], '\\') !== false ? $config['type'] : '\\smart\\session\\driver\\' . ucwords($config['type']);
            if (!class_exists($class) || !session_set_save_handler(new $class($config))) {
                throw new ClassNotFoundException('error session handler:'.$class, $class);
            }
        }
        // 启动session
        if ($isDoStart) {
            session_start();
            self::$init = true;
        } else {
            self::$init = false;
        }
    }

    /**
     * SESSION自动启动或初始化
     * @return void
     */
    public static function boot()
    {
        if (is_null(self::$init)) {
            self::init();
        } elseif (self::$init === false) {
            if (session_status() != PHP_SESSION_ACTIVE) {
                session_start();
            }
            self::$init = true;
        }
    }

    /**
     * 设置SESSION
     * @param  string $name   SESSION名
     * @param  mixed  $value  SESSION值
     * @param  string $prefix 作用域
     * @return void
     */
    public static function set($name, $value = null, $prefix = '')
    {
        // 启动SESSION，设置作用域
        empty(self::$init) && self::boot();
        $prefix = $prefix ?: self::$prefix;
        // 设置SESSION
        if (strpos($name, '.') !== false) {
            // 二维数组赋值
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                $_SESSION[$prefix][$name1][$name2] = $value;
            } else {
                $_SESSION[$name1][$name2] = $value;
            }
        } elseif ($prefix) {
            // 一维数组赋值（有作用域）
            $_SESSION[$prefix][$name] = $value;
        } else {
            // 一维数组赋值（无作用域）
            $_SESSION[$name] = $value;
        }
    }

    /**
     * 添加数据到数组
     * @param  string  $name
     * @param  mixed   $value
     * @return void
     */
    public static function push($name, $value)
    {
        $array = self::get($name);
        if (is_null($array)) {
            $array = [];
        }
        $array[] = $value;
        self::set($name, $array);
    }

    /**
     * 删除SESSION
     * @param  mixed  $name
     * @param  string $prefix
     * @return void
     */
    public static function delete($name, $prefix = '')
    {
        // 启动SESSION，设置作用域
        empty(self::$init) && self::boot();
        $prefix = $prefix ?: self::$prefix;
        // 删除SESSION
        if (is_array($name)) {
            // 为数组表示批量删除
            foreach ($name as $key) {
                self::delete($key, $prefix);
            }
        } elseif (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                unset($_SESSION[$prefix][$name1][$name2]);
            } else {
                unset($_SESSION[$name1][$name2]);
            }
        } else {
            if ($prefix) {
                unset($_SESSION[$prefix][$name]);
            } else {
                unset($_SESSION[$name]);
            }
        }
    }

    /**
     * 销毁SESSION
     * @return void
     */
    public static function destroy()
    {
        // // 启动SESSION
        empty(self::$init) && self::boot();
        // 销毁SESSION
        if (!empty($_SESSION)) {
            $_SESSION = [];
        }
        session_unset();
        session_destroy();
        self::$init = null;
    }

    /**
     * 获取并删除SESSION
     * @param  string $name
     * @param  string $prefix
     * @return mixed
     */
    public static function pull($name, $prefix = '')
    {
        $result = self::get($name, $prefix);
        if ($result) {
            self::delete($name, $prefix);
            return $result;
        } else {
            return;
        }
    }

    /**
     * 获取SESSION
     *
     * @param  string $name   SESSION名
     * @param  string $prefix 作用域
     * @return mixed
     */
    public static function get($name = '', $prefix = '')
    {
        // 启动SESSION，设置作用域
        empty(self::$init) && self::boot();
        $prefix = $prefix ?: self::$prefix;
        // 获取SESSION
        if (empty($name)) {
            // 获取全部SESSION
            return $prefix ? (!empty($_SESSION[$prefix]) ? $_SESSION[$prefix] : []) : $_SESSION;
        } elseif ($prefix) {
            // 获取SESSION（有作用域）
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            // 获取SESSION（无作用域）
            if (strpos($name, '.') !== false) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    }

    /**
     * 检查SESSION是否存在
     * @param  string $name
     * @param  string $prefix
     * @return bool
     */
    public static function has($name, $prefix = '')
    {
        // 启动SESSION，设置作用域
        empty(self::$init) && self::boot();
        $prefix = $prefix ?: self::$prefix;
        // 检查SESSION
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
        } else {
            return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
        }
    }

    /**
     * 启动SESSION
     * @return void
     */
    public static function start()
    {
        @session_start();

        self::$init = true;
    }

    /**
     * 获取session id
     * @return string
     */
    public static function getSessionId()
    {
        // 启动SESSION
        empty(self::$init) && self::boot();

        // 返回SESSIONID
        return session_id();
    }
}
