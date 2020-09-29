<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Exception;


use Cross\Interactive\ResponseData;
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
        try {
            if (null === $this->ResponseData) {
                $rpd = ResponseData::builder();
                $rpd->setStatus($code);
                $rpd->setMessage($msg ?? '');
                parent::addResponseData($rpd);
            }

            parent::__construct($this->getResponseData()->getMessage(), $code);
        } catch (Throwable $e) {
            parent::__construct($e->getMessage(), $code);
        }
    }
}
