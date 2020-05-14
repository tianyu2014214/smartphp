<?php

namespace smart\validate;

class Idcard
{
    /**
     * 校验身份证是否有效
     * @param  string $idcard 身份证号
     * @return bool
     */
    public static function check($idcard)
    {
        $idcard = (strlen($idcard) == 15) ? (self::convertIdcard15to18($idcard)) : $idcard;
        
        return self::checkIdCard($idcard);
    }

    /**
     * 计算身份证最后一位验证码
     * @param  string $idcard 身份证号前17位
     * @return int
     */
    private static function calcIdcardCode($idcardBody)
    {
        if (strlen($idcardBody) != 17) {
            return false;
        }

        // 加权因子和校验码对应值
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $code   = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        // 计算校验码
        $checkSum = 0;
        for ($i=0; $i < strlen($idcardBody); $i++) { 
            $checkSum += substr($idcardBody, $i, 1) * $factor[$i];
        }

        return $code[$checkSum % 11];
    }

    /**
     * 将15位身份证升级到18位
     * @param  string $idcard 身份证号
     * @return string
     */
    private static function convertIdcard15to18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        }

        // 百岁以上老人身份证顺序码996，997，998，999
        if (array_search(substr($idcard, 12, 3), ['996', '997', '998', '999']) !== false) {
            $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
        } else {
            $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
        }
        return $idcard . self::calcIdcardCode($idcard);
    }

    /**
     * 检查身份证号有效性
     * @param  string $idcard 身份证号
     * @return bool
     */
    private static function checkIdcard($idcard)
    {
        if (strlen($idcard) != 18) {
            return false;
        }

        $idcardBody = substr($idcard, 0, 17);
        $idcardCode = strtoupper(substr($idcard, 17, 1));

        if(self::calcIdcardCode($idcardBody) == $idcardCode) {
            return true;
        } else {
            return false;
        }
    }
}
