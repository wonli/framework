<?php
/**
 *    文件上传辅助类
 *
 *    @author    Garbin
 *    @usage    none
 */
class Uploader
{
    var $_file              = null;
    var $_allowed_file_type = null;
    var $_allowed_file_size = null;
    var $_root_dir          = null;

    /**
     *    添加由POST上来的文件
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function addFile($file)
    {
        if (!is_uploaded_file($file['tmp_name']))
        {
            return false;
        }
        $this->_file = $this->_get_uploaded_info($file);
    }

    /**
     *    设定允许添加的文件类型
     *
     *    @author    Garbin
     *    @param     string $type （小写）示例：gif|jpg|jpeg|png
     *    @return    void
     */
    function allowed_type($type)
    {
        $this->_allowed_file_type = explode('|', $type);
    }

    /**
     *    允许的大小
     *
     *    @author    Garbin
     *    @param     mixed $size
     *    @return    void
     */
    function allowed_size($size)
    {
        $this->_allowed_file_size = $size;
    }


    function _get_uploaded_info($file)
    {
        $pathinfo = pathinfo($file['name']);
        $file['extension'] = $pathinfo['extension'];
        $file['filename']     = $pathinfo['basename'];
        if (!$this->_is_allowd_type($file['extension']))
        {
            $this->_error('not_allowed_type', $file['extension']);

            return false;
        }
        if (!$this->_is_allowd_size($file['size']))
        {
            $this->_error('not_allowed_size', $file['size']);

            return false;
        }

        return $file;
    }

    function _is_allowd_type($type)
    {
        if (!$this->_allowed_file_type)
        {
            return true;
        }
        return in_array(strtolower($type), $this->_allowed_file_type);
    }

    function _is_allowd_size($size)
    {
        if (!$this->_allowed_file_size)
        {
            return true;
        }

        return is_numeric($this->_allowed_file_size) ?
                ($size <= $this->_allowed_file_size) :
                ($size >= $this->_allowed_file_size[0] && $size <= $this->_allowed_file_size[1]);
    }
    /**
     *    获取上传文件的信息
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function file_info()
    {
        return $this->_file;
    }

    /**
     *    若没有指定root，则将会按照所指定的path来保存，但是这样一来，所获得的路径就是一个绝对或者相对当前目录的路径，因此用Web访问时就会有问题，所以大多数情况下需要指定
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function root_dir($dir)
    {
        $this->_root_dir = $dir;
    }
    function save($dir, $name, $mkdir = true)
    {
        if (!$this->_file)
        {
            return false;
        }
        if (!$name)
        {
            $name = $this->_file['filename'];
        }
        else
        {
            $name .= '.' . $this->_file['extension'];
        }
        $path = trim($dir, '/') . '/' . $name;
        
        return $this->move_uploaded_file($this->_file['tmp_name'], $path);
    }

    /**
     *    将上传的文件移动到指定的位置
     *
     *    @author    Garbin
     *    @param     string $src
     *    @param     string $target
     *    @return    bool
     */
    function move_uploaded_file($src, $target)
    {
        $abs_path = $this->_root_dir ? trim( $this->_root_dir . '/' ) . $target : $target;
        $dirname = dirname($target);

        if(! file_exists($dirname))
        {
            if (! mkdir($dirname, 0666, true) )
            {
                $this->_error('dir_doesnt_exists');

                return false;
            }        
        }        

        if (move_uploaded_file($src, $abs_path))
        {
            @chmod($abs_path, 0666);
            return $target;
        }
        else
        {
            return false;
        }
    }

    /**
     * 生成随机的文件名
     */
    function random_filename()
    {
        $seedstr = explode(" ", microtime(), 5);
        $seed    = $seedstr[0] * 10000;
        srand($seed);
        $random  = rand(1000,10000);

        return date("YmdHis", time()) . $random;
    }
}

/**
 *    FtpUploader
 *
 *    @author    Garbin
 *    @usage    none
 */
class FtpUploader extends Uploader
{
    var $furl;
    var $host;
    var $user;
    var $pass;
    var $path;
    var $port;
    var $delay;
    var $connect;
    var $make_thumb;
    
    function __construct(&$ftpinfo, $make_thumb = true)
    {
        $this->init($ftpinfo);
        $this->make_thumb = $make_thumb;
    }
    
    function init($ftpinfo)
    {
        $this->parseConfig($ftpinfo);
        $this->connect = $this->_connect();
        
        if(! $this->_login() ) {
            $this->_error('login_ftp_fail');
            return;
        }

        return $this;
    }
    
    private function parseConfig($ftpinfo)
    {
        foreach($ftpinfo as $key=>$value) {
            if(! isset($this->$key)) {
                $this->$key = $value;
            }
        }
    }
    
    private function _login($ispasv = false)
    {
        if(ftp_login($this->connect, $this->user, $this->pass)) {
            if(true === $ispasv) {
                @ftp_pasv($this->conn_id,1); // 打开被动模拟
            }
            return true;
        }
        return false;
    }
    
    private function _connect ()
    {
        return @ftp_connect($this->host, $this->port);
    }
    
    function save($newpath, $name, $mkdir = true)
    {
        if(! $newpath) {
            $newpath = $this->path;
        } else {
            $newpath = $this->path.$newpath;
        }

        if($mkdir) $this->dir_mkdirs($newpath);

        if (!$name) {
            $name = $this->_file['filename'];
        } else {
            $name .= '.' . $this->_file['extension'];
        }
        $ftp_file = array();
        $return = array();
        $_tmpfile = $this->_file['tmp_name'];
        $filename = $newpath.'/'.$name;
            
        $return["file_url"] = $this->furl.$filename;
        $return["mime_type"] = $this->_return_mimetype($name);
        $ftp_file[$filename] = $_tmpfile;          
        
        if( $this->make_thumb )
        {
            $thumb_filename = $newpath.'/small_'.$name;       
            $_thumbfile = tempnam(dirname($_tmpfile), 'thumb_'.$name);        
            
            if( make_thumb($_tmpfile, $_thumbfile, THUMB_WIDTH, THUMB_HEIGHT, THUMB_QUALITY) )
            {
                $ftp_file[$thumb_filename] = $_thumbfile;
                $return["thumbnail_url"] = $this->furl.$thumb_filename;
            } else {
                $this->_error('make_thumbnail_fail');
                return;
            }
        }
            
        if($this->save_by_put($ftp_file)) {
            if($this->make_thumb) {
                unlink($_thumbfile);
            }
            return $return;
        } else {
            $this->_error('file_upload_fail');
            return;        
        }
    }
    
    function save_by_put($file_arr, $mod = FTP_BINARY)
    {
        $error = 0;
        foreach($file_arr as $ftp_file_name => $resource_file_name) {
            if(! @ftp_put($this->connect, $ftp_file_name, $resource_file_name, $mod)) {
                echo $error ++;
            }
        }
        if($error > 0) return false;
        else return true;
    }

    function dir_mkdirs($path)
    {
        $path_arr = explode('/',$path); // 取目录数组
        $path_div = count($path_arr); // 取层数

        foreach($path_arr as $val) // 创建目录
        {
            if(@ftp_chdir($this->connect, $val) == FALSE)
            {
                $tmp = @ftp_mkdir($this->connect, $val);
                if($tmp == FALSE)
                {
                    $this->_error('make_dir_fail');
                    return;  
                }
                @ftp_chdir($this->connect, $val);
            }
        }
        
        for($i=1;$i<=$path_div;$i++) // 回退到根
        {
            @ftp_cdup($this->connect);
        }
    }
    
    function ftp_unlink($path)
    {
        if(is_array($path)) {
            $error = 0;
            foreach($path as $path_value) {
                if(! @ftp_delete ( $this->connect , $path_value ) ) {
                    $error ++;
                }
            }
            
            if($error > 0) {
                return false;
            }
            return true;
            
        } else {
            if( @ftp_delete ( $this->connect , $path ) ) return true;
            else return false;
        }
    }
    
    function _chdir($dir)
    {
        restore_error_handler();

        $dirs = explode('/', $dir);
        if (empty($dirs))
        {
            return true;
        }
        /* 循环创建目录 */
        foreach ($dirs as $d)
        {
            if (!@$this->_ftp_server->chdir($d))
            {
                $this->_ftp_server->mkdir($d);
                $this->_ftp_server->chmod($d);
                $this->_ftp_server->chdir($d);
                $this->_ftp_server->put(ROOT_PATH . '/data/index.html', 'index.html');
            }
        }

        reset_error_handler();

        return true;
    }
    
    function _return_mimetype($filename)
    {
        preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
        switch(strtolower($fileSuffix[1]))
        {
            case "js" :
                return "application/x-javascript";

            case "json" :
                return "application/json";

            case "jpg" :
            case "jpeg" :
            case "jpe" :
                return "image/jpeg";

            case "png" :
            case "gif" :
            case "bmp" :
            case "tiff" :
                return "image/".strtolower($fileSuffix[1]);

            case "css" :
                return "text/css";

            case "xml" :
                return "application/xml";

            case "doc" :
            case "docx" :
                return "application/msword";

            case "xls" :
            case "xlt" :
            case "xlm" :
            case "xld" :
            case "xla" :
            case "xlc" :
            case "xlw" :
            case "xll" :
                return "application/vnd.ms-excel";

            case "ppt" :
            case "pps" :
                return "application/vnd.ms-powerpoint";

            case "rtf" :
                return "application/rtf";

            case "pdf" :
                return "application/pdf";

            case "html" :
            case "htm" :
            case "php" :
                return "text/html";

            case "txt" :
                return "text/plain";

            case "mpeg" :
            case "mpg" :
            case "mpe" :
                return "video/mpeg";

            case "mp3" :
                return "audio/mpeg3";

            case "wav" :
                return "audio/wav";

            case "aiff" :
            case "aif" :
                return "audio/aiff";

            case "avi" :
                return "video/msvideo";

            case "wmv" :
                return "video/x-ms-wmv";

            case "mov" :
                return "video/quicktime";

            case "rar" :
                return "application/x-rar-compressed";

            case "zip" :
            return "application/zip";

            case "tar" :
                return "application/x-tar";

            case "swf" :
                return "application/x-shockwave-flash";

            default :
            if(function_exists("mime_content_type"))
            {
                $fileSuffix = mime_content_type($filename);
            }
            return "unknown/" . trim($fileSuffix[0], ".");
        }
    }    
}

?>