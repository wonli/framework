<?php
/**
 * @Auth wonli <wonli@live.com>
 * Class MysqlModel
 */

class MysqlModel implements SqlInterface
{
    /**
     * @var $stmt
     */
    public $stmt;

    /**
     * @var PDO
     */
    public $pdo;

    /**
     * @var 数据库连接实例
     */
    private static $instance;

    /**
     * 创建数据库连接
     * <ul>
     * <li>PDO::ATTR_PERSISTENT => false //禁用长连接</li>
     * <li>PDO::ATTR_EMULATE_PREPARES => false //禁用模拟预处理</li>
     * <li>PDO::ATTR_STRINGIFY_FETCHES => false //禁止数值转换成字符串</li>
     * <li>PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true //使用缓冲查询</li>
     * <li>PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //发生错误时抛出异常 </li>
     * <li>PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" </li>
     * </ul>
     *
     * @param $dsn dsn
     * @param $user 数据库用户名
     * @param $password 数据库密码
     * @throws CoreException
     */
    private function __construct( $dsn, $user, $password )
    {
        try{
            $this->pdo = new PDO($dsn, $user, $password, array(
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ));
        } catch(Exception $e) {
            throw new CoreException($e->getMessage().' line:'.$e->getLine().' '.$e->getFile());
        }
    }

    /**
     * @see MysqlModel::__construct
     *
     * @param $dsn
     * @param $user
     * @param $password
     * @return mixed
     */
    static function getInstance( $dsn, $user, $password )
    {
        //同时建立多个数据库连接
        $_instance =  md5($dsn.$user.$password);
        if(! isset( self::$instance[ $_instance ]) )
        {
            self::$instance [$_instance] = new self($dsn, $user, $password);
        }

        return self::$instance [$_instance];
    }

    /**
     * prepare方式单条查询
     * @param $table 表名称
     * @param $fields 需要返回的字段
     * @param $where 查询条件
     * @return mixed
     */
    function get($table, $fields, $where)
    {
        return $this->prepare_getone($table, $fields, $where);
    }

    /**
     * 返回所有结果
     *
     * @param $table 表名称
     * @param $fields 需要返回的字段
     * @param $where 查询条件
     * @param int $order 排序规则
     * @param int $group_by
     * @return array
     */
    function getAll($table, $fields, $where=null, $order=1, $group_by = 1)
    {
        return $this->prepare_get_all($table, $fields, $where, $order, $group_by);
    }

    /**
     * prepare插入
     * @param $table 表名称
     * @param $data 要处理的数据,跟表中的字段对应
     * @param bool $multi <pre>
     *  是否批量插入数据,如果是
     *      $data = array(
     *          'fields' = array(字段1,字段2,...),
     *          'values'= array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     *  </pre>
     * @return array
     * @throws FrontException
     */
    function add($table, $data, $multi = false)
    {
        return $this->prepare_insert($table, $data, $multi);
    }

    /**
     * 带分页功能的查询
     * @param $table 联合查询$table变量 $table = table_a a LEFT JOIN table_b b ON a.id=b.aid;
     * @param $fields 要查询的字段 所有字段的时候 $fields='*'
     * @param $where 查询条件
     * @param int $order 排序
     * @param array $page 分页参数 默认返回50条记录
     * @return array
     */
    function find($table, $fields, $where, $order = 1, & $page = array('p'=>1, 'limit'=>50))
    {
        return $this->prepare_find($table, $fields, $where, $order, $page);
    }

    /**
     * prepare 更新数据
     * @param $table
     * @param $data
     * @param $where
     * @return $this|array|string
     */
    function update($table, $data, $where)
    {
        return $this->prepare_update($table, $data, $where);
    }

    /**
     * prepare 删除数据
     *
     * @param $table
     * @param $where
     * @return mixed|void
     */
    function del($table, $where)
    {
        return $this->prepare_delete($table, $where);
    }

    /**
     * 执行一条SQL 语句 并返回结果
     *
     * @param $sql
     * @param int $model
     * @throws CoreException
     * @return mixed
     */
    function fetchOne($sql, $model = PDO::FETCH_ASSOC)
    {
        try
        {
            $data = $this->pdo->query( $sql );
            return $data->fetch( $model );
        } catch( Exception $e ) {
            throw new CoreException( $e->getMessage() );
        }
    }

    /**
     * 执行sql 并返回所有结果
     *
     * @param $sql
     * @param int $model
     * @throws CoreException
     * @return array
     */
    function fetchAll($sql, $model = PDO::FETCH_ASSOC)
    {
        try
        {
            $data = $this->pdo->query( $sql );
            return $data->fetchAll( $model );
        } catch ( Exception $e ) {
            throw new CoreException( $e->getMessage() );
        }
    }

    /**
     * 执行sql 用于无返回值的操作
     *
     * @param $sql
     * @return bool
     */
    function execute($sql)
    {
        if($this->pdo->exec($sql))
        {
            return true;
        }
        return false;
    }

    /**
     * 参数绑定
     *
     * @param $statement
     * @param array $params
     * @return $this
     * @throws CoreException
     */
    public function prepare($statement, $params = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
    {
        try
        {
            $this->stmt = $this->pdo->prepare($statement, $params);
            return $this;
        } catch (PDOException $e) {
            throw new CoreException( $e->getMessage() );
        }
    }

    /**
     * 执行参数绑定
     *
     * @param null $args
     * @return $this
     * @throws CoreException
     */
    public function exec($args=null)
    {
        try
        {
            $result = $this->stmt->execute($args);
            return $this;
        } catch (PDOException $e) {
            throw new CoreException( $e->getMessage() );
        }
    }

    /**
     * 返回参数绑定结果
     *
     * @param bool $_fetchAll
     * @param $result_type
     * @return mixed
     * @throws CoreException
     */
    public function stmt_fetch($_fetchAll=false, $result_type = PDO::FETCH_ASSOC)
    {
        if(! $this->stmt) throw new CoreException("stmt init failed!");
        if(true === $_fetchAll)
        {
            return $this->stmt->fetchAll($result_type);
        }
        return $this->stmt->fetch($result_type);
    }

    /**
     * prepare方式单条查询
     *
     * @param $table
     * @param $fields
     * @param $where
     * @return mixed
     */
    function prepare_getone($table, $fields, $where)
    {
        $one_sql = "SELECT %s FROM {$table} WHERE %s LIMIT 1";
        $params = array();

        $field_str = $this->parse_fields($fields);
		$where_str = $this->parse_where($where, $params);

        $this->sql = sprintf($one_sql, $field_str, $where_str);
        $result = $this->prepare( $this->sql )->exec($params)->stmt_fetch();

        return $result;
    }

    /**
     * prepare插入
     *
     * @param $table 表名称
     * @param $data 要处理的数据,跟表中的字段对应
     * @param bool $multi <pre>
     *  是否批量插入数据,如果是
     *      $data = array(
     *          'fields' = array(字段1,字段2,...),
     *          'values'= array(
     *                      array(字段1的值, 字段2的值),
     *                      array(字段1的值, 字段2的值))
     *      );
     *  </pre>
     * @return array
     * @throws FrontException
     */
    function prepare_insert($table, $data, $multi = false)
    {
        $insert_sql = "INSERT {$table} (%s) VALUE (%s)";
        $field = $value = '';

        if(true === $multi )
        {
            if( empty($data ['fields']) || empty($data ['values']) ) {
                throw new FrontException("data format error!");
            }

            foreach ($data ['fields'] as $d) {
                $field .= "{$d},";
                $value .= "?,";
            }

            $this->sql = sprintf($insert_sql, rtrim($field, ","), rtrim($value, ","));

            $stmt = $this->prepare($this->sql);
            foreach($data ['values'] as $data_array) {
                $stmt->exec($data_array);
            }

            return true;
        }
        else
        {
            foreach($data as $_field => $_value) {
                $field .= "{$_field},";
                $value .= "?,";
                $r[] = $_value;
            }

            $this->sql = sprintf($insert_sql, rtrim($field, ","), rtrim($value, ","));
            $stmt = $this->prepare($this->sql);
            $id = $stmt->exec($r)->insert_id();

            return $id;
        }
    }

    /**
     * 带分页功能的查询
     *
     * @param $table 联合查询$table变量 $table = table_a a LEFT JOIN table_b b ON a.id=b.aid;
     * @param $fields 要查询的字段 所有字段的时候 $fields='*'
     * @param $where 查询条件
     * @param int $order 排序
     * @param array $page 分页参数 默认返回50条记录
     * @return array
     */
    function prepare_find($table, $fields, $where = null, $order = 1, & $page = array('p'=>1, 'limit'=>50))
    {
        $all_sql = "SELECT %s FROM {$table} WHERE %s ORDER BY %s LIMIT %s";
        $params = array();

        $field_str = $this->parse_fields($fields);
        $where_str = $this->parse_where($where, $params);
        $order_str = $this->parse_order($order);

        $total = $this->prepare_getone($table, 'COUNT(*) as total', $where);

        $page['result_count'] = intval($total ['total']);
        $page['limit'] = max(1, intval($page['limit']));
        $page['total_page'] = ceil($page['result_count'] / $page['limit']);
        $page['p'] = max(1, min( $page['p'], $page['total_page']));
        $p = ($page['p'] - 1) * $page['limit'];

        $this->sql = sprintf($all_sql, $field_str, $where_str, $order_str, "{$p}, {$page['limit']}");
        $result = $this->prepare($this->sql)->exec($params)->stmt_fetch(true);
        return $result;
    }

    /**
     * 取出所有结果
     *
     * @param $table 联合查询$table变量 $table = table_a a LEFT JOIN table_b b ON a.id=b.aid;
     * @param $fields 要查询的字段 所有字段的时候 $fields='*'
     * @param $where 查询条件
     * @param int $order 排序
     * @param int $group_by
     * @return array
     */
    function prepare_get_all($table, $fields, $where = null, $order = 1, $group_by=1)
    {
        $params = array();
        $field_str = $this->parse_fields($fields);
        $where_str = $this->parse_where($where, $params);
        $order_str = $this->parse_order($order);
        $group_str = $this->parse_group($group_by);

        if($group_by !== 1)
        {
            $all_sql = "SELECT %s FROM {$table} WHERE %s GROUP BY %s ORDER BY %s";
            $this->sql = sprintf($all_sql, $field_str, $where_str, $group_str, $order_str);
        }
        else
        {
            $all_sql = "SELECT %s FROM {$table} WHERE %s ORDER BY %s";
            $this->sql = sprintf($all_sql, $field_str, $where_str, $order_str);
        }

        $result = $this->prepare($this->sql)->exec( $params )->stmt_fetch(true);
        return $result;
    }

    /**
     * prepare 方式更新
     * @param $table
     * @param $data
     * @param $where
     * @return $this|array|string
     */
    function prepare_update($table, $data, $where)
    {
        $up_sql = "UPDATE {$table} SET %s WHERE %s";
        $params = array();

        if(! empty($data) )
        {
            $field = '';
            if(is_array($data))
            {
                foreach($data as $d_key => $d_value) {
                    $field .= "{$d_key} = ? ,";
                    $params [] = strval($d_value);
                }
            }
            else
            {
                $field = $data;
            }
        }

        $where_params = array();
        $where_str = $this->parse_where($where, $where_params);

        foreach($where_params as $wp)
        {
        	$params [] = $wp;
        }

        $this->sql = sprintf($up_sql, trim($field, ","), $where_str);
        $this->prepare($this->sql)->exec($params);
        return true;
    }

    /**
     * 删除数据
     *
     * @param $table 表名称
     * @param $where 条件
     * @return bool
     */
    function prepare_delete($table, $where)
    {
        $del_sql = "DELETE FROM %s WHERE %s";

        $params = array();
        $where_str = $this->parse_where($where, $params);
        $this->sql = sprintf($del_sql, $table, $where_str);

        $stmt = $this->prepare( $this->sql );
        foreach($params as $param)
        {
            if(is_array($param))
            {
                foreach($param as $p)
                {
                    $stmt->exec( array($p) );
                }
            }
            else
            {
                $stmt->exec( array($param) );
            }
        }

        return true;
    }

    /**
     * 解析字段
     *
     * @param $fields
     * @return string
     */
    function parse_fields( $fields )
    {
    	if(empty($fields)) {
    		$field_str = '*';
    	} else {
    		if(is_array($fields)) {
    			$field_str = implode(",", $fields);
    		} else {
    			$field_str = $fields;
    		}
    	}

    	return $field_str;
    }

    /**
     * 解析where
     *
     * @param $where
     * @param $params
     * @return string
     */
    function parse_where($where, & $params)
    {
        if(! empty($where))
        {
            if( is_array($where) )
            {
                foreach($where as $w_key => $w_value)
                {
                    $operator = '=';
                    if(is_array($w_value))
                    {
                        list($operator, $n_value) = $w_value;
                        $w_value = $n_value;
                    }

                    $where_str  [] = "{$w_key} {$operator} ?";
                    $params [] = $w_value;
                }
                $where_str = implode(" AND ", $where_str);
            }
            else
            {
                $where_str = $where;
            }
        }
        else
        {
            $where_str = '1=1';
        }

        return $where_str;
    }

    /**
     * 解析order
     *
     * @param $order
     * @return int|string
     */
    function parse_order($order)
    {
        if(! empty($order))
        {
            if(is_array($order)) {
                $order_str = implode(",", $order);
            } else {
                $order_str = $order;
            }
        }
        else
        {
            $order_str = 1;
        }

        return $order_str;
    }

    /**
     * 解析group by
     *
     * @param $group_by
     * @return int
     */
    function parse_group($group_by)
    {
    	if(! empty($group_by))
    	{
    		$group_str = $group_by;
    	}
    	else
    	{
    		$group_str = 1;
    	}
    	return $group_str;
    }

    /**
     * 开启事务
     *
     * @return bool
     */
    public function commit(){
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function beginTA(){
        return $this->pdo->beginTransaction();
    }

    /**
     * 回滚
     *
     * @return bool
     */
    public function rollBack(){
        return $this->pdo->rollBack();
    }

    /**
     * 返回最后操作的id
     *
     * @return string
     */
    function insert_id(){
        return $this->pdo->lastInsertId();
    }
}
