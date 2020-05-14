<?php

namespace smart\exception;

use \smart\Exception;

class DbException extends Exception
{
    /**
     * DbException constructor
     * @param string $message
     * @param array  $config
     * @param string $sql
     * @param int    $code
     */
    public function __construct($message, array $config, $sql = null, $code = 10500)
    {
        // exception base
        $this->message = $message;
        $this->code    = $code;
        // exception detail
        $this->setData('Database Status', [
            'Error Code'    => $code,
            'Error Message' => $message,
            'Error SQL'     => $sql
        ]);
        // database config
        unset($config['username'], $config['password']);
        $this->setData('Database Config', $config);
    }

    /**
     * 获取发生异常时的错误编码
     * @return string
     */
    public function getErrorCode()
    {
        return $this->data['Database Status']['Error Code'];
    }

    /**
     * 获取发生异常时的错误信息
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->data['Database Status']['Error Message'];
    }

    /**
     * 获取发生异常时执行的SQL
     * @return string
     */
    public function getDbSql()
    {
        return $this->data['Database Status']['Error SQL'];
    }

    /**
     * 获取发生异常时数据库配置
     * @return array
     */
    public function getDbConfig()
    {
        return $this->data['Database Config'];
    }
}
