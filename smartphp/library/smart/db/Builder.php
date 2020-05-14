<?php

namespace smart\db;

/**
 * SQL构造器
 */
class Builder
{
    // 查询选项
    protected $options = [
        'table'  => null,
        'field'  => null,
        'where'  => null,
        'join'   => null,
        'group'  => null,
        'order'  => null,
        'having' => null,
        'limit'  => null,
    ];

    // 是否仅返回SQL
    protected $fetchSql = false;

    // 执行读SQL
    protected $sql = '';

    // 查询条件
    protected $where = '';

    /**
     * 分析连接查询JOIN表名
     * @param  string $table 表名
     * @return string
     */
    protected function parseJoinTable(string $table)
    {
        @list($name, $alias) = explode(' ', trim($table, ' '));
        if (is_null($alias)) {
            $alias = (strpos($name, '.') !== false) ? explode('.', $name)[1] : $name;
        }
        return $this->parseKey($name, $alias);
    }

    /**
     * 分析连接查询JOIN条件
     * @param  mixed $conditions 关联条件
     * @return string
     */
    protected function parseJoinCondition($conditions)
    {
        $conditions = (array) $conditions;
        foreach ($conditions as $index => $condition) {
            // 检查是否关联字段
            if (strpos($condition, '=') !== false) {
                list($key, $value) = explode('=', $condition, 2);
                $key   = $this->parseKey(trim($key, ' '));
                $value = $this->parseKey(trim($value, ' '));
                $conditions[$index] = $key . ' = ' . $value;
            }
        }
        return implode(' AND ', $conditions);
    }

    /**
     * 分析查询条件
     * @param  mixed  $field     字段
     * @param  string $op        运算符
     * @param  mixed  $condition 条件
     * @return string
     */
    protected function parseWhere($field, $op, $condition)
    {
        // field为闭包函数
        if ($field instanceof \Closure) {
            // 生成并返回查询子条件
            $query = new driver\Mysql;
            call_user_func_array($field, [&$query]);
            return empty($op) ? '( '.$query->where.' )' : strapend(strtoupper($op), ' ') . '( ' . $query->sql . ' )';
        }

        // condition为闭包函数
        if ($field instanceof \Closure) {
            // 生成并返回子SQL
            $query = new driver\Mysql;
            call_user_func_array($field, [&$query]);
            if ($this->fetchSql) {
                $condition = '( ' . $query->sql . ' )';
                return $this->parseKey($field) . strapend(strtoupper($op), ' ') . $condition;
            } else {
                return '( ' . $this->where . ' )';
            }
        }

        // 分析查询条件
        if (is_array($field)) {
            // 批量设置
            $wherelist = '';
            foreach ($field as $where) {
                // 校验参数完整性
                if (is_string($where) || count($where) < 2) {
                    throw new \Exception('[database][parseWhere]参数{field}为数组类型时，子元素须为长度为2的非空数组');
                }
                //剥离参数，分析查询子条件
                if (is_array($where[1])) {
                    // 单个字段多个条件
                    foreach ($where[1] as $whereitem) {
                        // 判断条件是否符合规则
                        if (is_array($whereitem)) {
                            $op         = $whereitem[0];
                            $whereKey   = $where[0];
                            $whereValue = isset($whereitem[1]) ? $whereitem[1] : '';
                            $logic      = isset($whereitem[2]) ? $whereitem[2] : 'AND';
                            $wherestr   = $this->parseWhereItem($whereKey, $op, $whereValue);
                            $wherelist  = empty($wherelist) ? $wherestr : "{$wherelist} {$logic} {$wherestr}";
                        }
                    }
                } else {
                    // 单个字段单个条件
                    $op         = $where[1];
                    $whereKey   = $where[0];
                    $whereValue = isset($where[2]) ? $where[2] : '';
                    $logic      = isset($where[3]) ? $where[3] : 'AND';
                    $wherestr   = $this->parseWhereItem($whereKey, $op, $whereValue);
                    $wherelist  = empty($wherelist) ? $wherestr : "{$wherelist} {$logic} {$wherestr}";
                }
            }
            return $wherelist;
        } else {
            // 单个设置
            return empty($op) ? $field : $this->parseWhereItem($field, $op, $condition);
        }
    }

    /**
     * 分析查询子条件
     * @param  mixed  $field     字段
     * @param  string $op        运算符
     * @param  mixed  $condition 条件
     * @return string
     */
    protected function parseWhereItem($field, $op, $condition)
    {
        // 处理字段名和操作运算符
        $op    = strtoupper($op);
        $field = $this->parseKey($field);

        // 根据操作运算符的不同执行不同操作
        $cop = str_replace(' ', '', explode('.', $op)[0]);
        if ($cop == 'LIKE' || $cop == 'NOTLIKE') {
            // 操作符LIKE 或 NOT LIKE
            if (strpos($op, '.') !== false) {
                $op  = ($cop == 'LIKE') ? 'LIKE' : 'NOT LIKE';
                $pos = strtolower(explode('.', $op)[1]);
            } else {
                $op  = ($cop == 'LIKE') ? 'LIKE' : 'NOT LIKE';
                $pos = 'both';
            }
            $fieldValue = $this->escapeLikeString($condition, $pos);
            return "{$field} {$op} {$fieldValue}";

        } elseif ($cop == 'IN' || $cop == 'NOTIN') {
            // 操作符 IN 或 NOT IN
            if (is_array($condition)) {
                $condition  = $this->escapeString($condition);
                $fieldValue = implode(', ', $condition);
                return "{$field} {$op} ( {$fieldValue} )";
            } else {
                throw new \Exception('[database][parseWhereItem]参数{condition}为IN或NOT IN时要求其类型为数组');
            }

        } elseif ($cop == 'BETWEEN' || $cop == 'NOTBETWEEN') {
            // 操作符BETWEEN 或 NOT BETWEEN
            if (!is_array($condition) || count($condition) != 2) {
                throw new \Exception('[database][parseWhereItem]参数{op}为BETWEEN 或 NOT BETWEEN时，参数{condition}要求为长度为2的数组');
            }
            // 判断条件所属类型
            if (is_int($condition[0]) && is_int($condition[1])) {
                $min = $condition[0];
                $max = $condition[1];
            } else {
                $min = $this->escapeString($condition[0]);
                $max = $this->escapeString($condition[1]);
            }
            return "{$field} {$op} {$min} AND {$max}";

        } elseif ($cop == 'ISNULL' || $cop == 'ISNOTNULL') {
            // 操作符IS NULL 或 IS NOT NULL
            return "{$field} {$op}";

        } elseif ($cop == 'EXISTS' || $cop == 'NOTEXISTS') {
            // 操作符EXISTS 或 NOT EXISTS
            throw new \Exception('[database][parseWhereItem]参数{op}为EXISTS时，字段{field}必须为闭包函数');

        } else {
            // 检查条件是否为字段
            if (substr($op, 0, 1) == ':') {
                $op = substr($op, 1);
                $fieldValue = $this->parseKey($condition);
                return "{$field} {$op} {$fieldValue}";
            } else {
                // 检查查询条件是否为空字符串
                if ($condition === '' || $condition === null) {
                    return "{$field} IS NULL";
                } else {
                    $fieldValue = is_int($condition) ? $condition : $this->escapeString($condition);
                    return "{$field} {$op} {$fieldValue}";
                }
            }
        }
    }

    /**
     * 分析更新字段名
     * @param  array  $fields 待更新字段
     * @return string
     */
    protected function parseFields(array $fields)
    {
        foreach ($fields as $index => $field) {
            $fields[$index] = $this->parseKey($field);
        }
        return implode(',', $fields);
    }

    /**
     * 特殊字符转义（非模糊模式）
     * @param  mixed $data 待转义数据
     * @return mixed
     */
    protected function escapeString($data)
    {
        $this->initConnect(false);
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = is_int($value) ? $value : $this->linkID->quote($value);
            }
        } else {
            $data = is_int($data) ? $data : $this->linkID->quote($data);
        }
        return $data;
    }

    /**
     * 特殊字符转义（模糊模式）
     * @param  mixed  $data 待转义数据
     * @param  string $pos  百分号位置
     * @return mixed
     */
    protected function escapeLikeString($data, $pos = 'both')
    {
        $this->initConnect(false);
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->linkID->quote(strapend($value, '%'));
            }
        } else {
            $data = $this->linkID->quote(strapend($data, '%', $pos));
        }
        return $data;
    }

    /**
     * 生成查询SQL
     * @return string
     */
    protected function buildQuerySql()
    {
        // 数据准备
        $this->options['field']   = $this->options['field'] ?: '*';
        $this->options['where']   = $this->options['where'] ?: ' WHERE 1';
        $this->options['locking'] = $this->locking ? ' FOR UPDATE' : '';
        // 生成查询SQL
        return "SELECT {$this->options['field']} FROM {$this->options['table']}"
             . "{$this->options['join']}"
             . "{$this->options['where']}"
             . "{$this->options['group']}"
             . "{$this->options['having']}"
             . "{$this->options['order']}"
             . "{$this->options['limit']}"
             . "{$this->options['locking']};";
    }
}
