<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Response
 * @package Cross\Core
 */
class Response
{
    /**
     * http头
     *
     * @var array
     */
    protected $header;

    /**
     * 返回头http类型
     *
     * @var string
     */
    protected $content_type;

    /**
     * http 状态代码
     *
     * @var int
     */
    protected $response_status;

    /**
     * 停止发送标识
     *
     * @var bool
     */
    protected $is_end_flush = false;

    /**
     * 防止重复发送header头
     *
     * @var bool
     */
    static $is_send_header = false;

    /**
     * Response instance
     *
     * @var object
     */
    static $instance;

    private function __construct()
    {

    }

    /**
     * 单例模式
     *
     * @return Response
     */
    static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Response();
        }

        return self::$instance;
    }

    /**
     * @param int $status
     * @return $this
     */
    function setResponseStatus($status = 200)
    {
        $this->response_status = $status;
        return $this;
    }

    /**
     * @return int
     */
    function getResponseStatus()
    {
        if (!$this->response_status) {
            $this->setResponseStatus();
        }

        return $this->response_status;
    }

    /**
     * 设置header信息
     *
     * @param $header
     * @return $this
     */
    function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 获取要发送到header信息
     */
    function getHeader()
    {
        return $this->header;
    }

    /**
     * 设置返回头类型
     *
     * @param string $content_type
     * @return $this
     */
    function setContentType($content_type = 'html')
    {
        $this->content_type = strtolower($content_type);
        return $this;
    }

    /**
     * 返回ContentType
     *
     * @return mixed
     */
    function getContentType()
    {
        if (!$this->content_type) {
            $this->setContentType();
        }
        return $this->content_type;
    }

    /**
     * 发送basicAuth认证
     *
     * @param array $config
     * @return bool
     */
    function basicAuth($config)
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            if ((isset($config['user']) && $_SERVER['PHP_AUTH_USER'] == $config['user'])
                && (isset($config['pw']) && $_SERVER['PHP_AUTH_PW'] == $config['pw'])
            ) {
                return true;
            }
        }

        $realm = isset($config['realm']) ? $config['realm'] : 'Basic Auth';
        $failed_msg = isset($config['failed_msg']) ? $config['failed_msg'] : 'Auth Failed';
        return $this->setHeader(401)
            ->setHeader(sprintf('WWW-Authenticate: Basic realm="%s"', $realm))
            ->displayOver($failed_msg);
    }

    /**
     * 发送http 状态码
     *
     * @param int $code
     */
    function sendResponseStatus($code = 200)
    {
        $descriptions = self::$statusDescriptions[$code];
        header("HTTP/1.1 {$code} {$descriptions}");
    }

    /**
     * 发送ContentType
     */
    function sendContentType()
    {
        $content_type_name = $this->getContentType();
        if (isset(self::$mime_types [$content_type_name])) {
            $content_type = self::$mime_types [$content_type_name];
        } else {
            $content_type = self::$mime_types ['html'];
        }

        header("Content-Type: {$content_type}; charset=utf-8");
    }

    /**
     * 为response附加返回参数
     *
     * @param $content
     * @return $this
     */
    function addParams($content)
    {
        $contents = $this->makeParams($content);
        foreach ($contents as $c_name => $c_val) {
            $_SERVER[$c_name] = $c_val;
        }

        return $this;
    }

    /**
     * 生成参数
     *
     * @param $content
     * @return array
     */
    private function makeParams($content)
    {
        $result = array();
        if (!is_array($content)) {
            $result['CP_PARAMS'] = $content;
        } else {
            $result = $content;
        }

        return $result;
    }

    /**
     * 发送header
     *
     * @return $this
     */
    private function sendHeader()
    {
        $contents = $this->getHeader();
        if (!empty($contents)) {
            if (!is_array($contents)) {
                $contents = array($contents);
            }

            foreach ($contents as $content) {
                header($content);
            }
        }

        return $this;
    }

    /**
     * 发送Response头
     */
    private function sendResponseHeader()
    {
        $this->sendContentType();
        $this->sendHeader();
    }

    /**
     * 输出内容
     *
     * @param string $message
     * @param string $tpl
     * @return bool
     */
    private function flushContent($message, $tpl = '')
    {
        if (null !== $tpl && is_file($tpl)) {
            require $tpl;
        } else {
            echo $message;
        }

        return true;
    }

    /**
     * 标识停止输出
     */
    function setEndFlush()
    {
        $this->is_end_flush = true;
        return $this;
    }

    /**
     * 获取标识状态,是否终止输出
     *
     * @return bool
     */
    function isEndFlush()
    {
        return $this->is_end_flush;
    }

    /**
     * 重定向
     *
     * @param $url
     * @param int $status
     * @return string
     */
    function redirect($url, $status = 200)
    {
        return $this->setResponseStatus($status)->setHeader("Location: {$url}")->displayOver();
    }

    /**
     * 调用模板输出信息
     *
     * @param string $content
     * @param string $tpl
     * @return string
     */
    function display($content = '', $tpl = '')
    {
        $code = $this->getResponseStatus();
        if (false == self::$is_send_header && PHP_SAPI != 'cli') {
            $this->sendResponseStatus($code);
            $this->sendResponseHeader();
            self::$is_send_header = true;
        }

        if (!$content) {
            $content = self::$statusDescriptions [$code];
        }

        $this->flushContent($content, $tpl);
    }

    /**
     * 输出当前内容并结束
     *
     * @param string $content
     * @param string $tpl
     * @return string
     */
    function displayOver($content = '', $tpl = '')
    {
        $this->setEndFlush();
        return $this->display($content, $tpl);
    }

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
    static public $mime_types = array(
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
}

