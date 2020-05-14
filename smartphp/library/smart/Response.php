<?php

namespace smart;

use smart\exception\HttpException;

class Response
{
    // 实例对象
    private static $instance;

    // HTTP状态码含义
    protected static $httpStatus = [
        '200' => 'OK',
        '204' => 'No Content',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '500' => 'Server Internal Error',
        '601' => 'Redirect',
        '602' => 'Form Redirect',
        '603' => 'Route Redirect',
    ];

    /**
     * 对象初始化
     * @param  array  $options 参数
     * @return Request
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 响应JSON数据到客户端
     * @param  mixed   $data      待响应数据
     * @param  integer $code      HTTP状态码
     * @param  boolean $escape    是否转义
     * @return void
     */
    public static function json($data, $code = 200, $escape = false)
    {
        // 判断是否为重定向
        if (in_array($code, ['601', '602', '603'])) {
            // 判断HTTP状态
            switch ($code) {
                // 链接跳转
                case '601':
                    $data = ['url' => $data];
                    break;
                // 表单跳转
                case '602':
                    $data = ['form' => $data];
                    break;
                // 路由跳转
                case '603':
                    $data = ['route' => $data];
                    break;

                default:
                    throw new \ThrowableException($code, '无效的跳转状态码');
                    break;
            }            
        }
        
        // 设置响应头
        $httpStatusText = isset(self::$httpStatus[$code]) ? (self::$httpStatus[$code]) : 'Unknow Http Status';
        header("HTTP/1.1 {$code} {$httpStatusText}");
        header('Content-type: application/json; charset=utf-8');
        // 响应数据给客户端
        $data = $escape ? json_encode($data) : json_encode($data, JSON_UNESCAPED_UNICODE);
        exit($data);
    }

    /**
     * 响应数据：alert方式
     * @param  string  $data 数据
     * @param  string  $url  跳转URL
     * @param  integer $code HTTP状态码
     * @return void
     */
    public static function alert($data, $url = null, $code = 200)
    {
        //设置响应头
        $httpStatusText = isset(self::$httpStatus[$code]) ? (self::$httpStatus[$code]) : 'Unknow Http Status';
        header("HTTP/1.1 {$code} {$httpStatusText}");
        header('Content-type: text/html; charset=utf-8');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $data = "<script>alert(\"{$data}\");</script>";
        } else {
            $data = "<script>alert(\"{$data}\");window.location.href=\"{$url}\";</script>";
        }
        exit($data);
    }

    /**
     * 网址跳转
     * @param  string  $url    网址
     * @param  array   $params 参数
     * @param  integer $code   状态码
     * @return void
     */
    public static function jump($url, array $params = [], $code = 200)
    {
        // 跳转前准备
        $url            = empty($params) ? $url : \smart\Url::buildUrl('/', $params, $url);
        $httpStatusText = isset(self::$httpStatus[$code]) ? (self::$httpStatus[$code]) : 'Unknow Http Status';
        // URL跳转
        header("HTTP/1.1 {$code} {$httpStatusText}");
        header('Content-type: text/html; charset=utf-8');
        header('Location:' . $url);
        exit();
    }
}
