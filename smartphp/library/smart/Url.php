<?php

namespace smart;

class Url
{
    // 实例对象
    private static $instance;

    /**
     * 获取对象实例
     * @return object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 生成访问URL
     * @param  string $route  路由
     * @param  mixed  $params 参数
     * @param  string $host   主机
     * @param  bool   $encode 转义
     * @return string
     */
    public static function buildUrl($route, $params, string $host = '', bool $encode = false)
    {
        // 参数params为数组类型
        if (is_array($params)) {
            // 解析参数
            ksort($params);
            foreach ($params as $key => $value) {
                array_push($params, "{$key}={$value}");
                unset($params[$key]);
            }
            // 组装参数
            $params = implode('&', $params);
        } elseif (!is_string($params)) {
            // 异常参数类型
            throw new \Exception('[buildUrl]参数{param}仅支持数组和字符串类型');
        }
        // 组装URL
        $host   = rtrim($host ?: Config::get('app_host'), '/');
        $route  = rtrim($route, '/');
        $params = ltrim($params, '?');
        $params = $params ? '?'.$params : '';
        $url    = rtrim($host . $route . $params, '/');
        // 返回结果
        return $encode ? urlencode($url) : $url;
    }

    /**
     * 分析查询参数
     * @param  string $params
     * @return array
     */
    public static function parseQueryParams(string $params)
    {
        $params = explode('&', $params);
        foreach ($params as $index => $param) {
            unset($params[$index]);
            list($key, $value) = @explode('=', $param, 2);
            $params[$key] = $value;
        }
        return $params;
    }
}