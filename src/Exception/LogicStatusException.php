<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Exception;


use Cross\Interactive\ResponseData;
use Cross\Core\Delegate;
use Cross\Core\Loader;
use Throwable;

/**
 * 逻辑状态异常
 *
 * @author wonli <wonli@live.com>
 * Class LogicStatusException
 * @package Cross\Exception
 */
class LogicStatusException extends CrossException
{
    protected $httpStatusCode = 200;

    /**
     * LogicStatusException constructor.
     *
     * @param int|null $code
     * @param string|null $msg
     */
    function __construct(int $code = null, string $msg = null)
    {
        if (1 !== $code) {
            try {
                if (null === $msg) {
                    $statusConfigFile = Delegate::env('sys.status') ?? 'status.config.php';
                    if (!file_exists($statusConfigFile) && defined('PROJECT_REAL_PATH')) {
                        $statusConfigFile = PROJECT_REAL_PATH . 'config' . DIRECTORY_SEPARATOR . $statusConfigFile;
                    }

                    $statusMsg = Loader::read($statusConfigFile);
                    $message = $statusMsg[$code] ?? $code;
                } else {
                    $message = $msg;
                }

                $rpd = ResponseData::builder();
                $rpd->setStatus($code);
                $rpd->setMessage($message);
                parent::addResponseData($rpd);

                parent::__construct($message, $code);
            } catch (Throwable $e) {
                parent::__construct($e->getMessage(), $code);
            }
        }
    }
}
