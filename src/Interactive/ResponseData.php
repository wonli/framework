<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Interactive;


/**
 * 响应数据类
 *
 * Class ResponseData
 * @package Cross\MVC
 */
class ResponseData
{
    /**
     * 状态
     *
     * @var int
     */
    protected $status = 1;

    /**
     * 消息
     *
     * @var string
     */
    protected $message = '';

    /**
     * 数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 状态名称
     *
     * @var string
     */
    private $statusName = 'status';

    /**
     * 消息名称
     *
     * @var string
     */
    private $messageName = 'message';

    /**
     * 数据名称
     *
     * @var string
     */
    private $dataName = 'data';

    /**
     * @var static
     */
    private static $instance;

    /**
     * ResponseData constructor.
     */
    private function __construct()
    {
        $this->data = [];
    }

    /**
     * @return static
     */
    public static function builder(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 返回数据
     *
     * @param bool $getContent 是否获取数据内容
     * @return array
     */
    public function getData(bool $getContent = true): array
    {
        $data = [
            $this->statusName => $this->status,
            $this->messageName => $this->message
        ];

        if ($getContent && !empty($this->data)) {
            $data[$this->dataName] = $this->data;
        }

        return $data;
    }

    /**
     * 获取数据内容
     *
     * @return array
     */
    public function getDataContent(): array
    {
        return $this->data;
    }

    /**
     * 更新状态和消息属性
     *
     * @param array $data
     */
    public function updateInfoProperty(array &$data): void
    {
        if (isset($data[$this->statusName])) {
            $this->setStatus($data[$this->statusName]);
            unset($data[$this->statusName]);
        }

        if (isset($data[$this->messageName])) {
            $this->setMessage($data[$this->messageName]);
            unset($data[$this->messageName]);
        }
    }

    /**
     * 数据内容
     *
     * @param array $data
     * @param bool $merge 默认不合并数据
     */
    public function setData(array $data, $merge = false): void
    {
        if ($merge && !empty($this->data)) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data = $data;
        }
    }

    /**
     * 添加数据
     *
     * @param string $key
     * @param mixed $value
     */
    public function addData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getStatusName(): string
    {
        return $this->statusName;
    }

    /**
     * @param string $statusName
     * @return string
     */
    public function setStatusName(string $statusName): string
    {
        $this->statusName = $statusName;
        return $statusName;
    }

    /**
     * @return string
     */
    public function getMessageName(): string
    {
        return $this->messageName;
    }

    /**
     * @param string $messageName
     * @return string
     */
    public function setMessageName(string $messageName): string
    {
        $this->messageName = $messageName;
        return $messageName;
    }

    /**
     * @return string
     */
    public function getDataName(): string
    {
        return $this->dataName;
    }

    /**
     * @param string $dataName
     * @return string
     */
    public function setDataName(string $dataName): string
    {
        $this->dataName = $dataName;
        return $dataName;
    }
}