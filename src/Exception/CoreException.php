<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Exception;


use Exception;

/**
 * @author wonli <wonli@live.com>
 * Class CoreException
 * @package Cross\Exception
 */
class CoreException extends CrossException
{
    /**
     * CoreException constructor.
     *
     * @param string $message
     * @param null $code HTTP状态码
     * @param Exception|null $previous
     */
    function __construct($message = 'CrossPHP Exception', $code = null, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->httpStatusCode = $code;
    }
}
