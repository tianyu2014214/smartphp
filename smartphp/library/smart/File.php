<?php

namespace smart;

use smart\Log;
use smart\file\Upload;
use smart\exception\ClassNotFoundException;

class File extends \SplFileObject
{
    // 文件处理句柄
    private static $handler;

    // 错误信息
    private static $errors;

    // 文件头部信息
    private static $mimes = [
        'image/bmp'     => 'bmp',
        'image/gif'     => 'gif',
        'image/jpeg'    => 'jpg',
        'image/png'     => 'png',
        'image/x-icon'  => 'ico',
    ];

    /**
     * 设置自定义文件处理器
     * @param  object $obj  操作对象
     * @param  string $func 操作方法
     * @return object
     */
    public static function handler(object $obj, string $func)
    {
        // 检测自定义处理器是否可用
        if (is_callable([$obj, $func])) {
            // 处理器可用
            self::$handler = [$obj, $func];
        } else {
            // 处理器不可用
            $func = get_class($obj) . '->' . $func . '()';

            throw new \Exception('自定义文件处理器不可用：' . $func);
        }
    }

    /**
     * 上传文件到服务器
     * @param  array  $info 上传文件信息
     * @return object
     */
    public static function upload(array $info)
    {
        return new Upload($info);
    }

    /**
     * 下载文件到服务器
     * @param  string $url      下载地址
     * @param  string $savePath 存储路径
     * @param  mixed  $saveName 存储名称
     * @return mixed
     */
    public static function download($url, $savePath, $saveName = true)
    {
        // 检查是否采用第三方处理器
        if (!is_null(self::$handler)) {
            return self::callHandler([$url, $savePath, $saveName]);
        }

        // 下载文件
        $curl   = Curl::init(false)->showResponseHeader(true);
        $result = $curl->send($url);
        if ($result['code'] == 200) {
            // 提取文件信息
            $data = $result['data'];
            $mime = $result['header']['response']['Content-Type'];
            $ext  = isset(self::$mimes[$mime]) ? ('.'.self::$mimes[$mime]) : '';
        } else {
            // 文件不存在
            self::$errors = '无效的文件地址';
            return false;
        }

        // 生成存储文件名
        if (is_bool($saveName)) {
            // 自动生成文件名
            $hash = hash('sha1', $data);
            $saveName = $saveName ? substr($hash, 0, 2) . DS . substr($hash, 2) : $hash;
            $saveName = $saveName . $ext;
        }

        // 保存文件
        $saveName = trim($saveName, DS);
        $savePath = $savePath ? trim($savePath, DS) . DS : '';
        $filePath = UPLOAD_PATH . $savePath . $saveName;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        file_put_contents($filePath, $data);
        return $savePath . $saveName;
    }

    /**
     * 删除文件
     * @param  string  $filepath    文件名
     * @param  boolean $logicDelete 是否逻辑删除
     * @return boolean
     */
    public static function delete(string $filepath, bool $logicDelete = false)
    {
        // 检查是否采用第三方处理器
        if (!is_null(self::$handler)) {
            return self::callHandler([$filepath, $logicDelete]);
        }

        // 默认删除文件程序
        try {
            // 检查文件是否存在
            if (!file_exists(UPLOAD_PATH . $filepath)) {
                self::$errors = '文件不存在：' . pathinfo($filepath, PATHINFO_BASENAME);
                return false;
            }
            // 检查是否为逻辑删除
            if ($logicDelete) {
                $date = date('Y-m-d', time());
                $name = str_replace('/', '-', $filepath);
                $path = 'trash' . DS . $date . DS . $name;
                return self::move($filepath, $path);
            }
            // 进行物理删除
            if (!unlink(UPLOAD_PATH . $filepath)) {
                self::$errors = '文件删除失败：' . pathinfo($filepath, PATHINFO_BASENAME);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::instance()->write('critical', '[delete]' . $e->getMessage());
            self::$errors = '系统发生异常~';
            return false;
        }
    }

    /**
     * 移动文件
     * @param  string $from 源地址
     * @param  string $to   目标地址
     * @return bool
     */
    public static function move(string $from, string $to)
    {
        // 检查是否采用第三方处理器
        if (!is_null(self::$handler)) {
            return self::callHandler([$from, $to]);
        }

        // 默认移动文件程序
        try {
            // 检查文件是否存在
            if (!file_exists(UPLOAD_PATH . $from)) {
                self::$errors = '源文件不存在：' . pathinfo($from, PATHINFO_BASENAME);
                return false;
            }
            // 移动文件
            if (!rename($from, $to)) {
                self::$errors = '文件移动失败：' . pathinfo($from, PATHINFO_BASENAME);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            Log::instance()->write('critical', '[move]' . $e->getMessage());
            self::$errors = '系统发生异常~';
            return false;
        }
    }

    /**
     * 加载文件
     * @param  string $filepath 文件路径
     * @return mixed
     */
    public static function load(string $filepath)
    {
        // 检查是否采用第三方处理器
        if (!is_null(self::$handler)) {
            return self::callHandler([$filepath]);
        }

        // 默认加载文件程序
        try {
            // 检查文件是否存在
            if (!file_exists(UPLOAD_PATH . $filepath)) {
                self::$errors = '文件不存在：' . pathinfo($filepath, PATHINFO_BASENAME);
                return false;
            }
            // 加载文件内容
            return file_get_contents(UPLOAD_PATH . $filepath);
        } catch (\Exception $e) {
            Log::instance()->write('critical', '[load]' . $e->getMessage());
            self::$errors = '系统发生异常~';
            return false;
        }
    }

    /**
     * 生成文件访问链接
     * @param  string $filepath 文件路径
     * @param  string $host     访问主机
     * @return string
     */
    public static function url($filepath, $host = null)
    {
        $host = $host ?: Config::get('storage_host');

        return rtrim($host, '/') . DS . $filepath;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public static function getError()
    {
        return self::$errors;
    }

    /**
     * 调用第三方处理程序
     * @param  array $params 调用参数
     * @return bool
     */
    private static function callHandler(array $params)
    {
        // 调用第三方处理器
        if (!call_user_func_array(self::$handler, $params)) {
            self::$errors  = self::$handler->getError();
            slef::$handler = null;
            return false;
        }
        // 返回结果
        self::$handler = null;
        return true;
    }
}