<?php

class PdoDataAccess
{
    public $stmt;
    public $pdo;
    private static $instance;

    /**
     * @param $config_path 默认参数的路径
     */
    private function __construct( $dsn, $user, $password )
    {
        try{
            $this->pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_PERSISTENT => true));
            $this->pdo->query('set names utf8;');
        } catch(Exception $e) {
            throw new CoreException($e->getMessage().' line:'.$e->getLine().'<br>'.$e->getFile());
        }
    }

    /**
     * @param $dsn $user, $password
     */
    static function getInstance( $dsn, $user, $password )
    {
	//同时连接多个数据库 取消单例模式
        if(! self::$instance) {
            self::$instance = new PdoDataAccess($dsn, $user, $password);
        }
        return self::$instance;
    }

    /*
     *数组添加
     */
    function insertArr($arrData,$table,$where=''){
        $Item = array();
        foreach($arrData as $key=>$data){
            $Item[] = "`$key`='$data'";
        }
        $intStr = implode(',',$Item);
        $sql = "insert into {$table}  SET {$intStr} {$where}";
        $this->pdo->exec("insert into $table  SET $intStr $where");
        return $this->insert_id();
    }

    /*
     *数组更新(Update)
     */
    function updateArr($arrData,$table,$where=''){
        $Item = array();
        foreach($arrData as $key => $date)
        {
            $Item[] = "`$key`='$date'";
        }
        $upStr = implode(',',$Item);

        $upSql = "UPDATE `$table`  SET  $upStr";
        if($where) {
            $upSql .= " WHERE {$where}";
        }

        if( $this->execute($upSql) ) {
            return true;
        }
        return false;
    }

	/*
	 *取得上一步INSERT产生的ID
	 */
	function insert_id(){
		return	$this->pdo->lastInsertId();
	}

    function fetchOne($sql, $model = PDO::FETCH_ASSOC)
    {
        $data = $this->pdo->query($sql);
        if($data) {
            return $data->fetch($model);
        }
    }

    function fetchAll($sql, $model = PDO::FETCH_ASSOC)
    {
        $data = $this->pdo->query($sql);
        if($data) {
            return $data->fetchAll( $model );
        }
    }

    function execute($sql)
    {
        if($this->pdo->exec($sql))
        {
            return true;
        }
        return false;
    }

    public function prepare($statement, $params = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY))
    {
        $res = $this->pdo->prepare($statement, $params);
        if ($res) {
            $this->stmt = $res;
            return $this;
        }
        throw new CoreException("prepare error!");
    }

    public function exec($args=null)
    {
        if(! $this->stmt) throw new CoreException("stmt init failed!");
        
        $res = $this->stmt->execute($args);
        if($res) {
            return $this;
        }
        throw new CoreException("stmt execute failed!");
    }

    public function stmt_fetch($_fetchAll=false, $result_type = PDO::FETCH_ASSOC)
    {
        if(! $this->stmt) throw new CoreException("stmt init failed!");

        if($_fetchAll) {
            return $this->stmt->fetchAll($result_type);
        }
        return $this->stmt->fetch($result_type);
    }

    public function commit(){
        return $this->pdo->commit();
    }

    public function beginTA(){
        return $this->pdo->beginTransaction();
    }

    public function rollBack(){
        return $this->pdo->rollBack();
    }

    function insertid()
    {
        return $this->pdo->lastInsertId();
    }
}