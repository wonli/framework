<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Http;

use Cross\Core\Delegate;

/**
 * @author wonli <wonli@live.com>
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
    protected $header = [];

    /**
     * cookie
     *
     * @var array
     */
    protected $cookie = [];

    /**
     * cookie配置
     *
     * @var array
     */
    protected $cookieConfig = [];

    /**
     * 返回头http类型
     *
     * @var string
     */
    protected $contentType;

    /**
     * http 状态代码
     *
     * @var int
     */
    protected $responseStatus;

    /**
     * 停止发送标识
     *
     * @var bool
     */
    protected $endFlush = false;

    /**
     * 输出内容
     *
     * @var string
     */
    protected $content = '';

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
     * @return static
     */
    static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new Response();
        }

        return self::$instance;
    }

    /**
     * @param int $status
     * @return self
     */
    function setResponseStatus(int $status = 200): self
    {
        $this->responseStatus = $status;
        return $this;
    }

    /**
     * @return int
     */
    function getResponseStatus(): int
    {
        if (!$this->responseStatus) {
            $this->setResponseStatus();
        }

        return $this->responseStatus;
    }

    /**
     * 设置header信息
     *
     * @param mixed $header
     * @return self
     */
    function setHeader($header): self
    {
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $this->header[] = "{$key}: {$value}";
            }
        } else {
            $this->header[] = $header;
        }

        return $this;
    }

    /**
     * header
     *
     * @return array
     */
    function getHeader(): array
    {
        return $this->header;
    }

    /**
     * 添加cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @return self
     */
    function setCookie(string $name, $value = '', int $expire = 0): self
    {
        $this->cookie[$name] = [
            $name, $value, $expire,
            $this->getCookieConfig('path'),
            $this->getCookieConfig('domain'),
            Request::getInstance()->isSecure(),
            true
        ];

        return $this;
    }

    /**
     * 发送一个原生cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @return $this
     */
    function setRawCookie(string $name, $value = '', int $expire = 0, $path = '', $domain = '')
    {
        $this->cookie[$name] = [
            $name, $value, $expire, $path, $domain, Request::getInstance()->isSecure(), true
        ];

        return $this;
    }

    /**
     * 获取cookie
     *
     * @return array
     */
    function getCookie(): array
    {
        return $this->cookie;
    }

    /**
     * 删除cookie
     *
     * @param string $name
     * @return self
     */
    function deleteCookie(string $name): self
    {
        $this->setCookie($name, null, -1);
        return $this;
    }

    /**
     * 设置cookie参数默认值
     * <pre>
     * 参数为一个数组, 支持以下参数
     *
     * path 默认为空字符串
     * domain 默认为空字符串
     * secure 默认为false
     * httponly 默认为true
     * </pre>
     *
     * @param array $config
     * @return self
     */
    function cookieConfig(array $config): self
    {
        $this->cookieConfig = $config;
        return $this;
    }

    /**
     * 获取cookie参数默认值
     *
     * @param string $key
     * @return mixed
     */
    function getCookieConfig(string $key)
    {
        static $cookieConfig = null;
        if (null === $cookieConfig) {
            $cookieConfig = ['path' => Delegate::env('cookie.path') ?? '/', 'domain' => Delegate::env('cookie.domain') ?? ''];
            if (!empty($this->cookieConfig)) {
                $cookieConfig = array_merge($cookieConfig, $this->cookieConfig);
            }
        }

        return $cookieConfig[$key] ?? null;
    }

    /**
     * 设置返回头类型
     *
     * @param string $contentType
     * @return self
     */
    function setContentType(string $contentType = 'html'): self
    {
        $this->contentType = strtolower($contentType);
        return $this;
    }

    /**
     * 返回ContentType
     *
     * @return string
     */
    function getContentType(): string
    {
        if (!$this->contentType) {
            $this->setContentType();
        }

        return $this->contentType;
    }

    /**
     * 输出内容
     *
     * @param string $content
     */
    function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * 输出内容
     *
     * @return string
     */
    function getContent(): string
    {
        return $this->content;
    }

    /**
     * basic authentication
     *
     * @param array $users ['user' => 'password']
     * @param string $user PHP_AUTH_USER
     * @param string $password PHP_AUTH_PW
     * @param array $options
     */
    function basicAuth(array $users, string $user, string $password, array $options = []): void
    {
        if (isset($users[$user]) && (0 === strcmp($password, $users[$user]))) {
            return;
        }

        $realm = $options['realm'] ?? 'CP login required';
        $message = $options['fail_msg'] ?? self::$statusDescriptions[401];
        $this->setResponseStatus(401)
            ->setHeader('WWW-Authenticate: Basic realm="' . $realm . '"')
            ->end($message);
    }

    /**
     * digest authentication
     *
     * @param array $users ['user' => 'password']
     * @param string $digest PHP_AUTH_DIGEST
     * @param string $requestMethod REQUEST_METHOD
     * @param array $options
     */
    function digestAuth(array $users, string $digest, string $requestMethod, array $options = []): void
    {
        $realm = $options['realm'] ?? 'CP login required';
        $data = $this->httpDigestParse($digest);
        if (isset($data['username']) && isset($users[$data['username']])) {
            $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
            $A2 = md5($requestMethod . ':' . $data['uri']);
            $validResponse = md5($A1 . ':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' . $A2);
            if (0 === strcmp($validResponse, $data['response'])) {
                return;
            }
        }

        $nonce = $options['nonce'] ?? uniqid();
        $message = $options['fail_msg'] ?? self::$statusDescriptions[401];
        $this->setResponseStatus(401)
            ->setHeader('WWW-Authenticate: Digest realm="' . $realm .
                '",qop="auth",nonce="' . $nonce . '",opaque="' . md5($realm) . '"')
            ->end($message);
    }

    /**
     * 发送http 状态码
     *
     * @param int $code
     * @param string $descriptions
     */
    function sendResponseStatus(int $code = 0, string $descriptions = ''): void
    {
        if (0 === $code) {
            $code = $this->getResponseStatus();
        }

        if ($descriptions == '' && isset(self::$statusDescriptions[$code])) {
            $descriptions = self::$statusDescriptions[$code];
        }

        header("HTTP/1.1 {$code} {$descriptions}");
    }

    /**
     * 发送ContentType
     */
    function sendContentType(): void
    {
        $contentTypeName = $this->getContentType();
        if (isset(self::$mimeTypes [$contentTypeName])) {
            $contentType = self::$mimeTypes [$contentTypeName];
        } elseif ($contentTypeName) {
            $contentType = $contentTypeName;
        } else {
            $contentType = self::$mimeTypes ['html'];
        }

        header("Content-Type: {$contentType}; charset=utf-8");
    }

    /**
     * 发送header
     */
    private function sendHeader(): void
    {
        $contents = $this->getHeader();
        if (!empty($contents)) {
            foreach ($contents as $content) {
                header($content);
            }
        }
    }

    /**
     * 发送cookie
     */
    private function sendCookie(): void
    {
        if (!empty($this->cookie)) {
            foreach ($this->cookie as $cookie) {
                call_user_func_array('setcookie', $cookie);
            }
        }
    }

    /**
     * 输出内容
     *
     * @param mixed $message
     * @param string $tpl
     */
    private function makeResponseContent($message, string $tpl = ''): void
    {
        ob_start();
        if (null !== $tpl && is_file($tpl)) {
            require $tpl;
        } elseif (is_array($message)) {
            var_export($message);
        } else {
            echo $message;
        }

        $this->content = ob_get_clean();
    }

    /**
     * 标识停止Send
     *
     * @param bool $status
     * @return Response
     */
    function setEndFlush(bool $status): self
    {
        $this->endFlush = $status;
        return $this;
    }

    /**
     * 获取标识状态,是否终止输出
     *
     * @return bool
     */
    function isEndFlush(): bool
    {
        return $this->endFlush;
    }

    /**
     * 重定向
     *
     * @param string $url
     * @param int $status
     */
    function redirect(string $url, int $status = 302): void
    {
        $this->setResponseStatus($status)->setHeader("Location: {$url}")->end();
    }

    /**
     * 调用模板输出信息
     *
     * @param string $content
     * @param string $tpl
     */
    function send($content = '', string $tpl = ''): void
    {
        if ($this->isEndFlush()) {
            return;
        }

        if (!headers_sent() && PHP_SAPI != 'cli') {
            $this->sendResponseStatus();
            $this->sendContentType();
            $this->sendCookie();
            $this->sendHeader();
        }

        $this->makeResponseContent($content, $tpl);
        echo $this->content;
    }

    /**
     * 输出内容并添加停止标识
     *
     * @param string $content
     * @param string $tpl
     */
    function end(string $content = '', string $tpl = ''): void
    {
        $this->send($content, $tpl);
        $this->setEndFlush(true);
    }

    /**
     * Parse digest authentication string
     *
     * @param string $txt
     * @return array|bool
     */
    private function httpDigestParse(string $txt)
    {
        $data = [];
        $neededParts = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
        $keys = implode('|', array_keys($neededParts));

        preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $data[$m[1]] = $m[3] ? $m[3] : $m[4];
            unset($neededParts[$m[1]]);
        }

        return $neededParts ? false : $data;
    }

    /**
     * @var array $statusDescriptions
     */
    static public $statusDescriptions = [
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
    ];

    /**
     * @var array $mimeTypes
     */
    static public $mimeTypes = [
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
    ];
}

