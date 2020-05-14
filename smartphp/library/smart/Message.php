<?php

namespace smart;

class Message
{
    // 错误编码
    private $code;

    // 错误信息
    private $message;

    // 暂存数据
    private $data = [];

    /**
     * 设置错误信息
     * @param  string $errorCode    错误代码
     * @param  string $errorMessage 错误信息
     * @return void
     */
    public function setError(string $errorCode, string $errorMessage)
    {
        $this->code    = $errorCode;
        $this->message = $errorMessage;
    }

    /**
     * 设置提示信息
     * @param  string $message 提示信息
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * 设置成功数据
     * @param  mixed  $data 数据信息
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * 获取错误编码
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 获取成功数据
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
