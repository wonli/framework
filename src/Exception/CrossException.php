<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Exception;

use Cross\Http\Response;
use ReflectionMethod;
use ReflectionClass;
use SplFileObject;
use Exception;

/**
 * @Auth: wonli <wonli@live.com>
 * Class CrossException
 * @package Cross\Exception
 */
abstract class CrossException extends Exception
{
    /**
     * CrossException constructor.
     *
     * @param string $message
     * @param null|int $code
     * @param Exception|null $previous
     */
    function __construct($message = 'CrossPHP Exception', $code = null, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
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
        $file = $e->getFile();
        $exception_line = $e->getLine();

        $exception_file_source = array();
        $exception_file_info = new SplFileObject($file);
        foreach ($exception_file_info as $line => $code) {
            if ($line <= $exception_line + 7 && $line >= $exception_line - 7) {
                $highlight_code_fragment = highlight_string("<?php{$code}", true);
                $exception_file_source[$line] = str_replace('&lt;?php', '', $highlight_code_fragment);
            }
        }

        $result['main'] = array(
            'file' => $file,
            'line' => $exception_line,
            'message' => $this->hiddenFileRealPath($e->getMessage()),
            'show_file' => $this->hiddenFileRealPath($file),
            'source' => $exception_file_source,
        );

        $trace = $e->getTrace();
        $this->getTraceInfo($trace, $result['trace']);
        if ($e->getPrevious()) {
            $this->getTraceInfo($e->getPrevious()->getTrace(), $result['previous_trace']);
        }

        return $result;
    }

    /**
     * cli模式下的异常处理
     *
     * @param Exception $e
     * @return string
     */
    function cliErrorHandler(Exception $e)
    {
        $trace_table = array();
        $trace = $e->getTrace();
        $this->getCliTraceInfo($trace, $trace_table);

        $previous_trace = array();
        if ($e->getPrevious()) {
            $previous_trace = $e->getPrevious()->getTrace();
            $this->getCliTraceInfo($previous_trace, $trace_table);
        }

        $result['line'] = $e->getLine();
        $result['file'] = $e->getFile();

        $result['trace'] = $trace;
        $result['trace_table'] = $trace_table;
        $result['previous_trace'] = $previous_trace;

        return Response::getInstance()->display($result, __DIR__ . '/_tpl/cli_error.tpl.php');
    }

    /**
     * trace
     *
     * @param array $trace
     * @param $content
     */
    protected function getTraceInfo(array $trace, &$content)
    {
        if (!empty($trace)) {
            $this->alignmentTraceData($trace);
            foreach ($trace as $tn => &$t) {
                $i = 0;
                $trace_file_info = new SplFileObject($t['file']);
                foreach ($trace_file_info as $t_line => $t_code) {
                    if (($t_line <= $t['end_line'] && $t_line >= $t['start_line']) && $i < 16) {
                        $highlight_code_fragment = highlight_string("<?php{$t_code}", true);
                        $t['source'][$t_line] = str_replace('&lt;?php', '', $highlight_code_fragment);
                        $i++;
                    }
                }

                $content[] = $t;
            }
        }
    }

    /**
     * CLI trace
     *
     * @param array $trace
     * @param $trace_table
     */
    protected function getCliTraceInfo(&$trace, &$trace_table)
    {
        if (!empty($trace)) {
            $this->alignmentTraceData($trace);
            foreach ($trace as &$t) {
                foreach ($t as $type_name => &$trace_content) {
                    switch ($type_name) {
                        case 'file':
                        case 'line':
                        case 'function':
                            $line_max_width = max(strlen($type_name), strlen($trace_content));
                            if (!isset($trace_table[$type_name]) || $line_max_width > $trace_table[$type_name]) {
                                if (($line_max_width) % 2 != 0) {
                                    $line_max_width += 9;
                                } else {
                                    $line_max_width += 8;
                                }
                                $trace_table[$type_name] = $line_max_width;
                            }
                            break;
                        default:
                            unset($t[$type_name]);
                    }
                }
            }
        }
    }

    /**
     * 隐藏异常中的真实文件路径
     *
     * @param $path
     * @return mixed
     */
    protected function hiddenFileRealPath($path)
    {
        return str_replace(array(PROJECT_REAL_PATH, CP_PATH, str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT'])),
            array('Project->', 'Cross->', 'Index->'), $path);
    }

    /**
     * 整理trace数据
     *
     * @param array $trace
     */
    private function alignmentTraceData(array &$trace = array())
    {
        foreach ($trace as &$t) {
            if (isset($t['file'])) {
                $t['show_file'] = $this->hiddenFileRealPath($t['file']);
                $t['start_line'] = max(1, $t['line'] - 7);
                $t['end_line'] = $t['line'] + 7;
            } elseif (isset($t['function'])) {
                $rc = new ReflectionClass($t['class']);
                $t['file'] = $rc->getFileName();
                $t['show_file'] = $this->hiddenFileRealPath($rc->getFileName());

                $rf = new ReflectionMethod($t['class'], $t['function']);
                $t['start_line'] = $rf->getStartLine();
                $t['end_line'] = $rf->getEndLine();
                $t['line'] = sprintf("%s ~ %s", $t['start_line'], $t['end_line']);
            } else {
                continue;
            }
        }
    }

    /**
     * 异常处理抽象方法
     *
     * @param Exception $e
     * @return mixed
     */
    abstract protected function errorHandler(Exception $e);
}
