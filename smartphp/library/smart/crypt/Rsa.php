<?php

namespace smart\crypt;

class Rsa
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
     * 使用 RSA 对明文进行加密
     * @param  string $cleartext 明文
     * @param  string $publicKey 公钥
     * @param  string $type      类型
     * @return string
     */
    public function encrypt(string $cleartext, $publicKey, $type = 'rsa1')
    {
        // 检查待加密数据长度
        if (strlen($cleartext) > 117) {
            throw new \Exception('RSA加密数据长度不得大于117个字节');
        }

        // 加密数据
        switch (strtolower($type)) {
            // 使用SHA1算法进行加密
            case 'rsa1':
                openssl_public_encrypt($cleartext, $ciphertext, $publicKey, OPENSSL_PKCS1_PADDING);
                break;

            // 使用SHA256算法进行加密
            case 'rsa2':
                openssl_public_encrypt($cleartext, $ciphertext, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
                break;

            // 非法的RSA类型
            default:
                throw new \Exception('非法的RSA类型');
                break;
        }

        // 释放资源，并对密文数据
        openssl_free_key($publicKey);
        return base64_encode($ciphertext);
    }

    /**
     * 使用 RSA 对密文进行解密
     * @param  string $ciphertext 密文
     * @param  string $privateKey 密钥
     * @param  string $type       类型
     * @return string
     */
    public function decrypt(string $ciphertext, $privateKey, $type = 'rsa1')
    {
        // 将密文进行解密
        switch (strtolower($type)) {
            // 使用SHA1算法解密
            case 'rsa1':
                openssl_private_decrypt(base64_decode($ciphertext), $cleartext, $privateKey, OPENSSL_PKCS1_PADDING);
                break;

            // 使用SHA256算法解密
            case 'rsa2':
                openssl_private_decrypt(base64_decode($ciphertext), $cleartext, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
                break;

            // 非法的RSA类型
            default:
                throw new \Exception('非法的RSA类型');
                break;
        }

        // 释放资源，返回明文数据
        openssl_free_key($privateKey);
        return $cleartext;
    }

    /**
     * 使用 RSA 生成签名
     * @param  string $data       数据
     * @param  string $privateKey 私钥
     * @param  string $type       类型
     * @return string
     */
    public function sign(string $data, $privateKey, $type = 'rsa1')
    {
        // 生成签名
        switch (strtolower($type)) {
            // 使用SHA1算法签名
            case 'rsa1':
                openssl_sign($data, $sign, $privateKey, OPENSSL_ALGO_SHA1);
                break;

            // 使用SHA256算法签名
            case 'rsa2':
                openssl_sign($data, $sign, $privateKey, OPENSSL_ALGO_SHA256);
                break;

            // 非法的RSA类型
            default:
                throw new \Exception('非法的RSA类型');
                break;
        }

        // 释放资源, 返回签名
        openssl_free_key($privateKey);
        return base64_encode($sign);
    }

    /**
     * 使用 RSA 验证签名
     * @param  string $data       数据
     * @param  string $sign       签名
     * @param  string $publicKey  公钥
     * @param  string $type       类型
     * @return string
     */
    public function verify(string $data, string $sign, $publicKey, $type = 'rsa1')
    {
        // 验证签名
        switch (strtolower($type)) {
            // 使用SHA1算法签名
            case 'rsa1':
                $result = (bool) openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA1);
                break;

            // 使用SHA256算法签名
            case 'rsa2':
                $result = (bool) openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
                break;

            // 非法的RSA类型
            default:
                throw new \Exception('非法的RSA类型');
                break;
        }

        // 释放资源，返回验签结果
        openssl_free_key($publicKey);
        return $result;
    }

    /**
     * 从文件中获取 RSA 密钥
     * @param  string $path 密钥路径
     * @param  string $type 密钥类型
     * @return string
     */
    public static function getRsaKeyFromFile($path, $type)
    {
        // 检查密钥类型
        $type = strtolower($type);
        if ($type != 'public' && $type != 'private') {
            throw new \Exception('密钥类型仅支持：public/private');
        }

        // 获取密钥信息
        if ($path && file_exists($path)) {
            // 密钥文件存在
            $content = file_get_contents($path);
        } else {
            // 密钥文件不存在
            throw new \Exception('RSA密钥文件不存在');
        }

        // 解析密钥信息
        $getKeyFunc = "openssl_get_{$type}key";
        return $getKeyFunc($content);
    }
}
