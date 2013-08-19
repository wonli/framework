<?php
/**
* @Author: wonli <wonli@live.com>
*/
class Msql
{
    /**
     * @var mysql link resource
     */
    private $_link;

    /**
     * mysql
     *
     * @param $config
     */
    function __construct( $config )
    {
        $this->connect($config['dbhost'], $config['dbuser'],
            $config['dbpswd'],$config['dbname'], $config['dbport']=3306, $config['charset']);
    }

    /**
     * connetc to mysql
     *
     * @param $dbhost
     * @param $dbuser
     * @param $dbpass
     * @param $dbname
     * @param int $dbport
     * @param string $charset
     * @return resource
     */
    function connect($dbhost, $dbuser, $dbpass, $dbname, $dbport=3306, $charset='utf-8')
    {
    	$this->_link = mysql_connect("{$dbhost}:{$dbport}", $dbuser, $dbpass)or die("MySql connect error");

        mysql_select_db($dbname, $this->_link);
		mysql_query("set names {$charset}");

        return $this->_link;
    }

    /**
     * query
     *
     * @param string sql
     * @return resouce $query;
     */
    private function query($sql)
    {
        $query = mysql_query($sql, $this->_link);
        if($query) return $query;
        else exit('Query error.'.mysql_errno().'-'.mysql_error());
    }

    /**
     * execute a sql
     *
     * @param string sql
     * @return bool true|false;
     */
    public function execute($sql)
    {
        return $this->query($sql);
    }

    /**
     * fetch a row
     *
     * @param sql
     * @return array $result
     */
    public function fetchOne($sql)
    {
        $_query = $this->query($sql);

        $result = mysql_fetch_assoc($_query);
        return $result;
    }

    /**
     * fetch all rows
     *
     * @param sql
     * @return array $result
     */
    public function fetchAll($sql)
    {
        $_query = $this->query($sql);

        $result = array();
        $i = 0;
        while($row = mysql_fetch_assoc($_query))
        {
            $result[$i] = $row;
            $i++;
        }
        return $result;
    }

    /**
     * return last insert id
     *
     * @param
     * @return int
     */
    public function insertid()
    {
        return mysql_insert_id($this->_link);
    }

}







