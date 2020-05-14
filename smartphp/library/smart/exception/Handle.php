<?php

namespace smart\exception;

use smart\App;
use smart\Log;
use smart\Config;
use smart\Response;

class Handle
{
    protected $render;
    protected $ignoreReport = [
        '\smart\exception\HttpException',
    ];

    public function setRender($render)
    {
        $this->render = $render;
    }

    /**
     * 将错误信息记录到日志
     * @param  \Exception $exception
     * @return void
     */
    public function report(\Exception $exception)
    {
        if (!$this->isIgnoreReport($exception)) {
            // 收集异常数据
            $data = [
                'code'    => $this->getCode($exception),
                'message' => $this->getMessage($exception),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine()
            ];
            $log  = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            Log::instance()->write('error', $log);
        }
    }

    /**
     * 将错误信息响应到客户端
     * @param  \Exception $exception
     * @param  boolean    $directOutput
     * @return void
     */
    public function render(\Exception $exception, $directOutput = true)
    {
        // 自定义异常输出
        if ($this->render && $this->render instanceof \Closure) {
            $result = call_user_func_array($this->render, [$exception]);
            if ($result) {
                return $directOutput ? (Response::json($result)) : $result;
            }
        }
        // 系统内置异常输出程序
        if ($exception instanceof \smart\exception\HttpException) {
            return $this->renderHttpException($exception, $exception->getStatusCode(), $directOutput);
        } else {
            return $this->renderHttpException($exception, 500, $directOutput);
        }
    }

    /**
     * 是否忽略记录日志
     * @param  \Exception $exception
     * @return boolean
     */
    protected function isIgnoreReport(\Exception $exception)
    {
        foreach ($this->ignoreReport as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * 响应HTTP异常
     * @param  HttpException $exception
     * @return void
     */
    protected function renderHttpException($exception, $status, $directOutput = true)
    {
        // 收集异常数据
        $data = [
            'name'    => get_class($exception),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'code'    => $this->getCode($exception),
            'message' => $this->getMessage($exception),
            'trace'   => $exception->getTrace(),
            'datas'   => $this->getExtendData($exception),
            'request' => [
                'GET'                 => $_GET,
                'POST'                => $_POST,
                'Files'               => $_FILES,
                'Cookies'             => $_COOKIE,
                'Session'             => isset($_SESSION) ? $_SESSION : [],
                'Server/Request Data' => $_SERVER,
            ],
        ];
        // 记录运行过程出现的异常信息
        if (RUN_ENV == 'production') {
            unset($data['request']['Server/Request Data']);
            Log::instance()->write('error', json($data), 'run');
        }
        // 部署模式下信息显示
        if (!Config::get('app_debug')) {
            // 调试模式，获取详细的错误信息
            $data = Config::get('show_error_msg') ? [
                // 显示错误信息
                'message' => $data['message']
            ] : [
                // 隐藏错误信息
                'message' => Config::get('error_message')
            ];
        }
        // 数据输出
        return $directOutput ? (Response::json($data, $status)) : $data;
    }

    /**
     * 获取错误编码
     * ErrorException使用错误级别作为错误编码
     * @param  \Exception $exception
     * @return integer
     */
    protected function getCode(\Exception $exception)
    {
        $code = $exception->getCode();
        if (!$code && $exception instanceof ErrorException) {
            $code = $exception->getSeverity();
        }
        return $code;
    }

    /**
     * 获取错误信息
     * @param  \Exception $exception
     * @return string
     */
    protected function getMessage(\Exception $exception)
    {
        return $exception->getMessage();
    }

    /**
     * 获取异常扩展信息
     * @param  \Exception $exception
     * @return array
     */
    protected function getExtendData(\Exception $exception)
    {
        $data = [];
        if ($exception instanceof \smart\Exception) {
            $data = $exception->getData();
        }
        return $data;
    }
}
