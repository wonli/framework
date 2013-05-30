<?php defined('CROSSPHP_PATH')or die('Access Denied');
/**
 * @Author: wangli
 */
class CoreModel extends FrameBase
{
    private $dbtype;
	protected $link;

	function __construct($controller = null)
    {
        $this->controller = $controller;
        $this->link = $this->dbcontent();
    }

    private function dbcontent()
    {
        $db = $this->getDBConfig();        
        $dbtype = $this->getDBType();
        
        if(!$db) {
            return false;
        }

        if($dbtype == 'mongodb') {
            if($db["dsn"]) {
                return  new Mongo($db["dsn"]);
            } else throw new CoreException("建立数据库连接失败!");
        } else if($dbtype == 'mysql') {
            if($db) {
                return PdoDataAccess::getInstance($db["dsn"], $db["user"], $db["pass"]);
            } else throw new CoreException("建立数据库连接失败!");
        } else {
            throw new CoreException("不支持的数据库类型!请自行扩展");
        }
    }

    private function setDBType($type)
    {
        if(! $this->dbtype) {
            $this->dbtype = $type;
        }
    }

    private function getDBType()
    {
        return $this->dbtype;
    }

    private function getDBConfig()
    {
        $db_config_file = Cross::config()->get("sys", "app_path").DS.'config'.DS."db.config.php";
        
        if( is_file($db_config_file) ) {

            $controller_config = Cross::config()->get("controller", strtolower($this->controller));
            $dbconfig = include $db_config_file;

            if(isset( $controller_config["db"] )) {
                if($controller_config["db"]) {
                    list($use, $type, $num) = $controller_config["db"];
                    if($use) {
                        if($type) {
                            $this->setDBType($type);
                        }

                        if(isset($dbconfig[$type][$num])) {
                            return $dbconfig[$type][$num];
                        } else {
                            throw new CoreException("数据库配置错误: ".$type.'-'.$num);
                        }
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                if($dbconfig["mysql"]["db"]) {
                    $this->setDBType("mysql");
                    return $dbconfig["mysql"]["db"];
                } else {
                    throw new CoreException("未找到数据库默认配置");
                }
            }
        }
        else
        throw new CoreException("unread db config");
    }

    final function load($module_name)
    {
        $name = substr($module_name, 0, -6);
        return $this->initModule($name);
    }
}
