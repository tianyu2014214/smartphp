<?php

namespace smart\crypt;

class Aes
{
    // 实例对象
    private static $instance;

    // 构造函数
    private function __construct() {}

    // 克隆函数
    private function __clone() {}

    /**
     * 获取 RSA 对象实例
     * @return object
     */
    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 使用 AES 加密数据
     * @param  string $data 待加密数据
     * @param  string $key  密钥
     * @param  string $iv   向量
     * @return string
     */
    public function encrypt($data, $key, $iv)
    {
        $data    = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        return base64_encode($data);
    }

    /**
     * 使用 AES 解密数据
     * @param  string $data 待解密数据
     * @param  string $key  密钥
     * @param  string $iv   向量
     * @return string
     */
    public function decrypt($data, $key, $iv)
    {
        $data    = openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        return $data;
    }
}
