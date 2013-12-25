<?php
/**
 * @Auth: wonli <wonli@live.com>
 * Class Response
 */
class Response
{
    /**
     * 返回头http类型
     *
     * @var string
     */
    protected $content_type;

    /**
     * @var array $statusDescriptions
     */
    static public $statusDescriptions = array(
        100 => 'Continue',
        101 => 'Switching Protocols',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    /**
     * @var array $mime_types
     */
    static public $mime_types = array (
        'ez' => 'application/andrew-inset',
        'hqx' => 'application/mac-binhex40',
        'cpt' => 'application/mac-compactpro',
        'doc' => 'application/msword',
        'bin' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'lha' => 'application/octet-stream',
        'lzh' => 'application/octet-stream',
        'exe' => 'application/octet-stream',
        'class' => 'application/octet-stream',
        'so' => 'application/octet-stream',
        'dll' => 'application/octet-stream',
        'oda' => 'application/oda',
        'pdf' => 'application/pdf',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'mif' => 'application/vnd.mif',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'wbxml' => 'application/vnd.wap.wbxml',
        'wmlc' => 'application/vnd.wap.wmlc',
        'wmlsc' => 'application/vnd.wap.wmlscriptc',
        'bcpio' => 'application/x-bcpio',
        'vcd' => 'application/x-cdlink',
        'pgn' => 'application/x-chess-pgn',
        'cpio' => 'application/x-cpio',
        'csh' => 'application/x-csh',
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dxr' => 'application/x-director',
        'dvi' => 'application/x-dvi',
        'spl' => 'application/x-futuresplash',
        'gtar' => 'application/x-gtar',
        'hdf' => 'application/x-hdf',
        'js' => 'application/x-javascript',
        'json' => 'application/json',
        'skp' => 'application/x-koan',
        'skd' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'latex' => 'application/x-latex',
        'nc' => 'application/x-netcdf',
        'cdf' => 'application/x-netcdf',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'swf' => 'application/x-shockwave-flash',
        'sit' => 'application/x-stuffit',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texinfo' => 'application/x-texinfo',
        'texi' => 'application/x-texinfo',
        't' => 'application/x-troff',
        'tr' => 'application/x-troff',
        'roff' => 'application/x-troff',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'ms' => 'application/x-troff-ms',
        'ustar' => 'application/x-ustar',
        'src' => 'application/x-wais-source',
        'xhtml' => 'application/xhtml+xml',
        'xht' => 'application/xhtml+xml',
        'zip' => 'application/zip',
        'au' => 'audio/basic',
        'snd' => 'audio/basic',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'kar' => 'audio/midi',
        'mpga' => 'audio/mpeg',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'aif' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'm3u' => 'audio/x-mpegurl',
        'ram' => 'audio/x-pn-realaudio',
        'rm' => 'audio/x-pn-realaudio',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'ra' => 'audio/x-realaudio',
        'wav' => 'audio/x-wav',
        'pdb' => 'chemical/x-pdb',
        'xyz' => 'chemical/x-xyz',
        'bmp' => 'image/bmp',
        'gif' => 'image/gif',
        'ief' => 'image/ief',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'png' => 'image/png',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'djvu' => 'image/vnd.djvu',
        'djv' => 'image/vnd.djvu',
        'wbmp' => 'image/vnd.wap.wbmp',
        'ras' => 'image/x-cmu-raster',
        'pnm' => 'image/x-portable-anymap',
        'pbm' => 'image/x-portable-bitmap',
        'pgm' => 'image/x-portable-graymap',
        'ppm' => 'image/x-portable-pixmap',
        'rgb' => 'image/x-rgb',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'igs' => 'model/iges',
        'iges' => 'model/iges',
        'msh' => 'model/mesh',
        'mesh' => 'model/mesh',
        'silo' => 'model/mesh',
        'wrl' => 'model/vrml',
        'vrml' => 'model/vrml',
        'css' => 'text/css',
        'html' => 'text/html',
        'htm' => 'text/html',
        'asc' => 'text/plain',
        'txt' => 'text/plain',
        'rtx' => 'text/richtext',
        'rtf' => 'text/rtf',
        'sgml' => 'text/sgml',
        'sgm' => 'text/sgml',
        'tsv' => 'text/tab-separated-values',
        'wml' => 'text/vnd.wap.wml',
        'wmls' => 'text/vnd.wap.wmlscript',
        'etx' => 'text/x-setext',
        'xsl' => 'text/xml',
        'xml' => 'text/xml',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'mxu' => 'video/vnd.mpegurl',
        'avi' => 'video/x-msvideo',
        'movie' => 'video/x-sgi-movie',
        'ice' => 'x-conference/x-cooltalk',
    );

    /**
     * Response instance
     *
     * @var object
     */
    static $instance;

    private function __construct( $content_type )
    {
        $this->set_ContentType( $content_type );
    }

    /**
     * 单例模式
     *
     * @param string $content_type
     * @internal param string $response_type
     * @return Response
     */
    static function getInstance( $content_type = 'html' )
    {
        if(! self::$instance) {
            self::$instance = new Response( strtolower($content_type) );
        }
        return self::$instance;
    }

    /**
     * @param $code
     */
    function sendStatus($code) {
        header("HTTP/1.1 {$code}");
    }

    /**
     * 设置返回头类型
     *
     * @param $header_type
     * @return $this
     */
    function set_ContentType( $header_type )
    {
        if(isset(self::$mime_types [$header_type]))
        {
            $this->content_type = self::$mime_types [$header_type];
        } else {
            $this->content_type = self::$mime_types [ 'html' ];
        }
        return $this;
    }

    /**
     * 返回ContentType
     *
     * @return mixed
     */
    function get_ContentType()
    {
        return $this->content_type;
    }

    /**
     * 发送basic_auth认证
     */
    function basic_auth()
    {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="CP Secret"');
        print 'Auth Faile';
    }

    /**
     * 发送http 状态码
     *
     * @param int $code
     */
    function send_response_status($code = 200)
    {
        if( 200 != $code ) {
            $this->sendStatus($code);
        }

        header("Content-Type: {$this->content_type};charset=utf-8");
    }

    /**
     * 生成参数
     *
     * @param $content
     * @return array
     */
    private function make_params($content)
    {
        $result['CP_PARAMS'] = $content;
        return $result;
    }

    /**
     * 发送header
     *
     * @param $content
     * @return $this
     */
    function send_header( $content )
    {
        $contents = $this->make_params( $content );
        foreach( $contents as $c_name => $c_val )
        {
            if(is_array($c_val))
            {
                $c_val = json_encode( $c_val );
            }
            header("{$c_name}:{$c_val}");
        }
        return $this;
    }

    /**
     * 为response附加返回参数
     *
     * @param $content
     * @return $this
     */
    function add_params( $content )
    {
        $contents = $this->make_params( $content );
        foreach( $contents as $c_name => $c_val )
        {
            $_SERVER[$c_name] = $c_val;
        }
        return $this;
    }

    /**
     * 输出内容
     *
     * @param int $code
     * @param $contents
     * @return mixed
     */
    function output($code = 200, $contents = '')
    {
        $this->send_response_status($code);

        if(! $contents ) {
            $contents = self::$statusDescriptions [$code];
        }

        print $contents;
    }

    /**
     * 调用模板输出信息
     *
     * @param int $code
     * @param string $message
     * @param string $tpl
     * @return mixed
     */
    function display($code = 200, $message = '', $tpl = '')
    {
        $this->send_response_status($code);

        if(! $message ) {
            $message = self::$statusDescriptions [$code];
        }

        if(null !== $tpl && file_exists($tpl)) {
            return require $tpl;
        } else {
            var_export( $message, true);
        }
    }
}

