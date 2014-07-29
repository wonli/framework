<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.1
 */
namespace cross\lib\driver;

/**
 * @Auth: wonli <wonli@live.com>
 * Class MysqlSimple
 * @package cross\lib\driver
 */
class MysqlSimple
{
    /**
     * @var object
     */
    private $_link;

    /**
     * mysql
     *
     * @param $config
     */
    function __construct($config)
    {
        $this->connect($config['dbhost'], $config['dbuser'],
            $config['dbpswd'], $config['dbname'], $config['dbport'] = 3306, $config['charset']);
    }

    /**
     * connetc to mysql
     *
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @param int $dbport
     * @param string $charset
     * @return resource
     */
    function connect($dbhost, $dbuser, $dbpass, $dbname, $dbport = 3306, $charset = 'utf-8')
    {
        $this->_link = mysql_connect("{$dbhost}:{$dbport}", $dbuser, $dbpass) or die("MySql connect error");

        mysql_select_db($dbname, $this->_link);
        mysql_query("set names {$charset}");

        return $this->_link;
    }

    /**
     * query
     *
     * @param string $sql
     * @return resource
     */
    private function query($sql)
    {
        $query = mysql_query($sql, $this->_link);
        if ($query) return $query;
        else exit('Query error.' . mysql_errno() . '-' . mysql_error());
    }

    /**
     * execute a sql
     *
     * @param  string$sql
     * @return resource
     */
    public function execute($sql)
    {
        return $this->query($sql);
    }

    /**
     * fetch a row
     *
     * @param string $sql
     * @return array
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
     * @param string $sql
     * @return array
     */
    public function fetchAll($sql)
    {
        $_query = $this->query($sql);

        $result = array();
        $i = 0;
        while ($row = mysql_fetch_assoc($_query)) {
            $result[$i] = $row;
            $i++;
        }

        return $result;
    }

    /**
     * return last insert id
     *
     * @return int
     */
    public function insert_id()
    {
        return mysql_insert_id($this->_link);
    }

}







