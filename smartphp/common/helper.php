<?php

use smart\Config;
use smart\Curl;
use smart\Db;
use smart\Factory;
use smart\Log;
use smart\Request;
use smart\Response;
use smart\Session;
use smart\Url;
use smart\Validate;
use smart\crypt\Aes;

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param  mixed  $name
     * @param  mixed  $value
     * @param  string $range
     * @return mixed
     */
    function config($name, $value = null, $range = '')
    {
        if (is_array($name) || !is_null($value)) {
            // 设置配置信息
            return Config::set($name, $value, $range);
        } else {
            // 获取配置信息 或 检查配置是否存在
            if (strpos($name, '?') === 0) {
                // 检查配置是否存在
                return Config::has(substr($name, 1), $range);
            } elseif (strpos($name, '@') === 0) {
                // 读取指定作用域配置
                return Config::get(null, substr($name, 1));
            } else {
                // 获取配置信息
                return Config::get($name, $range);
            }
        }
    }
}

if (!function_exists('curl')) {
    /**
     * 执行CURL操作
     * @param  string $url    请求地址
     * @param  mixed  $data   待发送数据
     * @param  bool   $post   是否使用POST
     * @param  bool   $cookie 是否启用Cookie
     * @return mixed
     */
    function curl($url, $data = null, $post = false, $cookie = false)
    {
        return Curl::init($cookie)->send($url, $data, $post);
    }
}

if (!function_exists('db')) {
    /**
     * 实例化数据库类
     * @param  mixed  $config 数据库配置
     * @param  bool   $force  是否强制重连接
     * @return object
     */
    function db($config = 'pdo', $force = false)
    {
        $force = $force && is_string($force) ? "{$config}_{$force}" : $force;
        return Db::connect($config, $force);
    }
}

if (!function_exists('model')) {
    /**
     * 实例化模型对象
     * @param  string $name   模型名
     * @param  string $layer  业务层名称
     * @param  string $module 模块名称
     * @return object
     */
    function model($name, $layer = 'model', $module = '')
    {
        return Factory::M($name, $layer, $module);
    }
}

if (!function_exists('trace')) {
    /**
     * 记录日志信息
     * @param  string $data   日志内容
     * @param  string $level  日志级别
     * @param  string $module 日志模块
     * @return mixed
     */
    function trace($data = null, $level = 'info', $module = 'default')
    {
        $trace = Log::instance();

        return is_null($data) ? $trace : $trace->write($level, $data, $module);
    }
}

if (!function_exists('request')) {
    /**
     * 获取当前Request对象实例
     * @param  array  $options 实例化参数
     * @return object
     */
    function request(array $options = [])
    {
        return Request::instance($options);
    }
}

if (!function_exists('input')) {
    /**
     * 获取输入数据（支持默认值和过滤）
     * @param  string $key     变量名
     * @param  mixed  $default 默认值
     * @param  string $filter  筛选条件
     * @return mixed
     */
    function input($key, $default = '', $filter = '')
    {
        // 判断变量是否存在
        if (strpos($key, '?') === 0) {
            $key = substr($key, 1);
            return request()->has($key);
        }
        // 获取数据
        return request()->input($key, $default, $filter);
    }
}

if (!function_exists('response')) {
    /**
     * 响应数据到客户端
     * @param  mixed   $data 响应数据
     * @param  integer $code HTTP状态码
     * @param  string  $type 响应方式（默认json）
     * @return void
     */
    function response($data = null, $code = 200)
    {
        if (is_null($data)) {
            // 返回实例对象
            return Response::instance();
        } else {
            // 响应数据
            return Response::json($data, $code);
        }
    }
}

if (!function_exists('session')) {
    /**
     * Session管理
     * @param  string $name  Session名
     * @param  mixed  $value Session值
     * @return mixed
     */
    function session($name = '', $value = '')
    {
        if (empty($value)) {
            if (is_array($name)) {
                // 批量设置
                return Session::set($name);
            } elseif (strpos($name, '?') !== false) {
                // 判断session是否存在
                return Session::has(substr($name, 1));
            } elseif (strpos($name, '#') !== false) {
                return Session::delete(substr($name, 1));
            } else {
                // 获取session
                return Session::get($name);
            }
        } else {
            // 单个设置
            return Session::set($name, $value);
        }
    }
}

if (!function_exists('url')) {
    /**
     * 实例化URL对象或快速生成URL
     * @param  string $route  路由
     * @param  mixed  $params 参数
     * @param  string $host   主机
     * @param  bool   $encode 转义
     * @return mixed
     */
    function url($route = null, $params = '', $host = '', $encode = false)
    {
        if (is_null($route)) {
            // 返回对象实例
            return Url::instance();
        } else {
            // 生成URL
            return Url::buildUrl($route, $params, $host, $encode);
        }
    }
}

if (!function_exists('validate')) {
    /**
     * 实例化验证器或单字段校验
     * @param  mixed $rule    校验规则
     * @param  mixed $message 提示信息
     * @param  bool  $sington 是否单例模式
     * @return mixed
     */
    function validate($rule, $message, $sington = true)
    {
        if (is_array($rule)) {
            // 返回校验对象
            return Validate::make($rule, $message, $sington);
        } else {
            // 返回校验结果
            $rule = ['validate' => $rule];
            $data = ['validate' => $message];
            return Validate::make($rule)->check($data);
        }
    }
}

if (!function_exists('aes_crypt')) {
    /**
     * AES算法加密和解密
     * @param  string  $data    明文或密文
     * @param  boolean $encrypt 是否加密
     * @param  string  $key     加密KEY
     * @param  string  $iv      加密IV
     * @return void
     */
    function aes_crypt(string $data, bool $encrypt, $key = null, $iv = null)
    {
        $key = $key ?: config('aes_key');
        $iv  = $iv ?: config('aes_iv');
        $aes = \smart\crypt\Aes::init();
        return $encrypt ? $aes->encrypt($data, $key, $iv) : $aes->decrypt($data, $key, $iv);
    }
}
