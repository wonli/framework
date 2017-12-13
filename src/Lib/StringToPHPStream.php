<?php
/**
 * @author wonli <wonli@live.com>
 * StringToPHPStream.php
 */

namespace Cross\Lib;

/**
 * 字符串php代码通过wrapper转换为可以执行的php代码
 * <pre>
 * 使用方式 stream_register_wrapper("自定义名字", "stringToPHPStream")
 * $var = include ("自定义名字://字符串代码")
 * </pre>
 *
 * @author wonli <wonli@live.com>
 * Class StringToPHPStream
 * @package Cross\Lib\Other
 */
class StringToPHPStream
{

    /**
     * 代码内容
     *
     * @var array
     */
    static $content;

    /**
     * 在$content中的标示
     *
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $pos;

    /**
     * @param $path
     * @param $mode
     * @param $options
     * @param $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, $opened_path)
    {
        $this->key = md5($path);
        if (!isset(self::$content[$this->key])) {
            self::$content[$this->key] = sprintf('<?php return %s;', substr($path, 11));
        }

        $this->pos = 0;
        return true;
    }

    /**
     * @param $count
     * @return string
     */
    public function stream_read($count)
    {
        $content = self::$content[$this->key];
        $ret = substr($content, $this->pos, $count);
        $this->pos += strlen($ret);
        return $ret;
    }

    /**
     *
     */
    public function stream_stat()
    {

    }

    /**
     *
     */
    public function stream_eof()
    {

    }
}


