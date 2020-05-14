<?php

namespace smart;

class Validate
{
    // 实例对象
    private static $instance;

    // 校验规则
    private $rules;

    // 校验不通过提示信息
    private $messages = ['default' => '数据校验不通过'];

    // 校验类型别名
    private $alias = [
        '>'  => 'gt',
        '>=' => 'egt',
        '<'  => 'lt',
        '<=' => 'elt',
        '='  => 'eq'
    ];

    // 校验规则提示信息
    private $typeMessage = [
        'accepted'   => ':attribute只接受yes、on或1',
        'alpha'      => ':attribute只可填写字母',
        'alphaNum'   => ':attribute只可填写字母和数字',
        'alphaDash'  => ':attribute只可填写字母、数字、下划线和破折号',
        'array'      => ':attribute要求位数组类型',
        'activeUrl'  => ':attribute请填写有效的网址',
        'between'    => ':attribute范围必须在:1~:2',
        'boolean'    => ':attribute仅接受1或0',
        'chars'      => ':attribute中包含非法字符',
        'chs'        => ':attribute只可填写汉字',
        'chsAlpha'   => ':attribute只可填写汉字和字母',
        'chsAlphaNum'=> ':attribute只可填写汉字、字母和数字',
        'chsDash'    => ':attribute只可填写汉字、字母、数字、下划线、破折号和空格',
        'date'       => ':attribute要求为日期类型',
        'email'      => '无效的邮箱地址',
        'elt'        => ':attribute要求不大于:1',
        'eq'         => ':attribute要求等于:1',
        'egt'        => ':attribute要求不小于:1',
        'float'      => ':attribute请填写小数',
        'gt'         => ':attribute要求大于:1',
        'get'        => ':attribute字段要求以GET方式提交',
        'in'         => ':attribute不符合要求',
        'idcard'     => '无效的身份证号码',
        'integer'    => ':attribute请填写整数',
        'ip'         => ':attribute请填写有效的IP地址',
        'json'       => ':attribute要求为json',
        'lt'         => ':attribute要求小于:1',
        'length'     => ':attribute的长度要求为:1',
        'min'        => ':attribute长度不得低于:1个字符',
        'max'        => ':attribute长度不得高于:1个字符',
        'number'     => ':attribute请填写数字',
        'notIn'      => ':attribute不符合要求',
        'notBetween' => ':attribute范围不得在:1~:2',
        'password'   => ':attribute中含有非法字符',
        'post'       => ':attribute字段要求以POST方式提交',
        'phone'      => '无效的手机号码',
        'require'    => ':attribute不可以为空',
        'regex'      => ':attribute数据格式有误',
        'route'      => '无效的路由',
        'switch'     => ':attribute字段只接受on或off',
        'url'        => ':attribute请填写有效的网址',
    ];

    /**
     * 构造函数
     * @param  array $rules   校验规则
     * @param  array $message 校验提示信息
     */
    public function __construct($rules, $message = [])
    {
        $this->rules    = $rules;
        $this->messages = array_merge($this->messages, $message);
    }

    /**
     * 实例化验证类
     * @param  array $rules   校验规则
     * @param  array $message 校验提示信息
     * @param  bool  $sington 是否单例模式
     * @return Validate
     */
    public static function make($rules, $message = [], $sington = true)
    {
        // 检查是否为单例模式
        if (!$sington) {
            // 非单例模式
            return new self($rules, $message);
        }
        // 单例模式
        if (is_null(self::$instance)) {
            // 不存在实例则实例化
            self::$instance = new self($rules, $message);
        } else {
            // 存在实例则初始化校验规则和校验信息
            self::$instance->rules    = $rules;
            self::$instance->messages = $message;
        }
        return self::$instance;
    }

    /**
     * 校验数据合法性
     * @param  array $datas 待校验数据
     * @param  array $rules 校验规则
     * @return bool
     */
    public function check($datas, $rules = [], $messages = [])
    {
        // 遍历校验规则
        $rules = $rules ?: $this->rules;
        $this->messages = array_merge($this->messages, $messages);
        foreach ($rules as $field => $rule) {
            $rule = is_string($rule) ? explode('|', $rule) : $rule;
            $data = isset($datas[$field]) ? $datas[$field] : '';
            // 非必传字段
            if (!in_array('require', $rule) && empty($data)) {
                continue;
            }
            // 校验数据
            if (!$this->checkItem($field, $data, $rule)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 添加校验规则
     * @param  mixed  $field 校验字段(类型为数组时表示批量添加)
     * @param  string $rule  校验规则
     * @return Validate
     */
    public function rule($field, $rule = '')
    {
        if (is_array($filed)) {
            // 批量添加校验规则
            $this->rules = array_merge($this->rules, $filed);
        } else {
            // 单个添加校验规则
            $this->rules[$field] = $rule;
        }

        return $this;
    }

    /**
     * 添加校验提示信息
     * @param  mixed  $field   校验字段(类型为数组时表示批量添加)
     * @param  string $message 校验提示信息
     * @return Validate
     */
    public function message($field, $message = '')
    {
        if (is_array($field)) {
            // 批量添加校验提示信息
            $this->messages = array_merge($this->messages, $field);
        } else {
            // 单个添加校验提示信息
            $this->messages[$field] = $message;
        }

        return $this;
    }

    /**
     * 获取校验不通过时的错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 校验单个字段数据
     * @param  string $field 字段名
     * @param  string $data  待校验数据
     * @param  array  $rules 验证规则
     * @return bool
     */
    private function checkItem($field, $data, $rules)
    {
        // 遍历校验规则
        $rules = (array) $rules;
        foreach ($rules as $rule) {
            // 校验规则['phone'=>'require|phone']
            if (strpos($rule, ':') === false) {
                $result = $this->is($field, $rule, $data);
                if (!$result) {
                    $this->setError($field, $rule);
                    return false;
                }
            }
            // 校验规则['wage'=>'>:8000']
            else {
                list($ruleKey, $ruleValue) = explode(':', $rule);
                $func   = isset($this->alias[$ruleKey]) ? $this->alias[$ruleKey] : $ruleKey;
                $result = $this->$func($ruleValue, $data);
                if (!$result) {
                    $this->setError($field, $func, $ruleValue);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 判断指定字段是否符合要求
     * @param  string $field 字段名
     * @param  string $rule  校验规则
     * @param  mixed  $data  待校验数据
     * @return bool
     */
    private function is($field, $rule, $data)
    {
        switch ($rule) {
            // 仅接受yes, on, 1
            case 'accepted':
                $result = in_array($data, ['1', 'on', 'yes']);
                break;
            // 只允许字母
            case 'alpha':
                $result = $this->regex('/^[A-Za-z]+$/', $data);
                break;
            // 只允许字母和数字
            case 'alphaNum':
                $result = $this->regex('/^[A-Za-z0-9]+$/', $data);
                break;
            // 只允许字母、数字、下划线和破折号
            case 'alphaDash':
                $result = $this->regex('/^[A-Za-z0-9\-\_]+$/', $data);
                break;
            // 是否为有效网址
            case 'activeUrl':
                $result = checkdnsrr($data);
                break;
            // 是否为数组类型
            case 'array':
                $result = is_array($data);
                break;
            // bool
            case 'boolean':
                $result = in_array($data, [1, 0]);
                break;
            // 只允许汉字
            case 'chs':
                $result = $this->regex('/^[\x{4e00}-\x{9fa5}]+$/u', $data);
                break;
            // 只允许汉字、字母
            case 'chsAlpha':
                $result = $this->regex('/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u', $data);
                break;
            // 只允许汉字、字母和数字
            case 'chsAlphaNum':
                $result = $this->regex('/^[\x{4e00}-\x{9fa5}A-Za-z0-9]+$/u', $data);
                break;
            // 只允许汉字、字母、数字、下划线和破折号
            case 'chsDash':
                $result = $this->regex('/^[\x{4e00}-\x{9fa5}A-Za-z0-9\-\_ ]+$/u', $data);
                break;
            // 排除特定字符
            case 'chars':
                $result = !$this->regex('/[\$\'\"]/', $data);
                break;
            // 日期类型
            case 'date':
                $result = $this->regex('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $data);
                break;
            // 日期时间类型
            case 'datetime':
                $result = $this->regex('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})$/', $data);
                break;
            // 邮箱类型
            case 'email':
                $result = filter_var($data, FILTER_VALIDATE_EMAIL);
                break;
            // float类型
            case 'float':
                $result = filter_var($data, FILTER_VALIDATE_FLOAT);
                break;
            // 整数类型
            case 'integer':
                $result = filter_var($data, FILTER_VALIDATE_INT) || $data == '0';
                break;
            // 是否为IP
            case 'ip':
                $result = filter_var($data, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6]);
                break;
            // 是否为身份证号
            case 'idcard':
                $result = \smart\validate\Idcard::check($data);
                break;
            // 验证是否为json
            case 'json':
                $result = json_decode($data);
                $result = $result !== null && $result !== FALSE;
                break;
            // 是否为数字
            case 'number':
                $result = is_numeric($data);
                break;
            // 验证是否为手机号
            case 'phone':
                $result = $this->regex('/^1([358][0-9]|4[579]|66|7[135678]|9[89]){1}\d{8}$/', $data);
                break;
            // 密码类型
            case 'password':
                $result = $this->regex('/^[\w!@#%^&*.]*$/', $data);
                break;
            // 是否为post请求
            case 'post':
                $result = Request::instance()->isPost($field);
                break;
            // 必选字段
            case 'require':
                $result = !empty($data) || $data == '0';
                break;
            // 是否为路由
            case 'route':
                $result1 = preg_match('/^(\/[a-zA-Z0-9\-\_]+)+$/', $data);
                $result2 = preg_match('/^\/{1}$/', $data);
                $result  = $result1 || $result2;
                break;
            // 开关
            case 'switch':
                $result = in_array($data, ['on', 'off']);
                break;
            // 是否为一个URL地址
            case 'url':
                $result = filter_var($data, FILTER_VALIDATE_URL);
                break;
            // 默认抛出异常
            default:
                throw new \Exception('illegal validate rule of is()');
                break;
        }
        return $result;
    }

    /**
     * 正则校验
     * @param  string $rule 校验规则
     * @param  string $data 待校验数据
     * @return bool
     */
    private function regex($rule, $data)
    {
        $rule = str_replace('{or}', '|', $rule);
        $rule = str_replace('{colon}', ':', $rule);
        return (bool) preg_match($rule, $data);
    }

    /**
     * 校验是否大于某个值
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function gt($rule, $data)
    {
        return $data > $rule;
    }

    /**
     * 校验是否大于等于某个值
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function egt($rule, $data)
    {
        return $data >= $rule;
    }

    /**
     * 校验是否小于某个值
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function lt($rule, $data)
    {
        return $data < $rule;
    }

    /**
     * 校验是否小于等于某个值
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function elt($rule, $data)
    {
        return $data <= $rule;
    }

    /**
     * 校验是否等于某个值
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function eq($rule, $data)
    {
        return $data == $rule;
    }

    /**
     * 校验数据长度
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function length($rule, $data)
    {
        $length = mb_strlen($data, 'utf-8');
        if (strpos($rule, ',')) {
            // 长度区间
            list($min, $max) = explode(',', $rule);
            return $length >= $min && $length <= $max;
        } else {
            // 指定长度
            return $length == $rule;
        }
    }

    /**
     * 校验数据最小长度
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function min($rule, $data)
    {
        $length = mb_strlen($data, 'utf-8');

        return $length >= $rule;
    }

    /**
     * 校验数据最小长度
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function max($rule, $data)
    {
        $length = mb_strlen($data, 'utf-8');

        return $length <= $rule;
    }

    /**
     * 校验某值是否介于两个值之间
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function between($rule, $data)
    {
        list($min, $max) = @explode(',', $rule);

        return $data >= $min && $data <= $max;
    }

    /**
     * 校验某值是否不介于两个值之间
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function notBetween($rule, $data)
    {
        return !$this->between($rule, $data);
    }

    /**
     * 校验是否在范围内
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function in($rule, $data)
    {
        return in_array($data, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * 校验是否不在范围内
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function notIn($rule, $data)
    {
        return !$this->in($rule, $data);
    }

    /**
     * 校验是否为合格的域名或者IP
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function activeUrl($rule, $data)
    {
        $rule = in_array($rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY']) ? $rule : 'MX';
        
        return checkdnsrr($data, $rule);
    }

    /**
     * 验证是否有效IP
     * @param  string $rule 规则值
     * @param  string $data 待校验数据
     * @return bool
     */
    private function ip($rule, $data)
    {
        $rule = in_array($rule, ['ipv4', 'ipv6']) ? $rule : 'ipv4';

        return $this->filter($data, [FILTER_VALIDATE_IP, 'ipv4' == $rule ? FILTER_FLAG_IPV4 : FILTER_FLAG_IPV6]);
    }

    /**
     * 设置校验不通过提示信息
     * @param  string $field     校验字段
     * @param  string $rule      规则名
     * @param  string $ruleValue 规则值
     * @return void
     */
    private function setError($field, $rule = '', $ruleValue = '')
    {
        if (isset($this->typeMessage[$rule])) {
            // 加载错误信息模版
            @list($rule1, $rule2) = explode(',', $ruleValue, 2);
            $field = isset($this->messages[$field]) && !in_array($rule, ['post', 'get']) ? $this->messages[$field] : $field;
            $error = $this->typeMessage[$rule];
            $error = str_replace(':attribute', $field, $error);
            $error = str_replace(':1', $rule1, $error);
            $error = str_replace(':2', $rule2, $error);
        } else {
            // 默认提示信息
            $error = '数据校验不通过';
        }
        $this->error = $error;
    }
}
