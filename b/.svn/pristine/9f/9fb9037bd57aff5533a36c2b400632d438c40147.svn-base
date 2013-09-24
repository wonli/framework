<?php

class PdoDataAccess
{
    private $pdo;
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
        if(! self::$instance) {
            self::$instance = new PdoDataAccess($dsn, $user, $password);
        }
        return self::$instance;
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

    function insertid()
    {
        return $this->pdo->lastInsertId();
    }
}