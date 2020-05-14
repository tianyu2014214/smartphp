<?php

namespace smart;

class Curl
{
    // curl 操作句柄
    private $handle;

    // 请求头列表
    private $requestHeader = [];

    // 控制请求头显示
    private $showRequestHeader;

    // 控制响应头显示
    private $showResponseHeader;

    /**
     * 构造函数
     * @param mixed $cookie 开关/文件名
     */
    public function __construct($cookie = false)
    {
        // 初始化 CURL 操作句柄
        $this->handle = curl_init();

        // 开启cookie
        $this->enableCookie($cookie);

        // 设置是否保存请求头
        $this->showRequestHeader(false);

        // 设置是否保存响应头
        $this->showResponseHeader(false);

        // 设置是否自动重定向
        $this->isAutoRedirect(true);

        // 设置是否直接输出结果
        $this->isDirectExport(false);
    }

    /**
     * 初始化 CURL
     * @param  mixed  $cookie 开关/文件名
     * @return object
     */
    public static function init($cookie = false)
    {
        return new self($cookie);
    }

    /**
     * 启用Cookie
     * @param  mixed $cookie cookie开关/文件名
     * @return void
     */
    public function enableCookie($cookie)
    {
        // 生成 Cookie 存储文件
        if (!is_bool($cookie) && !is_string($cookie)) {
            throw new \Exception('[Cookie启用]参数格式错误');
        } elseif ($cookie) {
            // 启用Cookie
            $path     = RUNTIME_PATH . 'cookies' . DS;
            $file     = ($cookie !== true) ? $cookie : 'auto-cookie';
            $filePath = $path . $file;
        } else {
            // 禁用Cookie
            return false;
        }
        // 根据 Cookie 文件是否存在做不同操作
        if (file_exists($filePath)) {
            curl_setopt($this->handle, CURLOPT_COOKIEFILE, $filePath);
        } else {
            curl_setopt($this->handle, CURLOPT_COOKIEJAR, $filePath);
        }
    }

    /**
     * Cookie 文件移除
     * @param  string $cookieFileName Cookie文件名
     * @return void
     */
    public function removeCookie($cookieFileName)
    {
        // 检查Cookie文件名
        if (empty($cookieFileName) || !is_string($cookieFileName)) {
            throw new \Exception('[Cookie移除]Cookie文件名要求为非空字符串');
        }
        // 检查Cookie文件是否存在
        $filePath = RUNTIME_PATH . 'cookies' . DS . $cookieFileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 设置请求协议
     * @param  string $protocol 请求协议
     * @return void
     */
    public function setRequestProtocol($protocol)
    {
        if ($protocol == 'https') {
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, 2);
        } elseif ($protocol != 'http') {
            throw new \Exception('暂不支持协议:' . $protocol);
        }
    }

    /**
     * 设置请求地址
     * @param  string|array $url 请求地址
     * @return void
     */
    public function setRequestUrl($url)
    {
        // $url 为数组类型时进行重构
        if (is_array($url)) {
            // 检查参数完整性
            if (!isset($url['host']) || !isset($url['params'])) {
                throw new \Exception('参数{url}为数组类型时必须包含{host}和{params}');
            } else {
                $url = $this->buildUrl($url['host'], $url['params']);
            }
        }
        
        // 检查并设置请求地址
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('无效的请求地址:' . $url);
        } else {
            curl_setopt($this->handle, CURLOPT_URL, $url);
        }

        // 设置请求协议
        $protocol = (substr($url, 0, 5) == 'https') ? 'https' : 'http';
        $this->setRequestProtocol($protocol);
    }

    /**
     * 设置请求头
     * @param  array $headers 请求头
     * @return void
     */
    public function setRequestHeader($headers)
    {
        // 检查参数合法性
        if (empty($headers) || !is_array($headers)) {
            throw new \Exception('参数{headers}要求为非空数组');
        }
        // 设置请求头
        $this->requestHeader = array_merge($this->requestHeader, $headers);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->requestHeader);
    }

    /**
     * 设置请求数据
     * @param  mixed $data 请求数据
     * @param  bool  $post POST请求
     * @return void
     */
    public function setRequestData($data, $post = true)
    {
        if ($post) {
            // 设置请求数据
            curl_setopt($this->handle, CURLOPT_POST, true);
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * 设置请求时携带点Cookie信息
     * @param  mixed $cookie cookie数据
     * @return void
     */
    public function setRequestCookie($cookie)
    {
        // 检查参数合法性
        if (is_array($cookie)) {
            // 重构cookie参数
            foreach ($cookie as $key => $value) {
                array_push($cookie, $key . '=' . $value);
                unset($cookie[$key]);
            }
            $cookie = implode('; ', $cookie);
        } elseif (!is_string($cookie)) {
            throw new \Exception('[Cookie设置]参数{cookie}仅支持数组和字符串');
        }
        // 设置cookie
        if (empty($cookie)) {
            throw new CurlException('[Cookie设置]参数{cookie}不可以为空');
        } else {
            curl_setopt($this->handle, CURLOPT_COOKIE, $cookie);
        }
    }

    /**
     * 批量设置CURL选项
     * @param  array $opt
     * @return void
     */
    public function setRequestOptWithBatch($opt)
    {
        if (empty($opt) || !is_array($opt)) {
            throw new \Exception('批量设置CURL选项时，参数{opt}须为非空数组');
        } else {
            curl_setopt_array($this->handle, $opt);
        }
    }

    /**
     * 设置请求IP
     * @param  string $ip 请求IP
     * @return void
     */
    public function setRequestIp(&$ip = null)
    {
        $ip = $ip ?: $this->getRandsIp();
        $this->requestHeader = array_merge($this->requestHeader, [
            'CLIENT-IP:' . $ip,
            'X-FORWARDED-FOR:' . $ip
        ]);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->requestHeader);
    }

    /**
     * 获取HTTP状态码
     * @return integer
     */
    public function getHttpCode()
    {
        return curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
    }
/**
     * 获取发生异常后点错误信息
     * @return string
     */
    public function getError()
    {
        return curl_error($this->handle);
    }

    /**
     * 获取请求头信息
     * @return array
     */
    public function getRequestHeader()
    {
        if ($this->showRequestHeader >= 2) {
            $headers = curl_getinfo($this->handle, CURLINFO_HEADER_OUT);
            $headers = explode("\r\n", rtrim($headers, "\r\n"));
            if ($this->showRequestHeader == 3) {
                $headers = $this->formatHeaders($headers);
            }
            return $headers;
        }
        return [];
    }

    /**
     * 设置是否显示请求头
     * @param  boolean $opt 设置内容
     * @return object
     */
    public function showRequestHeader(bool $show = false, bool $format = true)
    {
        $this->showRequestHeader = bindec((int) $show . (int) $format);

        curl_setopt($this->handle, CURLINFO_HEADER_OUT, $show);

        return $this;
    }

    /**
     * 设置是否显示响应头
     * @param  boolean $show   是否显示
     * @param  boolean $format 是否格式化
     * @return object
     */
    public function showResponseHeader(bool $show = false, bool $format = true)
    {
        $this->showResponseHeader = bindec((int) $show . (int) $format);
        
        curl_setopt($this->handle, CURLOPT_HEADER, $show);

        return $this;
    }

    /**
     * 设置是否自动重定向
     * @param  boolean $opt 设置内容
     * @return object
     */
    public function isAutoRedirect($opt = false)
    {
        curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, (bool) $opt);

        return $this;
    }

    /**
     * 设置是否直接输出数据
     * @param  boolean $opt 设置内容
     * @return object
     */
    public function isDirectExport($opt = false)
    {
        $opt = (bool) $opt;

        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, !$opt);

        return $this;
    }

    /**
     * 发送 CURL 请求
     * @param  string $url      请求地址
     * @param  mixed  $data     请求数据
     * @param  bool   $post     请求方式
     * @return mixed
     */
    public function send($url, $data = null, $post = false)
    {
        // 设置请求地址、请求数据
        $post = $post ?: (is_null($data) ? false : true);
        $this->setRequestUrl($url);
        $this->setRequestData($data, $post);

        // 发送 CURL 请求
        $result = curl_exec($this->handle);
        if ($this->showResponseHeader >= 2) {
            list($headers, $response) = explode("\r\n\r\n", $result, 2);
            $responseHeader = explode("\r\n", $headers);
            if ($this->showResponseHeader == 3) {
                // 格式化header
                $responseHeader = $this->formatHeaders($responseHeader);
            }
        } else {
            $response = $result;
            $responseHeader = [];
        }

        // 返回结果
        return [
            'code'   => $this->getHttpCode(),
            'header' => [
                'request'  => $this->getRequestHeader(),
                'response' => $responseHeader,
            ],
            'data'   => $response
        ];
    }

    /**
     * 重置 CURL 操作句柄
     * @return void
     */
    public function resetCurlHandle()
    {
        curl_reset($this->handle);
    }

    /**
     * 生成请求地址
     * @param  string $host   主机
     * @param  array  $params 参数
     * @return string
     */
    private function buildUrl($host, $params)
    {
        // 参数params为字符串类型
        if (is_string($params)) {
            $params = trim($params, '?/');
            $params = explode('&', $params);
            foreach ($params as $index => $param) {
                list($key, $value) = explode('=', $param);
                unset($params[$index]);
                $params[$key] = $value;
            }
        }
        // 检查params是否为数组类型
        if (!is_array($params)) {
            throw new \Exception('[curl]参数{param}仅支持数组和字符串类型');
        }
        // 格式化并序列化请求参数
        ksort($params);
        foreach ($params as $key => $value) {
            array_push($params, "{$key}={$value}");
            unset($params[$key]);
        }
        // 生成URL
        $params = implode('&', $params);
        return rtrim($host, '?') . '?' . $params;
    }

    /**
     * 获取随机IP
     * @return string
     */
    private function getRandsIp()
    {
        for ($i=0; $i < 4; $i++) { 
            $ip[$i] = mt_rand(1, 255);
        }
        return implode('.', $ip);
    }

    /**
     * 格式化header
     * @param  array $headers HTTP头部信息
     * @return array
     */
    private function formatHeaders(array $headers)
    {
        foreach ($headers as $index => $header) {
            unset($headers[$index]);
            if (strpos($header, ':') !== false) {
                list($key, $value) = explode(':', $header, 2);
                $headers[$key] = trim($value, ' ');
            }
        }
        return $headers;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        curl_close($this->handle);
    }
}
