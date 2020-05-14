<?php

namespace smart\exception;

class HttpException extends \RuntimeException
{
    private $statusCode;
    private $headers;

    /**
     * 构造函数
     * @param  string     $statusCode 状态码
     * @param  string     $message    错误信息
     * @param  \Exception $previous   原始异常信息
     * @param  array      $headers    请求头
     * @param  integer    $code       错误码
     */
    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers    = $headers;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取HTTP状态码
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * 获取请求头
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
