<?php

/**
 * include
 * @param  string $file 文件路径
 * @return mixed
 */
function __include_file($file)
{
    return include $file;
}

/**
 * require
 * @param  string $file 文件路径
 * @return mixed
 */
function __require_file($file)
{
    return require $file;
}

/**
 * 向字符串中追加字符
 * @param  string $originStr 原字符串
 * @param  string $apendStr  追加字符
 * @param  string $position  追加位置
 * @return string
 */
function strapend($originStr, $apendStr, $position = 'both')
{
    switch ($position) {
        // 向左边追加
        case 'left':
            return $apendStr . $originStr;
            break;
        // 向右边追加
        case 'right':
            return $originStr . $apendStr;
            break;
        // 向两边追加
        default:
            return $apendStr . $originStr . $apendStr;
            break;
    }
}

/**
 * 生成随机字符串
 * @param  integer $num  字符串长度
 * @param  string  $type 字符串类型
 * @return string
 */
function rands($num, $type = '')
{
    // 选择字符串类型
    switch ($type) {
        // 字母
        case 'alpha':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        // 大写字母
        case 'upperAlpha':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        // 小写字母
        case 'lowerAlpha':
            $chars = 'abcdefghijklmnopqrstuvwxyz';
            break;
        // 数字
        case 'number':
            $chars = '0123456789';
            break;
        // 默认字母和数字组合
        default:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }
    // 生成随机字符串
    $str  = null;
	$rand = str_split($chars, 1);
	$len  = count($rand) - 1;
	for ($i=0; $i < $num; $i++) { 
		$seed = mt_rand(0, $len);
		$str .= $rand[$seed];
	}
	return $str;
}

/**
 * 生成二维码访问链接
 * @param  string $data 数据
 * @return string
 */
function qrcode($data)
{
    return 'http://qr.liantu.com/api.php?text=' . $data;
}

/**
 * 将数据格式化为JSON
 * @param  mixed  $data   数据
 * @param  bool   $escape 是否转义
 * @return string
 */
function json($data, $escape = false)
{
    return $escape ? json_encode($data) : json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * 隐藏部分字符
 * @param  string  $str   原始字符串
 * @param  integer $start 起始长度
 * @param  integer $end   结尾长度
 * @param  integer $len   隐藏符长度
 * @return string
 */
function strcut($str, $start = 1, $end = 1, $len = 1)
{
    // 特殊处理：email
    if (strpos($str, '@') !== false) {
        $email = explode('@', $str);
        $prestr = strlen($email[0]) <= 2 ? $email[0] : substr($email[0], 0, 2);
        return $prestr . '***@' . $email[1];
    }

    // 正常处理字符串
    $length = mb_strlen($str, 'utf-8');
    if ($length > 2) {
        $first = mb_substr($str, 0, $start >= $length ? $length-1 : $start, 'utf-8');
        $last  = $start+$end <= $length-1 ? mb_substr($str, -1*$end, $end, 'utf-8') : '';
        $len   = $len <=3 ? $length - mb_strlen($first) - mb_strlen($last) : $len;
    } else {
        $first = $length <= 1 ? $str : mb_substr($str, 0, 1, 'utf-8');
        $last  = '';
        $len   = 1;
    }
    return $first . str_repeat('*', $len) . $last;
}

/**
 * 转义数据中的特殊字符
 * @param  string $data 待转义数据
 * @return string
 */
function escape($data)
{
    $data = addslashes($data);
    $data = addcslashes($data, "[^]($){.+*?}");
    return $data;
}

/**
 * 将特殊字符转换为HTML实体
 * @param  string $data 待转义实体
 * @return string
 */
function htmlencode($data)
{
    return htmlentities($data, ENT_QUOTES);
}

/**
 * 将扁平化数据转化为树
 * @param  array  $items 数据
 * @param  string $id    编号
 * @param  string $pid   父编号
 * @param  string $son   子项名称
 * @return array
 */
function genTree($items, $id = 'id', $pid = 'pid', $son = 'children')
{
    // 临时扁平数据
	$tmpMap = [];
	foreach ($items as $item) {
        unset($item[$pid]);
		$tmpMap[$item[$id]] = $item;
	}

	// 将扁平数据格式化成树
	$tree = [];
	foreach ($items as $item) {
		if (isset($tmpMap[$item[$pid]])) {
			$tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
		} else {
			$tree[] = &$tmpMap[$item[$id]];
		}
	}

	// 销毁临时变量，返回结果
	unset($tmpMap);
	return $tree;
}

/**
 * 下划线转驼峰
 * @param  string $uncamelizeWords 非驼峰字符串
 * @param  string $separator       分隔符
 * @return string
 */
function camelize($uncamelizeWords, $separator = '_')
{
    $uncamelizeWords = $separator . str_replace($separator, ' ', strtolower($uncamelizeWords));
    return ltrim(str_replace(' ', '', ucwords($uncamelizeWords)), $separator);
}

/**
 * 驼峰转下划线
 * @param  string $camelCaps 驼峰字符串
 * @param  string $separator 分隔符
 * @return string
 */
function uncamelize($camelCaps, $separator = '_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

/**
 * 时间分段
 * @param  integer $timestamp 时间戳
 * @return string
 */
function timephased($timestamp)
{
    $timePhased = [
        '凌晨' => [0, 6],
        '早晨' => [6, 8],
        '上午' => [8, 11],
        '中午' => [11, 13],
        '下午' => [13, 17],
        '傍晚' => [17, 19],
        '晚上' => [19, 24],
    ];
    foreach ($timePhased as $text => $time) {
        // 判断指定时间是否属于该时间段
        $hour = date('G', $timestamp);
        if ($hour >= $time[0] && $hour < $time[1]) {
            return date("Y年m月d日 {$text}H:i", $timestamp);
        }
    }
}
