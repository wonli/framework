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
    protected int $httpStatusCode = 500;

    /**
     * 是否返回JSON格式的异常信息
     *
     * @var bool
     */
    protected bool $responseJSONExceptionMsg = false;

    /**
     * 响应数据
     *
     * @var ResponseData
     */
    protected ResponseData $ResponseData;

    /**
     * CrossException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    function __construct(string $message = 'CrossPHP Exception', int $code = 0, Throwable $previous = null)
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
    function cpExceptionSource(Throwable $e): array
    {
        $file = $e->getFile();
        $exceptionLine = $e->getLine();

        $exceptionFileSource = [];
        $exceptionFileInfo = new SplFileObject($file);
        foreach ($exceptionFileInfo as $line => $code) {
            $line += 1;
            if ($line <= $exceptionLine + 6 && $line >= $exceptionLine - 6) {
                $exceptionFileSource[$line] = self::highlightCode($code);
            }
        }

        $result['main'] = [
            'file' => $file,
            'line' => $exceptionLine,
            'message' => $this->hiddenFileRealPath($e->getMessage()),
            'show_file' => $this->hiddenFileRealPath($file),
            'source' => $exceptionFileSource,
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
        $traceTable = [];
        $trace = $e->getTrace();
        $this->getCliTraceInfo($trace, $traceTable);

        $previousTrace = [];
        if ($e->getPrevious()) {
            $previousTrace = $e->getPrevious()->getTrace();
            $this->getCliTraceInfo($previousTrace, $traceTable);
        }

        $result['line'] = $e->getLine();
        $result['file'] = $e->getFile();
        $result['message'] = $e->getMessage();

        $result['trace'] = $trace;
        $result['trace_table'] = $traceTable;
        $result['previous_trace'] = $previousTrace;

        Response::getInstance()->setRawContent($result, __DIR__ . '/tpl/cli_error.tpl.php')->send();
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
            if (null !== $this->ResponseData) {
                $ResponseData = $this->ResponseData;
            } else {
                $ResponseData = ResponseData::builder();
                $ResponseData->setStatus($e->getCode());
                $ResponseData->setMessage($e->getMessage());
            }

            $Response->setResponseStatus($this->httpStatusCode)
                ->setRawContent(json_encode($ResponseData->getData(), JSON_UNESCAPED_UNICODE))->send();
        } else {
            $exceptionMsg = $this->cpExceptionSource($e);
            $Response->setResponseStatus($this->httpStatusCode)
                ->setRawContent($exceptionMsg, __DIR__ . '/tpl/front_error.tpl.php')->send();
        }
    }

    /**
     * 设置扩展数据
     *
     * @param mixed $data
     */
    function addResponseData(ResponseData $data): void
    {
        $this->ResponseData = $data;
    }

    /**
     * 获取扩展数据
     *
     * @return ResponseData
     */
    function getResponseData(): ResponseData
    {
        return $this->ResponseData;
    }

    /**
     * 设置HTTP状态码
     *
     * @param int $code
     */
    function setHttpStatusCode(int $code): void
    {
        $this->httpStatusCode = $code;
    }

    /**
     * @return int
     */
    function getHttpStatusCode(): int
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
                $traceFileInfo = new SplFileObject($t['file']);
                foreach ($traceFileInfo as $line => $code) {
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
     * @param $traceTable
     */
    protected function getCliTraceInfo(array &$trace, &$traceTable): void
    {
        if (!empty($trace)) {
            $this->alignmentTraceData($trace);
            foreach ($trace as &$t) {
                foreach ($t as $typeName => &$traceContent) {
                    switch ($typeName) {
                        case 'file':
                        case 'line':
                        case 'function':
                            $lineMaxWidth = max(strlen($typeName), strlen($traceContent));
                            if (($lineMaxWidth % 2) != 0) {
                                $lineMaxWidth += 5;
                            } else {
                                $lineMaxWidth += 4;
                            }

                            if (!isset($traceTable[$typeName]) || $lineMaxWidth > $traceTable[$typeName]) {
                                $traceTable[$typeName] = $lineMaxWidth;
                            }
                            break;
                        default:
                            unset($t[$typeName]);
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

        $highlightCodeFragment = highlight_string("<?php {$code}", true);
        return str_replace('&lt;?php', '', $highlightCodeFragment);
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
