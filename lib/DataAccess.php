<?php
/**
* Author:       wonli
* Contact:      wonli@live.com
* Date:         2011.08
* Description:  date access
*/
class DataAccess
{
    private $_link;
    /**
     * connect to mysql and select database;
     *
     * @param string $dbhost database host;
     * @param string $dbuser mysql username;
     * @param string $dbpass mysql password;
     * @param string $dbname select database name;
     * @return mysql resouce $this->_link;
     */
    function __construct()
    {
        $this->_link = mysql_connect(config::$dbhost.':'.config::$dbport, config::$dbuser, config::$dbpass);
        mysql_select_db(config::$dbname, $this->_link);
		mysql_query('set names '.config::$db_charset);

        return $this->_link;
    }


    function connect($dbhost, $dbport=3306, $dbuser, $dbpass, $dbname)
    {
    	$this->_link = mysql_connect($dbhost.':'.$dbport, $dbuser, $dbpass)or die("MySql connect error");

        mysql_select_db($dbname, $this->_link);
		mysql_query('set names '.config::$db_charset);

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







