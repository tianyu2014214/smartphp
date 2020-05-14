<?php

namespace smart\db\driver;

use smart\db\Driver;

class Mysql extends Driver
{
    /**
     * 解析pdo连接的dsn信息
     * @param  array  $config 配置信息
     * @return string
     */
    protected function parseDsn($config)
    {
        // 连接协议
        $driver   = 'mysql:';
        // 主机地址
        $hostname = "host={$config['hostname']};";
        // 主机端口
        $hostport = "port={$config['hostport']};";
        // 默认数据库
        $database = "dbname={$config['database']};";
        // 默认编码方式
        $charset  = "charset={$config['charset']}";
        // 返回分析结果
        return $driver . $hostname . $hostport . $database . $charset;
    }

    /**
     * 字段和表名处理
     * @param  mixed  $key   字段或表名
     * @param  string $alias 字段或表别名
     * @return void
     */
    protected function parseKey(string $key, $alias = '')
    {
        // 检查KEY是否符合规则
        if (!preg_match('/^[\w\.\*]+$/', $key)) {
            throw new \Exception('[database][parseKey]no suport key:' . $key);
        }
        // 处理表名和字段名
        $alias = $alias ? ' '.strapend($alias, '`') : '';
        if (strpos($key, '.')) {
            // 指定数据库或表名
            $key = strpos($key, '*') ? '`'.str_replace('.', '`.', $key) : '`'.str_replace('.', '`.`', $key).'`';
        } else {
            // 未指定数据库和表名
            $key = strapend($key, '`');
        }
        // 返回结果
        return $key . $alias;
    }

    /**
     * 分析聚合函数
     * @param  string $funcstr 聚合函数
     * @param  string $alias   函数别名
     * @return string
     */
    protected function parseAggregate(string $funcstr, $alias = '')
    {
        $pattern = '/^([a-z]+\()([\w\.\*]+)(\))$/';
        $param   = preg_replace($pattern, '${2}', $funcstr);
        $param   = ($param != '*') ? $this->parseKey($param) : $param;
        $func    = preg_replace($pattern, '${1}' . $param . '$3', $funcstr);
        $alias   = $alias ? ' '.strapend(trim($alias, '`'), '`') : '';
        return $func . $alias;
    }
}
