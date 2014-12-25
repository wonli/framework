<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.1.0
 */
namespace Cross\Exception;

use Cross\Core\Response;
use Exception;
use SplFileObject;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CrossException
 * @package Cross\Exception
 */
abstract class CrossException extends Exception
{
    function __construct($message = 'Crossphp Framework Exception', $code = null)
    {
        parent::__construct($message, $code);
        if (PHP_SAPI === 'cli') {
            set_exception_handler(array($this, 'cliErrorHandler'));
        } else {
            set_exception_handler(array($this, 'errorHandler'));
        }
    }

    /**
     * 根据trace信息分析源码,生成异常处理详细数据
     *
     * @param Exception $e
     * @return array
     */
    function cpExceptionSource(Exception $e)
    {
        $trace = $e->getTrace();

        $result = array();
        $result['main'] = array('file' => $e->getFile(), 'line' => $e->getLine(), 'message' => $e->getMessage());
        $result['main']['show_file'] = $this->hiddenFileRealPath($result['main']['file']);

        foreach ($result as &$_i) {
            $file = new SplFileObject($_i['file']);

            foreach ($file as $line => $code) {
                if ($line < $_i['line'] + 6 && $line > $_i['line'] - 7) {
                    $h_string = highlight_string("<?php{$code}", true);
                    $_i['source'][$line] = str_replace("&lt;?php", "", $h_string);
                }
            }
        }

        if (!empty($trace)) {
            foreach ($trace as $tn => & $t) {
                if (isset($t['file'])) {
                    $trace_fileinfo = new SplFileObject($t['file']);
                    $t['show_file'] = $this->hiddenFileRealPath($t['file']);
                    foreach ($trace_fileinfo as $t_line => $t_code) {
                        if ($t_line < $t['line'] + 6 && $t_line > $t['line'] - 7) {
                            $h_string = highlight_string("<?php{$t_code}", true);
                            $t['source'][$t_line] = str_replace("&lt;?php", "", $h_string);
                        }
                    }
                    $result ['trace'] [$tn] = $t;
                }
            }
        }

        return $result;
    }

    /**
     * 隐藏异常中的真实文件路径
     *
     * @param $path
     * @return mixed
     */
    protected function hiddenFileRealPath($path)
    {
        return str_replace(array(PROJECT_REAL_PATH, CP_PATH), array('Project->', 'Cross->'), $path);
    }

    /**
     * cli模式下的异常处理
     *
     * @param Exception $e
     * @return string
     */
    function cliErrorHandler(Exception $e)
    {
        $trace = $e->getTrace();
        $trace_table = array();
        if (!empty($trace)) {
            foreach ($trace as & $t) {

                if (isset($t['file'])) {
                    $t['file'] = $this->hiddenFileRealPath($t['file']);
                }

                foreach ($t as $t_key => $t_info) {
                    switch ($t_key) {
                        case 'file':
                        case 'line':
                        case 'function':
                            $t_info_length = max(strlen($t_key), strlen($t_info));
                            if (!isset($trace_table[$t_key]) || $t_info_length > $trace_table[$t_key]) {
                                if (($t_info_length) % 2 != 0) {
                                    $t_info_length += 5;
                                } else {
                                    $t_info_length += 4;
                                }
                                $trace_table[$t_key] = $t_info_length;
                            }
                            break;
                    }
                }
            }
        }

        $result ['line'] = $e->getLine();
        $result ['file'] = $this->hiddenFileRealPath($e->getFile());

        $result ['trace'] = $trace;
        $result ['trace_table'] = $trace_table;

        return Response::getInstance()->display($result, __DIR__ . '/_tpl/cli_error.tpl.php');
    }

    /**
     * 异常处理抽象方法
     *
     * @param Exception $e
     * @return mixed
     */
    abstract protected function errorHandler(Exception $e);
}
