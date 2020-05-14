<?php

namespace smart;

class Config
{
    // 配置参数
    private static $config = [];

    // 参数作用域
    private static $range = '_sys_';

    /**
     * 设置配置参数的作用域
     * @param  string $range 作用域
     * @return void
     */
    public static function range($range)
    {
        self::$range = $range;

        if (!isset(self::$config[$range])) {
            self::$config[$range] = [];
        }
    }

    /**
     * 加载配置文件（PHP数组格式）
     * @param  string $file  配置文件名
     * @param  string $name  配置名（若设置即表示二级配置）
     * @param  string $range 作用域
     * @return mixed
     */
    public static function load($file, $name = '', $range = '')
    {
        // 重构作用域
        $range = $range ?: (is_file($file) ? (self::$range) : str_replace('/', '.', $file));
        
        // 加载配置文件
        $file = is_file($file) ? $file : CONFIG_PATH . $file . EXT;
        if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
            // 设置作用域
            self::$config[$range] = isset(self::$config[$range]) ? (self::$config[$range]) : [];
            // 加载并返回配置文件
            return self::set(include $file, $name, $range);
        }
        // 默认返回
        return [];
    }

    /**
     * 设置配置参数（name为数组则表示批量设置）
     * @param  string|array $name  配置参数名（支持二级配置 . 号分割）
     * @param  mixed        $value 配置值或子配置名
     * @param  string       $range 作用域
     * @return mixed
     */ 
    public static function set($name, $value = null, $range = '')
    {
        // 设置作用域
        $range = $range ?: self::$range;
        if (!isset(self::$config[$range])) {
            self::$config[$range] = [];
        }
        // 字符串表示单个设置
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                // 一级配置
                self::$config[$range][strtolower($name)] = $value;
            } else {
                // 二级配置
                $name = explode('.', $name, 2);
                self::$config[$range][strtolower($name[0])][$name[1]] = $value;
            }
            return $value;
        }
        // 数组表示批量设置
        if (is_array($name)) {
            if (!empty($value)) {
                self::$config[$range][$value] = isset(self::$config[$range][$value]) ? array_merge(self::$config[$range][$value], $name) : $name;
                return self::$config[$range][$value];
            }

            return self::$config[$range] = array_merge(self::$config[$range], array_change_key_case($name));
        }
        // 其他情况直接返回已有配置
        return self::$config[$range];
    }

    /**
     * 重置配置参数
     * @param  string $range 作用域
     * @return void
     */
    public static function reset($range = '')
    {
        // 设置待重置的作用域
        $range = $range ?: self::$range;
        // 重置配置
        if ($range === true) {
            // range 为 true 时表示重置整个配置
            self::$config = [];
        } else {
            // 重置指定作用域的配置
            self::$config[$range] = [];
        }
    }

    /**
     * 获取配置参数（为空表示获取所有配置）
     * @param  string $name  配置名（支持二级配置 . 号分割）
     * @param  string $range 作用域
     * @return mixed
     */
    public static function get($name = null, $range = '')
    {
        // 无参数时获取所有
        $range = $range ?: self::$range;
        if (empty($name)) {
            if (!isset(self::$config[$range])) {
                // 作用域不存在，进行尝试加载
                $file = CONFIG_PATH . str_replace('.', '/', $range) . EXT;
                is_file($file) && self::load($file, '', $range);
            }
            return self::$config[$range];
        }
        // 作用域不存在时尝试动态载入配置
        if (!isset(self::$config[$range])) {
            $file = CONFIG_PATH . str_replace('.', '/', $range) . EXT;
            is_file($file) && self::load($file, '', $range);
        }
        // 一级配置
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            return isset(self::$config[$range][$name]) ? self::$config[$range][$name] : null;
        }
        // 二级配置
        $name    = explode('.', $name, 2);
        $name[0] = strtolower($name[0]);
        return isset(self::$config[$range][$name[0]][$name[1]]) ? self::$config[$range][$name[0]][$name[1]] : null;
    }

    /**
     * 检测配置是否存在
     * @param  string $name  配置名（支持二级配置 . 号分割）
     * @param  string $range 作用域
     * @return boolean
     */
    public static function has($name, $range = '')
    {
        // 设置作用域
        $range = $range ?: self::$range;
        // 一级配置检测
        if (!strpos($name, '.')) {
            return isset(self::$config[$range][strtolower($name)]);
        }
        // 二级配置检测
        $name = explode('.', $name, 2);
        $name[0] = strtolower($name[0]);
        return isset(self::$config[$range][$name[0]][$name[1]]);
    }
}
