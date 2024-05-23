<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Upload;

use Cross\Core\Helper;
use Exception;
use Closure;

/**
 * 多文件上传类
 *
 * @author wonli <wonli@live.com>
 * Class Uploader
 */
class Uploader
{
    /**
     * 待上传文件
     *
     * @var array
     */
    protected array $files;

    /**
     * 目录权限
     *
     * @var int
     */
    protected int $mode = 0755;

    /**
     * 使用文件原名
     *
     * @var bool
     */
    protected bool $useOriginalName = false;

    /**
     * 失败文件数
     *
     * @var int
     */
    protected int $failCount = 0;

    /**
     * 校验没通过和上传失败的文件
     *
     * @var array
     */
    protected array $failFiles = [];

    /**
     * 自定义校验过滤方法
     *
     * @var Closure|null
     */
    protected ?Closure $filterHandle = null;

    /**
     * 过滤数组
     *
     * @var IFilter[]
     */
    protected array $filters;

    /**
     * 允许的文件扩展名
     *
     * @var array
     */
    protected array $allowExtension = [];

    /**
     * 允许的文件大小(默认19.22mb)
     *
     * @var int
     */
    protected int $allowSize = 20150627;

    /**
     * 文件储存路径
     *
     * @var string
     */
    protected string $savePath;

    /**
     * 文件路径(附加到返回路径前)
     *
     * @var string
     */
    protected string $fileDir;

    /**
     * 文件前缀
     *
     * @var string
     */
    protected string $fileNamePrefix;

    /**
     * 是否返回已上传文件真实地址
     *
     * @var bool
     */
    protected bool $withFilePath = false;

    /**
     * 返回文件详情
     *
     * @var bool
     */
    protected bool $withFileInfo = false;

    /**
     * 文件cdn服务器地址
     *
     * @var string
     */
    protected string $fileCdn = '';

    /**
     * 表单文件数组
     *
     * @param array $file 表单上传文件数组
     */
    function addFile(array $file): void
    {
        if (empty($file['tmp_name'])) {
            return;
        }

        if (is_array($file['tmp_name'])) {
            $tempFiles = &$this->files;
            array_walk($file, function ($f, $k) use (&$tempFiles) {
                $i = 0;
                while (null !== ($value = array_shift($f))) {
                    $tempFiles[$i][$k] = $value;
                    $i++;
                }
            });
        } else {
            $this->files[] = $file;
        }
    }

    /**
     * 获取通过验证待上传待文件列表
     *
     * @return array
     */
    function getFiles(): array
    {
        return $this->verifyUploadFile();
    }

    /**
     * 指定目录权限
     *
     * @param int $mode
     */
    function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * 设定允许上传的文件扩展名
     *
     * @param string $extension 竖线分隔，如：gif|jpg|jpeg|png|doc
     */
    function setAllowExtension(string $extension): void
    {
        $this->allowExtension = explode('|', strtolower($extension));
    }

    /**
     * 设定上传文件最大byte
     *
     * @param int $size
     */
    function setAllowSize(int $size): void
    {
        $this->allowSize = $size;
    }

    /**
     * 自定义过滤函数
     *
     * @param Closure $handle 验证通过返回true，失败false
     */
    function setFilterHandle(Closure $handle): void
    {
        $this->filterHandle = $handle;
    }

    /**
     * 添加文件过滤类
     *
     * @param IFilter $filter
     */
    function addFilter(IFilter $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * 保存文件时使用原名
     */
    function useOriginalName(): void
    {
        $this->useOriginalName = true;
    }

    /**
     * 返回上传文件真实地址
     */
    function withFilePath(): void
    {
        $this->withFilePath = true;
    }

    /**
     * 返回上传文件详情
     */
    function withFileInfo(): void
    {
        $this->withFileInfo = true;
    }

    /**
     * 文件CDN服务器地址
     *
     * @param string $server
     */
    function withFileCdn(string $server): void
    {
        $this->fileCdn = rtrim($server, '/');
    }

    /**
     * 设定存储文件路径（基础路径）
     *
     * @param string $path
     * @throws Exception
     */
    function setSavePath(string $path): void
    {
        $this->savePath = rtrim($path, '/') . '/';
        if (!is_dir($this->savePath)) {
            if (!mkdir($this->savePath, $this->mode, true)) {
                throw new Exception('Create path fail');
            }
        }
    }

    /**
     * 设置保存附加路径（可访问路径）
     *
     * @param string $dir
     * @param string $namePrefix 文件名前缀
     */
    function setFireDir(string $dir, string $namePrefix = ''): void
    {
        $this->fileDir = '/' . trim(trim($dir, '\\'), '/') . '/';
        if (!empty($namePrefix)) {
            $this->fileNamePrefix = trim($namePrefix);
        }
    }

    /**
     * 获取文件存储路径
     *
     * @return string
     */
    function getSavePath(): string
    {
        return $this->savePath;
    }

    /**
     * 保存上传文件
     *
     * @return array
     * @throws Exception
     */
    function save(): array
    {
        $data = [
            'files' => [],
            'uploadedCount' => 0,
        ];

        if ($this->fileDir) {
            $this->setSavePath(rtrim($this->savePath, DIRECTORY_SEPARATOR) . $this->fileDir);
        }

        $files = $this->verifyUploadFile();
        if (!empty($files)) {
            foreach ($files as $f) {
                if ($this->useOriginalName) {
                    $fileName = &$f['name'];
                } else {
                    $fileName = Helper::random(16) . '.' . $f['extension'];
                }

                if ($this->fileNamePrefix) {
                    $fileName = $this->fileNamePrefix . '_' . $fileName;
                }

                $fileUrl = $this->fileDir . $fileName;
                $destination = $this->savePath . $fileName;
                $isUpload = move_uploaded_file($f['tmp_name'], $destination);
                if ($isUpload) {
                    $data['uploadedCount']++;
                    $data['files'][] = $fileUrl;

                    if ('' !== $this->fileCdn) {
                        $data['cdnUrl'][] = $this->fileCdn . $fileUrl;
                    }

                    if ($this->withFilePath) {
                        $data['uploadedFilePath'][] = $destination;
                    }

                    if ($this->withFileInfo) {
                        $data['uploadedFileInfo'][] = [
                            'url' => $fileUrl,
                            'path' => $destination,
                            'size' => $f['size'] ?? null,
                            'type' => $f['type'] ?? null
                        ];
                    }
                } else {
                    $this->addFailFile($f['name'], '移动文件至目录失败: ' . $fileUrl);
                }
            }
        }

        if ($this->failCount > 0) {
            $data['failFiles'] = $this->failFiles;
            $data['failCount'] = $this->failCount;
        }

        return $data;
    }

    /**
     * 验证上传文件
     *
     * @return array
     */
    private function verifyUploadFile(): array
    {
        $verifyFiles = [];
        if (empty($this->files)) {
            return $verifyFiles;
        }

        foreach ($this->files as &$f) {
            //无法识别的文件
            if (!is_uploaded_file($f['tmp_name'])) {
                continue;
            }

            if ($f['error'] != 0) {
                $this->addFailFile($f['name'], 'upload error: ' . $f['error']);
                continue;
            }

            $f['extension'] = substr($f['name'], strrpos($f['name'], '.') + 1);
            if (!$this->isAllowExtension($f['extension'])) {
                $this->addFailFile($f['name'], '不支持的文件扩展名: ' . $f['extension']);
                continue;
            }

            if (!$this->isAllowSize($f['size'])) {
                $this->addFailFile($f['name'], '超出允许上传大小: ' . Helper::convert($this->allowSize));
                continue;
            }

            if ($this->filterHandle) {
                $msg = '';
                $v = call_user_func_array($this->filterHandle, [&$f, &$msg]);
                if (!$v) {
                    $this->addFailFile($f['name'], 'filterHandle: ' . ($msg ? $msg : '-'));
                    continue;
                }
            }

            if (!empty($this->filters)) {
                $allFilterMsg = [];
                $passFilterVerify = false;
                foreach ($this->filters as $filter) {
                    $msg = '';
                    $v = call_user_func_array([$filter, 'filter'], [&$f, &$msg]);
                    if ($v) {
                        $passFilterVerify = true;
                        break;
                    } else {
                        $allFilterMsg[] = get_class($filter) . ': ' . ($msg ? $msg : '-');
                    }
                }

                if (!$passFilterVerify) {
                    $this->addFailFile($f['name'], implode(" && ", $allFilterMsg));
                    continue;
                }
            }

            $verifyFiles[] = $f;
        }

        return $verifyFiles;
    }

    /**
     * 检测文件扩展名
     *
     * @param string $type
     * @return bool
     */
    private function isAllowExtension(string $type): bool
    {
        if (empty($this->allowExtension)) {
            return true;
        }

        return in_array(strtolower($type), $this->allowExtension);
    }

    /**
     * 检查上传文件的大小
     *
     * @param int $size
     * @return bool
     */
    private function isAllowSize(int $size): bool
    {
        if (!$this->allowSize) {
            return true;
        }

        return $size < $this->allowSize;
    }

    /**
     * 添加上传失败的文件
     *
     * @param string $filename
     * @param string $error
     */
    private function addFailFile(string $filename, string $error): void
    {
        $this->failCount++;
        $this->failFiles[] = [
            'filename' => $filename,
            'error' => $error,
        ];
    }
}


