<?php

namespace smart;

use smart\exception\ErrorException;
use smart\exception\ThrowableError;
use smart\exception\Handle;

class Error
{
    /**
     * 注册异常处理
     * @return void
     */
    public static function register()
    {
        $debug = Config::get('app_debug');
        error_reporting($debug ? E_ALL : (E_ERROR | E_PARSE));
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    /**
     * 异常处理
     * @param  \Exception|\Throwable $e 异常
     * @return void
     */
    public static function appException($e)
    {
        if (!$e instanceof \Exception) {
            $e = new ThrowableError($e);
        }
        
        $handler = self::getExceptionHandler();
        $handler->report($e);
        $handler->render($e, Config::get('error_render'));
    }

    /**
     * 错误处理
     * @param  integer $errno      错误编号
     * @param  integer $errstr     详细错误信息
     * @param  string  $errfile    出错的文件
     * @param  integer $errline    出错行号
     * @return void
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);

        // 符合异常处理的则将错误信息托管至 smart\exception\ErrorException
        if (error_reporting() & $errno) {
            throw $exception;
        }

        self::getExceptionHandler()->report($exception);
    }

    /**
     * 异常中止处理
     * @return void
     */
    public static function appShutdown()
    {
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            // 将错误信息托管至 smart\ErrorException
            $exception = new ErrorException(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
            self::appException($exception);
        }
    }

    /**
     * 获取异常处理实例
     * @return Handle
     */
    public static function getExceptionHandler()
    {
        static $handle;

        if (!$handle) {
            // 异常处理 handle
            $class = Config::get('exception_handle');

            if ($class && is_string($class) && class_exists($class) && is_subclass_of($class, '\smart\exception\Handle')) {
                $handle = new $class;
            } else {
                $handle = new Handle();
                if ($class instanceof \Closure) {
                    $handle->setRender($class);
                }
            }
        }

        return $handle;
    }

    /**
     * 确定错误类型是否致命
     * @param  int $type 错误类型
     * @return bool
     */
    protected static function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }
}
