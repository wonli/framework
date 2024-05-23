<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Core;

use Cross\Exception\CoreException;

/**
 * @author wonli <wonli@live.com>
 * Class Loader
 * @package Cross\Core
 */
class Loader
{
    /**
     * @var self|null
     */
    private static ?Loader $instance = null;

    /**
     * 已注册的命名空间
     *
     * @var array
     */
    private static array $namespace = [];

    /**
     * 已加载类的文件列表
     *
     * @var array
     */
    private static array $loaded = [];

    /**
     * 初始化Loader
     */
    private function __construct()
    {
        spl_autoload_register([$this, 'loadClass']);
        spl_autoload_register([$this, 'loadPSRClass']);
    }

    /**
     * 单例模式
     *
     * @return Loader
     */
    static function init(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 载入文件
     *
     * @param array|string $file
     * @return bool
     * @throws CoreException
     */
    static function import(array|string $file): bool
    {
        return self::requireFile(PROJECT_REAL_PATH . $file, true);
    }

    /**
     * 读取指定的单一文件
     *
     * @param string $file
     * @param bool $getFileContent 是否读取文件文本内容
     * @return mixed
     * @throws CoreException
     */
    static function read(string $file, bool $getFileContent = false): mixed
    {
        if (!file_exists($file)) {
            throw new CoreException("{$file} 文件不存在");
        }

        static $cache = null;
        $flag = (int)$getFileContent;
        if (isset($cache[$file][$flag])) {
            return $cache[$file][$flag];
        }

        if (is_readable($file)) {
            if ($getFileContent) {
                $fileContent = file_get_contents($file);
                $cache[$file][$flag] = $fileContent;
                return $fileContent;
            }

            switch (Helper::getExt($file)) {
                case 'php' :
                    $data = require $file;
                    $cache[$file][$flag] = $data;
                    break;

                case 'json' :
                    $data = json_decode(file_get_contents($file), true);
                    $cache[$file][$flag] = $data;
                    break;

                case 'ini':
                    $data = parse_ini_file($file, true);
                    $cache[$file][$flag] = $data;
                    break;

                default :
                    throw new CoreException('不支持的解析格式');
            }

            return $data;
        } else {
            throw new CoreException("读取文件失败:{$file}");
        }
    }

    /**
     * 获取已注册的命名空间
     *
     * @return array
     */
    static function getNamespaceMap(): array
    {
        return self::$namespace;
    }

    /**
     * 注册命名空间
     *
     * @param string $prefix 名称
     * @param string $baseDir 源文件绝对路径
     * @param bool $prepend
     */
    static function registerNamespace(string $prefix, string $baseDir, bool $prepend = false): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        if (isset(self::$namespace[$prefix]) === false) {
            self::$namespace[$prefix] = [];
        }

        if ($prepend) {
            array_unshift(self::$namespace[$prefix], $baseDir);
        } else {
            self::$namespace[$prefix][] = $baseDir;
        }
    }

    /**
     * 自动加载
     *
     * @param string $className
     * @return string
     * @throws CoreException
     */
    private function loadClass(string $className): string
    {
        $prefix = '';
        $pos = strpos($className, '\\');
        if (false !== $pos) {
            $prefix = substr($className, 0, $pos);
            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        }

        $checkFileExists = true;
        if ('' !== $prefix && 0 === strcasecmp($prefix, 'cross')) {
            $checkFileExists = false;
            $classFile = CP_PATH . substr($className, $pos + 1) . '.php';
        } else {
            $classFile = PROJECT_REAL_PATH . $className . '.php';
        }

        $this->requireFile($classFile, false, $checkFileExists);
        return $classFile;
    }

    /**
     * PSR-4
     *
     * @param string $class
     * @return bool|string
     * @throws CoreException
     */
    private function loadPSRClass(string $class): bool|string
    {
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }
            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * 匹配已注册的命名空间,require文件
     *
     * @param string $prefix
     * @param string $relativeClass
     * @return bool|string
     * @throws CoreException
     */
    private function loadMappedFile(string $prefix, string $relativeClass): bool|string
    {
        if (isset(self::$namespace[$prefix]) === false) {
            return false;
        }

        foreach (self::$namespace[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * require文件
     *
     * @param string $file
     * @param bool $throwException
     * @param bool $checkFileExists
     * @return bool
     * @throws CoreException
     */
    private static function requireFile(string $file, bool $throwException = false, bool $checkFileExists = true): bool
    {
        if (isset(self::$loaded[$file])) {
            return true;
        } elseif ($checkFileExists === false) {
            require $file;
            self::$loaded[$file] = true;
            return true;
        } elseif (is_file($file)) {
            require $file;
            self::$loaded[$file] = true;
            return true;
        } elseif ($throwException) {
            throw new CoreException("未找到要载入的文件:{$file}");
        } else {
            return false;
        }
    }

}
