<?php

namespace smart\file;

class Validate
{
    // 文件检测提示信息
    protected $checkMessage = [
        'size' => '文件大小不可超过{:size}KB',
        'mime' => '暂不支持文件类型：{:mime}',
        'ext'  => '暂不支持文件扩展名：{:ext}',
    ];

    /**
     * 检查目录是否可写
     * @param  string $path 文件路径
     * @return bool
     */
    public function checkPath($path)
    {
        if ((is_dir($path) && is_writable($path)) || mkdir($path, 0755, true)) {
            return true;
        }
        return false;
    }

    /**
     * 检查文件是否为合法到图像
     * @return bool
     */
    public function checkImage()
    {
        $ext = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

        return !$this->checkExt($ext) || in_array($this->getImageType($this->filename), [1, 2, 3, 4, 6, 13]);
    }

    /**
     * 检测上传文件大小
     * @param  integer $maxsize 允许的最大文件大小
     * @return bool
     */
    protected function checkSize($maxsize)
    {
        // 计算允许的最大大小
        $unit = strtolower(substr($maxsize, -1));
        if ($unit == 'k') {
            $maxsize = intval(substr($maxsize, 0, strlen($maxsize) - 2)) * 1024;
        } elseif ($unit == 'm') {
            $maxsize = intval(substr($maxsize, 0, strlen($maxsize) - 2)) * 1024*1024;
        }
        if ($this->info['size'] > $maxsize) {
            $maxsize = intval($maxsize / 1024) ?: 1;
            $this->errors = str_replace('{:size}', $maxsize, $this->checkMessage['size']);
            return false;
        }
        return true;
    }

    /**
     * 检测上传文件类型
     * @param  string $mime 允许的文件类型
     * @return bool
     */
    protected function checkMime(string $mime)
    {
        $type = strtolower($this->info['type']);
        if (!in_array($type, explode(',', strtolower($mime)))) {
            $this->errors = str_replace('{:mime}', $mime, $this->checkMessage['mime']);
            return false;
        }
        return true;
    }

    /**
     * 检测上传文件扩展名
     * @param  string $ext 允许的扩展名
     * @return bool
     */
    protected function checkExt($ext)
    {
        $ext = is_array($ext) ? $ext : explode(',', strtolower($ext));
        $extension = strtolower(pathinfo($this->info['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $ext)) {
            $this->errors = str_replace('{:ext}', $ext[0], $this->checkMessage['ext']);
            return false;
        }
    }

    /**
     * 获取图像类型
     * @param  string   $image 图像路径
     * @return bool|int
     */
    private function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }

        try {
            return ($info = getimagesize($image)) ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }
}