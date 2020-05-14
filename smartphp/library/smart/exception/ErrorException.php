<?php

namespace smart\exception;

use smart\Exception;

/**
 * SmartPHP错误异常
 * 主要用于封装 set_error_handler 和 register_shutdown_function 得到到错误
 */
class ErrorException extends Exception
{
    // 保存错误级别
    protected $severity;

    /**
     * 错误异常构造函数
     * @param  integer $severity 错误级别
     * @param  string  $message  错误详细信息
     * @param  string  $file     出错文件路径
     * @param  integer $line     出错行号
     * @param  array   $context  错误上下文，包含错误触发作用域内所有变量数据
     */
    public function __construct($severity, $message, $file, $line, array $context = [])
    {
        $this->severity = $severity;
        $this->message  = $message;
        $this->file     = $file;
        $this->line     = $line;
        $this->code     = 0;

        empty($context) || $this->setData('Error Context', $context);
    }

    /**
     * 获取错误级别
     * @return integer 错误级别
     */
    final public function getSeverity()
    {
        return $this->severity;
    }
}
