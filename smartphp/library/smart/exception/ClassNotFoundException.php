<?php

namespace smart\exception;

class ClassNotFoundException extends \RuntimeException
{
    // 类名
    protected $class;

    /**
     * 构造函数
     */
    public function __construct($message, $class = '')
    {
        $this->message = $message;
        $this->class   = $class;
    }

    /**
     * 获取类名
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
