<?php

namespace smart;

use smart\crypt\Aes;
use smart\crypt\Rsa;

class Crypt
{
    /**
     * 数据加密（RSA + AES）
     * @param  mixed  $data      数据
     * @param  string $publicKey 公钥
     * @param  string $type      类型
     * @return string
     */
    public static function encrypt($data, $publicKey = '', $type = '')
    {
        // 加密前准备工作
        $type      = $type ?: self::getRsaType();
        $publicKey = $publicKey ?: self::getRsaKey('public');
        $cipher    = [
            'key' => rands(32),
            'iv'  => rands(16)
        ];

        // 数据加密操作
        $encryptKey  = Rsa::init()->encrypt(json_encode($cipher), $publicKey, $type);
        $encryptData = Aes::init()->encrypt(json_encode($data), $cipher['key'], $cipher['iv']);
        return $encryptKey . $encryptData;
    }

    /**
     * 数据解密（RSA + AES）
     * @param  string $ciphertext 密文
     * @param  string $privateKey 密钥
     * @param  string $type       类型
     * @return mixed
     */
    public static function decrypt($ciphertext, $privateKey = '', $type = '')
    {
        // 解密 cipher 部分
        $type       = $type ?: self::getRsaType();
        $privateKey = $privateKey ?: self::getRsaKey('private');
        $cipherData = Rsa::init()->decrypt(substr($ciphertext, 0, 171), $privateKey, $type);
        $cipher     = json_decode($cipherData, true);
        if (empty($cipher)) {
            return '';
        }

        // 解密 data 部分
        $data = Aes::init()->decrypt(substr($ciphertext, 172), $cipher['key'], $cipher['iv']);
        return $data ? json_decode($data, true) : '';
    }

    /**
     * 创建签名
     * @param  mixed  $data       数据
     * @param  string $privateKey 私钥
     * @param  string $type       类型
     * @return string
     */
    public static function createSign($data, $privateKey = '', $type = '')
    {
        // 签名前准备工作
        $type       = $type ?: self::getRsaType();
        $privateKey = $privateKey ?: self::getRsaKey('private');
        // 检查数据是否为数组类型
        if (is_array($data)) {
            ksort($data);
            $data = json_encode($data);
        }
        // 检查数据是否为字符串类型
        if (!is_string($data)) {
            throw new \Exception('待验证签名的源数据仅支持数组和字符串类型');
        }
        // 签名操作
        return Rsa::init()->sign($data, $privateKey, $type);
    }

    /**
     * 验证签名
     * @param  mixed  $data      数据
     * @param  string $sign      签名
     * @param  string $publicKey 公钥
     * @param  string $type      类型
     * @return bool
     */
    public static function verifySign($data, $sign, $publicKey = '', $type = '')
    {
        // 签名前准备工作
        $type       = $type ?: self::getRsaType();
        $publicKey = $publicKey ?: self::getRsaKey('public');
        // 检查数据是否为数组类型
        if (is_array($data)) {
            ksort($data);
            $data = json_encode($data);
        }
        // 检查数据是否为字符串类型
        if (!is_string($data)) {
            throw new \Exception('待验证签名的源数据仅支持数组和字符串类型');
        }
        // 验签操作
        $sign = (string) $sign;
        return (bool) Rsa::init()->verify($data, $sign, $publicKey, $type);
    }

    /**
     * 通过 hash 算法对密码进行加密
     * @param  string  $password 密码
     * @param  string  $iv       向量
     * @param  integer $algo     盐值
     * @return void
     */
    public static function password($password, $iv = '', $algo = PASSWORD_DEFAULT)
    {
        $password = base64_encode(md5($password . $iv));

        return password_hash($password, $algo);
    }

    /**
     * 通过 hash 验证密码的有效性
     * @param  string $password 原始密码
     * @param  string $hash     HASH后的密码
     * @param  string $iv       向量
     * @return bool
     */
    public static function verify($password, $hash, $iv = '')
    {
        $password = base64_encode(md5($password . $iv));

        return password_verify($password, $hash);
    }

    /**
     * 获取默认 RSA 类型
     * @return string
     */
    private static function getRsaType()
    {
        return Config::get('crypt.type');
    }

    /**
     * 获取默认 RSA 密钥
     * @param  string $keytype 密钥类型
     * @return mixed
     */
    private static function getRsaKey($keytype)
    {
        // 从配置中获取密钥
        $conf    = Config::get('crypt');
        if ($conf["{$keytype}_key"]) {
            return $conf["{$keytype}_key"];
        }
        // 从文件中获取密钥
        return Rsa::getRsaKeyFromFile($conf["{$keytype}_key_path"], $keytype);
    }
}
