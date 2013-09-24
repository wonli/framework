<?php
/*
*************************************************************
*   this is a script for check services by  user self
*   You can delete it, or keep it .
*   Each service self-examination, will auto generate it
*************************************************************
*
*   Sina App Engine                 http://sae.sina.com.cn/
*
*********************************************** pangee ******
*/

header("Content-type: text/javascript; charset=utf-8");
define( 'my_version' , '2.0' );

//sign
$md5key = $GLOBALS['md5key'] = substr( md5( SAE_ACCESSKEY.SAE_SECRETKEY ) , 8 , 16 );
if( $md5key != v('passkey') )
    die( 'access deny!' );

//precheck
$service = strtolower(trim(v('service')));
$allow_services = array( 'memcache', 'mysql', 'kvdb', 'taskq', 'fetchurl', 'socket', 'image', 'storage', 'weibo' );
$GLOBALS['services'] = $allow_services;
if( !in_array($service, $allow_services) )
    die('operate deny!');

$GLOBALS['public_image_file'] = 'http://lib.sinaapp.com/sae_checker/10k.jpg';


//interface checker
interface I_sae_checker{
    public function report();
}
class sae_checker implements I_sae_checker{
    private $s_time;
    public  $successes;
    public  $errors;
    private $s_index;
    public function __construct(){
        $this->restart();
    }
    public function restart(){
        $this->s_time = micro_time();
    }
    public function lost(){
        return number_format( (micro_time()-$this->s_time), 2);
    }
    public function auto_init( $t ){
        $methods = get_class_methods( $t );
        $this->s_index = current(array_keys( $GLOBALS['services'], substr(get_class($t),0,0-strlen('_checker')) ));
        foreach( $methods as $m ){
            if( substr($m, 0, 3)!='ck_' ) continue;
            list($option, $result) = explode('.',$this->$m());
            if( intval($result)===0 )
                $this->successes[] = $option;
            else
                $this->errors[] = $option;
        }
        return true;
    }
    public function report(){
        $rep = array( 'index'=>$this->s_index ,'success'=>$this->successes, 'error'=>$this->errors );
        return rep( $rep );
    }
}

//extends 
class memcache_checker extends sae_checker{
    private $_mc;
    private $_mc_key;
    public function __construct(){
        $this->_mc_key = 'sae_checker_automake_'.rand(1,99).date('YmdHi');
    }
    private function mc_handle(){
        if( !$this->_mc ) $this->_mc=memcache_init();
        return $this->_mc;
    }
    public function ck_connect(){
        if( $this->mc_handle() ) return '1.0';
        else return '1.1';
    }
    public function ck_set(){
        $value = rand(100,999);
        memcache_set( $this->mc_handle(), $this->_mc_key, $value, null, 30 );
        $rep = memcache_get( $this->mc_handle(), $this->_mc_key );
        if( $rep==$value ) return '2.0';
        else return '2.1';
    }
    public function ck_get(){
        if( $this->ck_set()==='2.0' ) return '3.0';
        else '3.1';
    }
}

class mysql_checker extends sae_checker{
    private $_db_table  = 'sae_checker_';
    private $_db_host   = array( 'm'=>SAE_MYSQL_HOST_M, 's'=>SAE_MYSQL_HOST_S );
    private $_db        = array( 'm'=>null, 's'=>null );
    public function __construct(){
        $this->_db_table .= rand(10,99).'_'.date('YmdH');
    }
    private function db_handle( $type ){
        if( !$this->_db[$type] ){
            $mysqli = new mysqli( 
                                    $this->_db_host[$type], 
                                    SAE_MYSQL_USER, 
                                    SAE_MYSQL_PASS, 
                                    SAE_MYSQL_DB, 
                                    SAE_MYSQL_PORT
                                );
            if( mysqli_connect_errno() )
                return false;
            $this->_db[$type] = $mysqli;
        }
        else{
            if( !$this->_db[$type]->ping() )
                return false;
        }
        return $this->_db[$type];
    }
    public function ck_connect_m(){
        if( $this->db_handle('m') ) return '1.0';
        else return '1.1';
    }
    public function ck_connect_s(){
        if( $this->db_handle('s') ) return '2.0';
        else return '2.1';
    }
    public function ck_create_table(){
        $drop_sql = 'DROP TABLE `'.$this->_db_table.'`';
        if( $this->table_exsits('m') ){
            $this->db_handle('m')->query($drop_sql);
        }
        if( $this->need_table('m') ){
            if( $this->table_exsits('m') )
                return '3.0';
            else
                return '3.1';
        }
        return '3.1';
    }
    public function ck_insert( $ret_type = 'defalut'){
        $this->need_table( 'm' );
        $key = 'key_'.rand(100,999);
        $value = 'value_'.rand(10000,99999);
        $sql = "insert into `".$this->_db_table."` ( `selfchk_id` , `selfchk_value` ) ";
        $sql.= "VALUES ( '$key' , '$value' )";
        $this->db_handle('m')->query( $sql );
        $sql = "select selfchk_value from `".$this->_db_table."` where selfchk_id='$key' limit 0,1";
        $result = $this->db_handle('m')->query( $sql );
        if( !$result ) return '4.1';
        $result = $result->fetch_assoc();
        if( $result['selfchk_value']==$value && $ret_type == 'key')
            return $key;
		elseif( $result['selfchk_value']==$value && $ret_type == 'array')
            return array($key=>$value);
        elseif( $result['selfchk_value']==$value )
            return '4.0';
        else
            return '4.1';
    }
    public function ck_sync(){
		sleep(2);
        list($key, $value) = $this->ck_insert( 'array' );
        $sql 	= "select selfchk_value from `".$this->_db_table."` where selfchk_id='$key' limit 0,1";
        $result = $this->db_handle('s')->query( $sql );
        if( !$result ) return '5.1';
        $result = $result->fetch_assoc();
        if( $result['selfchk_value']==$value )
            return '5.0';
        else
            return '5.1';
    }
    public function ck_update(){
        $key = $this->ck_insert( 'key' );
        $value = rand(10000,99999);
        $sql = "update `".$this->_db_table."` set selfchk_value='$value' ";
        $sql.= "where selfchk_id='$key'";
        $this->db_handle( 'm' )->query( $sql );
        $sql = "select selfchk_value from `".$this->_db_table."` where selfchk_id='$key' limit 0,1";
        $result = $this->db_handle('m')->query( $sql );
        if( !$result ) return '6.1';
        $result = $result->fetch_assoc();
        if( $result['selfchk_value']==$value )
            return '6.0';
        else
            return '6.1';
    }
    public function ck_delete(){
        $key = $this->ck_insert( 'key' );
        $sql = "delete from `".$this->_db_table."` where selfchk_id='$key'";
        $this->db_handle( 'm' )->query( $sql );
        $sql = "select count(*) as count from `".$this->_db_table."` where selfchk_id='$key' limit 0,1";
        $result = $this->db_handle('m')->query( $sql );
        if( !$result ) return '7.1';
        $result = $result->fetch_assoc();
        if( $result && $result['count']==0 )
            return '7.0';
        else
            return '7.1';
    }
    public function ck_truncate(){
        $this->need_table( 'm' );
        $sql = "truncate table `".$this->_db_table."`";
        $this->db_handle( 'm' )->query( $sql );
        $sql = "select count(*) from `".$this->_db_table."` where 1;";
        $result = $this->db_handle('m')->query( $sql );
        if( !$result ) return '8.1';
        $result = $result->fetch_assoc();
        if( $result && $result['count']==0 )
            return '8.0';
        else
            return '8.1';
    }
    public function ck_droptable(){
        $this->need_table( 'm' );
        $sql = "drop table `".$this->_db_table."`";
        $this->db_handle( 'm' )->query( $sql );
        if( !$this->table_exsits('m') )
            return '9.0';
        else
            return '9.1';
    }
    private function need_table( $type='m' ){
        $create_sql = 'CREATE TABLE `app_'.SAE_APPNAME.'`.`'.$this->_db_table.'` (
                        `selfchk_id` VARCHAR( 80 ) NOT NULL ,
                        `selfchk_value` TEXT NULL ,
                        PRIMARY KEY ( `selfchk_id` )
                      ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
        if( !$this->table_exsits($type) ){
            if( !$this->db_handle( $type )->query( $create_sql ) )
                return false;
        }
        return true;
    }
    private function table_exsits( $type='m' ){
        $sql = "show tables;";
        if( $result=$this->db_handle($type)->query($sql) ){
            $tag = false;
            while($row = $result->fetch_assoc()){
                if( current($row)==$this->_db_table ){
                    $tag=true;
                    break;
                }
            }
            return $tag;
        }
        return false;  
    }
    public function __destruct(){
        foreach( $this->_db as $type=>$db ){
            if( $db ){
                if( $type=='m' && $this->table_exsits('m') ){
                    $db->query('drop table `'.$this->_db_table.'`');
                }
                $db->close();    
            }
        }
    }
}

class kvdb_checker extends sae_checker{
    private $kv;
    private $key;
    private $value;
    public function __construct(){
        $this->key = 'sae_checker_'.rand(100,999);
        $this->value = rand(10000,99999);
    }
    private function init(){
        if( !$this->kv )
            $this->kv = new SaeKV();
        return $this->kv->init();
    }
    public function ck_init(){
        if( $this->init() ) return '1.0';
        else    return '1.1';
    }
    public function ck_add(){
        $this->init();
        $r = $this->kv->add( $this->key , $this->value );
        if( $this->value == $this->kv->get($this->key) )
            return '2.0';
        else
            return '2.1';
    }
    public function ck_set( $for_other_method=false ){
        $this->init();
        $this->kv->set( $this->key , $this->value );
        $saved = $this->kv->get( $this->key );
        if( $this->value==$saved && $for_other_method )
            return true;
        elseif( $this->value==$saved )
            return '3.0';
        elseif( $for_other_method )
            return false;
        else
            return '3.1';
    }
    public function ck_get(){
        if( $this->ck_set(true) )
            return '4.0';
        else
            return '4.1';
    }
    public function ck_replace(){
        $this->init();
        $tmp_value = 'pangee_'.rand(100,999);
        $this->kv->replace( $this->key , $tmp_value );
        if( $this->kv->get($this->key)==$tmp_value )
            return '5.0';
        else
            return '5.1';
    }
    public function ck_delete(){
        $this->init();
        if( $this->ck_set(true) ){
            $this->kv->delete( $this->key );
            if( !$this->kv->get($this->key) )
                return '6.0';
            else
                return '6.1';
        } else {
            return '6.1';
        }
    }
    public function ck_mget(){
        $this->init();
        $keys = array();
        for($i=0;$i<10;$i++)
            $keys[] = $this->key.'_m'.rand().rand(1,99);
        foreach( $keys as $k )
            $this->kv->set($k,$this->value);
        $ret = $this->kv->mget($keys);
        if( !$ret ) return '7.1';
        $tag = '7.0';
        foreach( $ret as $r ){
            if( $r!=$this->value ){
                $tag = '7.1';
                break;
            }
        }
        return $tag;
    }
    public function ck_pkrget(){
        $this->init();
        $keys = array();
        $prefix = 'saeck_pkrget_'.uniqid().'_';
        for($i=0;$i<10;$i++)
            $keys[] = $prefix.$i;
        foreach( $keys as $k ){
            $this->kv->delete( $k );
            $this->kv->set($k,$this->value);
        }
        $ret = $this->kv->pkrget( $prefix, 10 );
        if( !$ret || count($ret)!=10 ) return '8.1';
        $tag = '8.0';
        foreach( $ret as $key=>$r ){
            if( (!in_array($key,$keys) || $r!=$this->value) && $tag == '8.0' )
                $tag = '8.1';
            $this->kv->delete( $key );
        }
        return $tag;
    }
}   

class taskq_checker extends sae_checker{
    private $tq;
    private $tq_name;
    public function __construct(){
        if( v('action')=='trigger' ){
            return $this->trigger();
        }
        if( !($this->tq_name=v('taskq_name')) )
            die('the name of queue is empty.');
    }
    private function trigger(){
        $c = new SaeCounter();
        $c->incr( 'sae_checker_wait_tq' );
        die('done!');
    }
    private function tq(){
        if( !$this->tq )
            $this->tq = new SaeTaskQueue( $this->tq_name );
        return $this->tq;
    }
    public function ck_addtask(){
        $c = new SaeCounter();
        if (!$c->exists('sae_checker_wait_tq')) {
            if (!$c->create( 'sae_checker_wait_tq', 0 )) {
                return '1.1';
            }
        }
        $url = $_SERVER['SCRIPT_URI'].'?passkey='.$GLOBALS['md5key'].'&service=taskq&action=trigger';
        $this->tq()->addTask( $url, NULL, true );
        $this->tq()->push();
        
        $s_time = time();
        $pass   = false;
        while( !$pass && (time()-$s_time<20) ){
            if( $c->get('sae_checker_wait_tq')>0 )
                $pass=true;
            else
                time_nanosleep(0, 100000000); // 1/10 second
        }
        $c->remove( 'sae_checker_wait_tq' );
        if( $pass ){
            return '1.0';
        }else 
            return '1.1';
    }
}

class fetchurl_checker extends sae_checker{
    private $contents = 'Hello everyone, I\'m pangee, and very glad to meet you!';
    private $fch;
    public function __construct(){
        if( v('action')=='trigger' )
            return $this->trigger();
    }
    private function trigger(){
        die( $this->contents );
    }
    private function fch(){
        if( !$this->fch )
            $this->fch = new SaeFetchurl();
        return $this->fch;
    }
    public function ck_fetch(){
        $url = $_SERVER['SCRIPT_URI'].'?passkey='.$GLOBALS['md5key'].'&service=fetchurl&action=trigger';
        $back= $this->fch()->fetch( $url );
        if( $back==$this->contents )
            return '1.0';
        else
            return '1.1';
    }
}

class socket_checker extends sae_checker{
    private $addr = 'lib.sinaapp.com';
    private $port = '80';
    private $connect_timeout = '2';
    private $response_timeout = '5';
    private $request_header = '';
    public function __construct(){
        $request_header = "GET /index.php HTTP/1.0\r\n";
        $request_header.= "Host: ".$this->addr."\r\n";
        $request_header.= "Accept:*/*\r\n";
        $request_header.= "Connection: close\r\n\r\n";
        $this->request_header = $request_header;
    }
    public function ck_connect( $rep_timeout=false ){
        $rep_timeout = $rep_timeout ? $rep_timeout : $this->response_timeout;
        $fp = fsockopen($this->addr, $this->port, $errno, $errstr, $this->connect_timeout);
        if (!$fp) {
            return '1.1';
        } else {
            stream_set_blocking($fp, true);
            stream_set_timeout( $fp, $rep_timeout);
            fwrite($fp, $this->request_header);
            $rep = stream_get_contents($fp);
            $meta = stream_get_meta_data($fp);
            if($meta['timed_out']){
                if( $rep_timeout>10 )
                    return '1.1';
                return $this->ck_connect( ++$rep_timeout );
            }
            fclose($fp);
            if( substr_count($rep, 'sae')>0 )
                return '1.0';
            return '1.1';   
        }
    }
}

class image_checker extends sae_checker{
    private $img;    
    private $img_data;
    public function __construct(){
        $this->img_data = pub_data();
        $this->img()->setData( $this->img_data );
    }
    private function img(){
        if( !$this->img )
            $this->img = new SaeImage();
        return $this->img;
    }
    public function ck_info(){
        $r = $this->img()->getImageAttr();
        if( $r[0]==$r[1] && $r[0]==500 && $r['mime']=='image/jpeg' )
            return '1.0';
        else
            return '1.1';
    }
}

class storage_checker extends sae_checker{
    private $stor;
    private $domain;
    private $file_name;
    private $file_data;
    public function __construct(){
        if( !($this->domain=v('domain')) )
            die('domain not exsits.');
        $this->file_name = 'sae_checker/storage_checker/tmp_'.rand(10000,99999).'.jpg';
        $this->file_data = pub_data();
    }
    private function stor(){
        if( !$this->stor )
            $this->stor = new SaeStorage();
        return $this->stor;
    }
    public function ck_write( $for_other_method=false ){
        $this->stor()->write( 
                                $this->domain, 
                                $this->file_name, 
                                $this->file_data
                            );
        $get = $this->stor()->read( $this->domain, $this->file_name);
        if( $get==$this->file_data && $for_other_method )
            return true;
        elseif( $get==$this->file_data )
            return '1.0';
        elseif( $for_other_method )
            return false;
        else
            return '1.1';
    }
    public function ck_read(){
        if( $this->ck_write(true) )
            return '2.0';
        else
            return '2.1';
    }
    public function ck_update(){
        $tmp_file = '/sae_checker.jpg';
        file_put_contents( SAE_TMP_PATH.$tmp_file, $this->file_data );
        $this->stor()->delete( $this->domain, $this->file_name );
        $this->stor()->upload( 
                                $this->domain,
                                $this->file_name,
                                SAE_TMP_PATH.$tmp_file
                             );
        $get = $this->stor()->read( $this->domain, $this->file_name);
        if( $get==$this->file_data )
            return '3.0';
        else
            return '3.1';
    }
    public function ck_delete(){
        if( !$this->stor()->fileExists($this->domain,$this->file_name) ){
            $this->stor()->write( $this->domain, $this->file_name, '123' );
        }
        if( !$this->stor()->fileExists($this->domain,$this->file_name) )
            return '4.1'; 
        $this->stor()->delete( $this->domain, $this->file_name );
        if( !$this->stor()->fileExists($this->domain,$this->file_name) )
            return '4.0';
        else
            return '4.1';
    }
    public function __destruct(){
        if( $this->stor && $this->stor()->fileExists($this->domain,$this->file_name) )
            $this->stor()->delete( $this->domain, $this->file_name );
    }
}

class weibo_checker extends sae_checker{
    public function ck_connect(){
        $api = 'https://api.weibo.com/2/users/show.json';
        
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_USERAGENT,"SaeChecker/2.0");
		$rep = curl_exec($ch);
		curl_close($ch);
        
        if (false !== stristr($rep, '"error_code":') 
            && false !== stristr($rep, 'appkey')) {
            return '1.0';
        }
        return '1.1';
    }    
}



// void main(){ ... }
$class_name = $service.'_checker';
$sae_checker = new $class_name();
$sae_checker->auto_init( $sae_checker );
$sae_checker->report();
// done.. thx!  :)



//<!-- function -->
function rep( $data ){
    $back = array();
    $back['status'] = 'im online!';
    $back = array_merge( $back , $data );

    $callback = trim(v('callback'));
    echo $callback.'('.json_encode( $back ).')';
    
    exit();
    return true;
}
function v( $key ){
    return $_REQUEST[$key]?$_REQUEST[$key]:false;
}
function micro_time(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function key_index( $name ){
    global $allow_services;
    return current( array_keys( $allow_services, $name ) );
}
function pub_data(){
    $f = new SaeFetchurl();
    return $f->fetch( $GLOBALS['public_image_file'] );
}
?>
