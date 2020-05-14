<?php

namespace wechat\library\crypt;

/**
 * 消息签名接口.
 */
class Sha1
{
	/**
	 * 用SHA1算法生成安全签名
	 * @param string $token       票据
	 * @param string $timestamp   时间戳
	 * @param string $nonce       随机字符串
	 * @param string $encrypt_msg 密文消息
	 */
	public function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
	{
		try {
            // 正常
			$array = array($encrypt_msg, $token, $timestamp, $nonce);
			sort($array, SORT_STRING);
			return [ErrorCode::$OK, sha1(implode($array))];
		} catch (\Exception $e) {
			// 异常
			return [ErrorCode::$ComputeSignatureError, null];
		}
	}
}
