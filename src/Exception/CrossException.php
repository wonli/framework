<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Exception;

use Cross\Interactive\ResponseData;
use Cross\Http\Response;

use ReflectionMethod;
use ReflectionClass;
use SplFileObject;
use Exception;
use Throwable;

/**
 * @author wonli <wonli@live.com>
 * Class CrossException
 * @package Cross\Exception
 */
abstract class CrossException extends Exception
{
    /**
     * HTTP状态码
     *
     * @var int
     */
    protected $httpStatusCode = 500;

    /**
     * 是否返回JSON格式的异常信息
     *
     * @var bool
     */
    protected $responseJSONExceptionMsg = false;

    /**
     * 扩展数据
     *
     * @var array
     */
    protected $extData = [];

    /**
     * CrossException constructor.
     *
     * @param string $message
     * @param null|int $code
     * @param Throwable|null $previous
     */
    function __construct(string $message = 'CrossPHP Exception', int $code = null, Throwable $previous = null)
    {
        if (PHP_SAPI === 'cli') {
            set_exception_handler(array($this, 'cliErrorHandler'));
        } else {
            set_exception_handler(array($this, 'errorHandler'));
        }

        $contentType = Response::getInstance()->getContentType();
        if (0 === strcasecmp($contentType, 'JSON')) {
            $this->responseJSONExceptionMsg = true;
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * 根据trace信息分析源码,生成异常处理详细数据
     *
     * @param Throwable $e
     * @return array
     */
    function cpExceptionSource(Throwable $e)
    {
        $file = $e->getFile();
        $exception_line = $e->getLine();

        $exception_file_source = [];
        $exception_file_info = new SplFileObject($file);
        foreach ($exception_file_info as $line => $code) {
            $line += 1;
            if ($line <= $exception_line + 6 && $line >= $exception_line - 6) {
                $exception_file_source[$line] = self::highlightCode($code);
            }
        }

        $result['main'] = [
            'file' => $file,
            'line' => $exception_line,
            'message' => $this->hiddenFileRealPath($e->getMessage()),
            'show_file' => $this->hiddenFileRealPath($file),
            'source' => $exception_file_source,
        ];

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
     * @param Throwable $e
     */
    function cliErrorHandler(Throwable $e): void
    {
        $trace_table = [];
        $trace = $e->getTrace();
        $this->getCliTraceInfo($trace, $trace_table);

        $previous_trace = [];
        if ($e->getPrevious()) {
            $previous_trace = $e->getPrevious()->getTrace();
            $this->getCliTraceInfo($previous_trace, $trace_table);
        }

        $result['line'] = $e->getLine();
        $result['file'] = $e->getFile();
        $result['message'] = $e->getMessage();

        $result['trace'] = $trace;
        $result['trace_table'] = $trace_table;
        $result['previous_trace'] = $previous_trace;

        Response::getInstance()->send($result, __DIR__ . '/tpl/cli_error.tpl.php');
    }

    /**
     * 异常处理方法
     *
     * @param Throwable $e
     */
    function errorHandler(Throwable $e): void
    {
        $Response = Response::getInstance();
        if ($this->responseJSONExceptionMsg) {
            $ResponseData = new ResponseData();
            $ResponseData->setStatus($e->getCode());
            $ResponseData->setMessage($e->getMessage());
            if (!empty($this->extData)) {
                $ResponseData->setData((array)$this->extData);
            }

            $Response->setResponseStatus($this->httpStatusCode)
                ->send(json_encode($ResponseData->getData(), JSON_UNESCAPED_UNICODE));
        } else {
            $exceptionMsg = $this->cpExceptionSource($e);
            $Response->setResponseStatus($this->httpStatusCode)
                ->send($exceptionMsg, __DIR__ . '/tpl/front_error.tpl.php');
        }
    }

    /**
     * 设置扩展数据
     *
     * @param mixed $data
     */
    function addExtData($data)
    {
        $this->extData = $data;
    }

    /**
     * 获取扩展数据
     *
     * @return array
     */
    function getExtData()
    {
        return $this->extData;
    }

    /**
     * @return int
     */
    function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * trace
     *
     * @param array $trace
     * @param $content
     */
    protected function getTraceInfo(array $trace, &$content): void
    {
        if (!empty($trace)) {
            $this->alignmentTraceData($trace);
            foreach ($trace as $tn => &$t) {
                if (!isset($t['file'])) {
                    continue;
                }

                $i = 0;
                $trace_file_info = new SplFileObject($t['file']);
                foreach ($trace_file_info as $line => $code) {
                    $line += 1;
                    if (($line <= $t['end_line'] && $line >= $t['start_line']) && $i < 16) {
                        $t['source'][$line] = self::highlightCode($code);
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
    protected function getCliTraceInfo(&$trace, &$trace_table): void
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
                            if (($line_max_width % 2) != 0) {
                                $line_max_width += 5;
                            } else {
                                $line_max_width += 4;
                            }

                            if (!isset($trace_table[$type_name]) || $line_max_width > $trace_table[$type_name]) {
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
     * @param string $path
     * @return mixed
     */
    protected function hiddenFileRealPath(string $path): string
    {
        return str_replace([PROJECT_REAL_PATH, CP_PATH], ['Project->', 'Cross->'], $path);
    }

    /**
     * 高亮代码
     *
     * @param string $code
     * @return mixed
     */
    private static function highlightCode(string $code): string
    {
        $code = rtrim($code);
        if (0 === strcasecmp(substr($code, 0, 5), '<?php ')) {
            return highlight_string($code, true);
        }

        $highlight_code_fragment = highlight_string("<?php {$code}", true);
        return str_replace('&lt;?php', '', $highlight_code_fragment);
    }

    /**
     * 整理trace数据
     *
     * @param array $trace
     */
    private function alignmentTraceData(array &$trace = []): void
    {
        foreach ($trace as &$t) {
            if (isset($t['file'])) {
                $t['show_file'] = $this->hiddenFileRealPath($t['file']);
                $t['start_line'] = max(1, $t['line'] - 6);
                $t['end_line'] = $t['line'] + 6;
            } elseif (isset($t['function']) && isset($t['class'])) {
                try {
                    $rc = new ReflectionClass($t['class']);
                    $t['file'] = $rc->getFileName();
                    $t['show_file'] = $this->hiddenFileRealPath($rc->getFileName());

                    $rf = new ReflectionMethod($t['class'], $t['function']);
                    $t['start_line'] = $rf->getStartLine();
                    $t['end_line'] = $rf->getEndLine();
                    $t['line'] = sprintf("%s ~ %s", $t['start_line'], $t['end_line']);
                } catch (Exception $e) {
                    continue;
                }
            } else {
                continue;
            }
        }
    }
}
