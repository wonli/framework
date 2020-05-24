<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Interactive;


/**
 * 统一控制器输出数据
 *
 * Class ResponseData
 * @package Cross\MVC
 */
class ResponseData
{
    protected $status = 1;
    protected $message = '';
    protected $data = [];

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * 赋值
     *
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * 添加数据
     *
     * @param string $key
     * @param mixed $data
     */
    public function addData(string $key, $data): void
    {
        $this->data[$key] = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = [
            'status' => $this->status,
            'message' => $this->message
        ];

        if (!empty($this->data)) {
            $data['data'] = $this->data;
        }

        return $data;
    }
}