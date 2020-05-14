<?php

namespace smart\file;

use smart\file\Validate;

class Upload extends Validate
{
    // 自定义处理器
    private $handler;

    // 错误提示信息
    protected $errors;

    // 完整文件名
    protected $filename;

    // 保存文件名
    protected $savename;

    // 上传信息
    protected $info;

    /**
     * 构造函数
     */
    public function __construct($info)
    {
        // 保存文件上传信息
        $this->info = $info;

        // 保存文件完整名称
        $this->filename = $info['tmp_name'];
    }

    /**
     * 设置自定义文件上传处理器
     * @param  object $obj  操作对象
     * @param  string $func 操作方法
     * @return object
     */
    public function handler(object $obj, string $func)
    {
        // 检测自定义处理器是否可用
        if (is_callable([$obj, $func])) {
            // 处理器可用
            self::$handler = [$obj, $func];
        } else {
            // 处理器不可用
            $func = get_class($obj) . '->' . $func . '()';

            throw new \Exception('自定义文件上传处理器不可用：' . $func);
        }
    }

    /**
     * 移动上传文件
     * @param  string  $path     文件保存路径
     * @param  boolean $savename 文件保存名（默认自动生成）
     * @param  boolean $replace  是否覆盖重名文件（默认覆盖）
     * @return boolean
     */
    public function move($path, $savename = true, $replace = true)
    {
        // 生成文件保存名称
        $filename = $this->buildSaveName($savename);
        $filepath = rtrim($path, DS) . DS . $filename;

        // 选择处理程序
        $this->handler = $this->handler ?: [$this, 'save'];
        if (!call_user_func_array($this->handler, [$this->filename, $filepath, $replace])) {
            $this->error   = $this->handler[0]->getError();
            $this->handler = null;
            return false;
        }

        // 返回结果
        $this->savename = $filepath;
        return true;
    }

    /**
     * 上传文件合法性校验
     * @param  array $rule 文件校验规则
     * @return bool
     */
    public function check(array $rule = [])
    {
        // 检查文件上传过程是否出错
        if (!empty($this->info['error'])) {
            $this->error = $this->info['error'];
            return false;
        }
        // 检查文件是否为合法到上传文件
        if (!is_uploaded_file($this->filename)) {
            $this->error = '非有效到上传文件';
            return false;
        }
        // 文件合法性校验
        foreach ($this->checkMessage as $key => $message) {
            $func = 'check' . ucwords($key);
            if (isset($rule[$key]) && !call_user_func([$this, $func], $rule[$key])) {
                $originName   = $this->getOriginName();
                $this->errors = "[{$originName}]" . $this->errors;
                return false;
            }
        }
        // 文件如果为图像文件则进行进一步检查
        if (!$this->checkImage()) {
            $originName   = $this->getOriginName();
            $this->errors = "[{$originName}]非法的图像文件";
            return false;
        }
        return true;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errors;
    }

    /**
     * 获取文件保存名称
     * @return string
     */
    public function getSaveName()
    {
        return $this->savename;
    }

    /**
     * 获取原始文件名
     * @param  boolean $ext 是否返回扩展名
     * @return string
     */
    public function getOriginName($ext = false)
    {
        $opt = $ext ? PATHINFO_BASENAME : PATHINFO_FILENAME;
        return pathinfo($this->info['name'], $opt);
    }

    /**
     * 生成上传文件存储名称
     * @param  mixed  $savename 存储名（boolean表示自动生成）
     * @return string
     */
    private function buildSaveName($savename)
    {
        // 生成文件名
        if (is_bool($savename)) {
            // 自动生成文件名
            $hash = hash_file('sha1', $this->filename) . md5(microtime() . rands(16));
            $savename = $savename ? substr($hash, 0, 2) . DS . substr($hash, 2) : $hash;
        } else {
            // 使用指定文件名
            $savename = empty($savename) ? $this->getOriginName(true) : $savename;
        }
        // 判断文件是否存在扩展名
        if (strpos($savename, '.') === false) {
            $ext = pathinfo($this->getOriginName(true), PATHINFO_EXTENSION);
            $ext = $ext ? strapend($ext, '.', 'left') : '.png';
            $savename .= $ext;
        }
        // 返回文件名
        return $savename;
    }

    /**
     * 存储文件到服务器
     * @param  array   $uploadinfo 上传信息
     * @param  string  $filepath   文件路径
     * @param  boolean $replace    是否覆盖
     * @return boolean
     */
    private function save($uploadinfo, $filepath, $replace)
    {
        // 检查文件存储路径
        $savepath = is_dir($filepath) ? $filepath : UPLOAD_PATH . $filepath;
        if (!$this->checkPath(dirname($savepath))) {
            $this->errors = '无存储权限';
            return false;
        }
        // 检查文件是否已存在
        if (!$replace && file_exists($savepath)) {
            $this->errors = '文件已存在：' . $filepath;
            return false;
        }
        // 保存文件到服务器
        if (!move_uploaded_file($uploadinfo, $savepath)) {
            $this->errors = '文件上传失败';
            return false;
        }
        return true;
    }
}