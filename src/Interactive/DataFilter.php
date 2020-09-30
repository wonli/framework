<?php
/**
 * @author wonli <wonli@live.com>
 * DataFilter.php
 */

namespace Cross\Interactive;

use Cross\Lib\Upload\Uploader;
use Cross\Exception\LogicStatusException;
use Cross\Runtime\Rules;
use Cross\Core\Delegate;
use Cross\Core\Helper;

use Throwable;
use Exception;
use Closure;


/**
 * Class DataFilter
 *
 * @package component
 */
class DataFilter
{

    /**
     * 待验证字符串
     *
     * @var mixed
     */
    protected $ctx;

    /**
     * 验证失败时返回的code
     *
     * @var int
     */
    protected $code = 0;

    /**
     * @var string
     */
    protected $msg;

    /**
     * 用户指定状态
     *
     * @var bool
     */
    protected $userState = false;

    /**
     * InputFilter constructor.
     *
     * @param mixed $ctx
     */
    function __construct($ctx)
    {
        $this->msg = null;
        $this->ctx = !is_array($ctx) ? trim($ctx) : $ctx;
    }

    /**
     * 大于0的正整数
     *
     * @return int
     * @throws LogicStatusException
     */
    function id()
    {
        $ctx = $this->uInt();
        if ($ctx == 0) {
            $this->throwMsg('参数必须是大于0的整数');
        }

        return $ctx;
    }

    /**
     * 整型
     *
     * @return int
     * @throws LogicStatusException
     */
    function int()
    {
        if (!is_numeric($this->ctx)) {
            $this->throwMsg('参数必须是一个数字');
        }

        if ($this->ctx > PHP_INT_MAX || $this->ctx < PHP_INT_MIN) {
            $this->throwMsg('参数值超过范围');
        }

        return intval($this->ctx);
    }

    /**
     * 正整数
     *
     * @throws LogicStatusException
     */
    function uInt()
    {
        $ctx = $this->int();
        if ($ctx < 0) {
            $this->throwMsg('参数必须是正整数');
        }

        return $ctx;
    }

    /**
     * 浮点数
     *
     * @return mixed
     * @throws LogicStatusException
     */
    function float()
    {
        if (!is_numeric($this->ctx)) {
            $this->throwMsg('参数必须是一个数字');
        }

        return floatval($this->ctx);
    }

    /**
     * 限定值
     *
     * @param array $val
     * @return mixed
     * @throws LogicStatusException
     */
    function fixed(...$val)
    {
        if (!in_array($this->ctx, $val)) {
            $this->throwMsg('参数必须是指定值中的一个');
        }

        return $this->ctx;
    }

    /**
     * 限定范围
     *
     * @param int $min
     * @param int $max
     * @return int|null
     * @throws LogicStatusException
     */
    function range(int $min, int $max)
    {
        $ctx = $this->int();
        if ($ctx < $min || $ctx > $max) {
            $this->throwMsg('参数值范围 %d ~ %d', $min, $max);
        }

        return $ctx;
    }

    /**
     * 限定参数长度（支持中文）
     *
     * @param int $min
     * @param int $max
     * @return string
     * @throws LogicStatusException
     */
    function length(int $min, int $max)
    {
        $ctx = $this->val();
        $len = Helper::strLen($ctx);
        if ($len < $min || $len > $max) {
            $this->throwMsg('参数长度 %d ~ %d', $min, $max);
        }

        return $ctx;
    }

    /**
     * 验证字母
     *
     * @throws LogicStatusException
     */
    function alpha()
    {
        return $this->regx('/[a-zA-Z]/', '参数只能是字母');
    }

    /**
     * 字母数字或下划线
     *
     * @param int $min 最小长度
     * @param int $max 最大长度
     * @return array|mixed
     * @throws LogicStatusException
     */
    function account(int $min = 2, int $max = 12)
    {
        $pattern = sprintf("/^(?!_)[a-zA-Z_0-9]{%d,%d}$/u", $min, $max);
        return $this->regx($pattern, sprintf('参数只能是 %d~%d 位字母数字或下划线（且不能以下划线开头）', $min, $max));
    }

    /**
     * 是否是一个绝对路径
     *
     * @return string|void
     * @throws LogicStatusException
     */
    function url()
    {
        $ctx = $this->val();
        $defines = ['http://' => 7, 'https://' => 8, '//' => 2];
        foreach ($defines as $prefix => $pos) {
            $compare = substr_compare($ctx, $prefix, 0, $pos, true);
            if (0 === $compare) {
                return strtolower($ctx);
            }
        }

        $this->throwMsg('参数必须是绝对地址');
    }

    /**
     * 中文
     *
     * @return mixed
     * @throws LogicStatusException
     */
    function chinese()
    {
        if (!Helper::isChinese($this->ctx)) {
            $this->throwMsg('参数必须是中文');
        }

        return $this->ctx;
    }

    /**
     * 手机号
     *
     * @return array|mixed|string
     * @throws LogicStatusException
     */
    function mobile()
    {
        if (!Helper::isMobile($this->ctx)) {
            $this->throwMsg('参数不是一个正确的手机号码');
        }

        return $this->ctx;
    }

    /**
     * 数组
     *
     * @param mixed $delimiter 分隔符
     * @return array|mixed
     * @throws LogicStatusException
     */
    function array($delimiter = null)
    {
        $ctx = $this->ctx;
        if (null !== $delimiter) {
            $ctx = explode($delimiter, $ctx);
        }

        if (!is_array($ctx)) {
            $this->throwMsg('参数必须是一个数组');
        }

        return array_map('trim', $ctx);
    }

    /**
     * json
     *
     * @param bool $array
     * @param int $depth
     * @param int $options
     * @return array|mixed|string
     * @throws LogicStatusException
     */
    function json(bool $array = true, int $depth = 128, int $options = 0)
    {
        if (is_array($this->ctx)) {
            return $this->ctx;
        }

        $ctx = $this->filter(FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $json = json_decode($ctx, true, $depth, $options);
        if (false === $json || null === $json || !is_array($json)) {
            $this->throwMsg('参数必须是一个json: %s(%d)', json_last_error_msg(), json_last_error());
        }

        return $array ? $json : $ctx;
    }

    /**
     * 正则匹配
     *
     * @param string $pattern
     * @param string|null $msg
     * @return mixed
     * @throws LogicStatusException
     */
    function regx(string $pattern, string $msg = null)
    {
        if (!preg_match($pattern, $this->ctx)) {
            $this->throwMsg($msg ?: '参数正则验证失败');
        }

        return $this->ctx;
    }

    /**
     * 自定义函数验证
     *
     * @param Closure $handler
     * @return mixed
     * @throws LogicStatusException
     */
    function closure(Closure $handler)
    {
        try {
            $v = $handler($this->ctx, $this);
            if (false === $v) {
                $this->throwMsg('false');
            }

            return $v;
        } catch (Throwable $e) {
            $this->throwMsg('参数用户验证异常：%s', $e->getMessage());
            return false;
        }
    }

    /**
     * 规则验证
     *
     * @param string $name
     * @param mixed $val
     * @return mixed
     * @throws LogicStatusException
     */
    function rule(string $name, &$val = null)
    {
        try {
            $val = Rules::match($name, $this->ctx);
            if (false === $val) {
                $this->throwMsg('false');
            }

            return $val;
        } catch (Exception $e) {
            $this->throwMsg('规则验证异常: %s', $e->getMessage());
            return false;
        }
    }

    /**
     * 验证日期
     *
     * @param null $unixTime
     * @return false|string
     * @throws LogicStatusException
     */
    function date(&$unixTime = null)
    {
        $unixTime = strtotime($this->ctx);
        if (!$unixTime) {
            $this->throwMsg('请输入正确的日期');
        }

        return $this->ctx;
    }

    /**
     * 验证email
     *
     * @param string $addValidExpr
     * @return mixed
     * @throws LogicStatusException
     */
    function email($addValidExpr = "/^[a-zA-Z0-9]([\w\-\.]?)+/")
    {
        if (!Helper::validEmail($this->ctx, $addValidExpr)) {
            $this->throwMsg('电子邮件地址验证失败');
        }

        return $this->ctx;
    }

    /**
     * 验证身份证
     *
     * @param bool $justCheckLength
     * @return mixed
     * @throws LogicStatusException
     */
    function idCard($justCheckLength = false)
    {
        try {
            if (!Helper::checkIDCard($this->ctx, $justCheckLength)) {
                $this->throwMsg('身份证验证失败');
            }
        } catch (Exception $e) {
            $this->throwMsg('身份证验证异常 %s', $e->getMessage());
        }

        return $this->ctx;
    }

    /**
     * 默认值
     *
     * @param mixed $val
     * @return mixed
     */
    function default($val)
    {
        if (null === $this->ctx) {
            return $val;
        }

        return $this->ctx;
    }

    /**
     * 获取原始入参
     *
     * @return mixed
     */
    function raw()
    {
        return $this->ctx;
    }

    /**
     * 转义后的原始参数
     *
     * @param bool $stripTags
     * @return string
     */
    function val($stripTags = true)
    {
        $ctx = $stripTags ? strip_tags($this->ctx) : $this->ctx;
        return htmlentities($ctx, ENT_COMPAT, 'utf-8');
    }

    /**
     * 上传验证
     *
     * @param Uploader $uploader
     * @return array
     * @throws LogicStatusException
     */
    function uploader(Uploader $uploader): array
    {
        $uploader->addFile($this->ctx);
        try {
            return $uploader->save();
        } catch (Exception $e) {
            $this->throwMsg('上传出错: %s', $e->getMessage());
        }

        return [];
    }

    /**
     * @param int $filter
     * @param null $options
     * @return mixed
     * @see filter_var
     */
    function filter($filter = FILTER_DEFAULT, $options = null)
    {
        return filter_var($this->ctx, $filter, $options);
    }

    /**
     * 通过所有验证方法
     *
     * @param array $handler 方法名:参数1,参数2
     * @return mixed
     * @throws LogicStatusException
     */
    function all(...$handler)
    {
        foreach ($handler as $act) {
            if (false !== strpos($act, ':')) {
                list($act, $actParamsSet) = explode(':', $act);
                if (!empty($actParamsSet)) {
                    $params = array_map('trim', explode(',', $actParamsSet));
                }
            }

            if (!method_exists($this, $act)) {
                $this->throwMsg('不支持的验证方法 %s', $act);
            }

            try {
                if (!empty($params)) {
                    call_user_func_array([$this, $act], $params);
                } else {
                    call_user_func([$this, $act]);
                }
            } catch (Exception $e) {
                $this->throwMsg('验证异常: %s', $e->getMessage());
            }
        }

        return $this->ctx;
    }

    /**
     * 满足任意规则
     *
     * @param mixed ...$handler 方法名:参数1,参数2
     * @return mixed
     * @throws LogicStatusException
     */
    function any(...$handler)
    {
        $acts = [];
        foreach ($handler as $act) {
            if (false !== strpos($act, ':')) {
                list($act, $actParamsSet) = explode(':', $act);
                if (!empty($actParamsSet)) {
                    $params = array_map('trim', explode(',', $actParamsSet));
                }
            }

            if (!method_exists($this, $act)) {
                $this->throwMsg('不支持的验证方法 %s', $act);
            }

            $acts[] = $act;
            try {
                if (!empty($params)) {
                    call_user_func_array([$this, $act], $params);
                } else {
                    call_user_func([$this, $act]);
                }

                return $this->ctx;
            } catch (Exception $e) {

            }
        }

        $this->throwMsg('验证失败: %s', implode(',', $acts));
        return false;
    }

    /**
     * 自定义验证失败时的消息
     *
     * @param int $code
     * @param string|null $msg
     * @return $this
     */
    function msg(int $code, string $msg = null)
    {
        $this->code = $code;
        $this->msg = $msg;
        $this->userState = true;
        return $this;
    }

    /**
     * 抛出异常信息
     *
     * @param null $msg
     * @param mixed ...$params
     * @throws LogicStatusException
     */
    function throwMsg($msg, ...$params)
    {
        $msgCtx = null;
        if (!$this->userState) {
            $msgCtx = sprintf($msg, ...$params);
        } elseif ($this->msg) {
            $msgCtx = $this->msg;
        }

        $code = Delegate::env('sys.filterFailStatus') ?? $this->code;
        throw new LogicStatusException($code, $msgCtx);
    }

    /**
     * toString
     * @return mixed
     */
    function __toString()
    {
        return $this->val();
    }
}