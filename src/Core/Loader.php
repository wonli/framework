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
     * @var self
     */
    private static $instance;

    /**
     * 已注册的命名空间
     *
     * @var array
     */
    private static $namespace;

    /**
     * 已加载类的文件列表
     *
     * @var array
     */
    private static $loaded = array();

    /**
     * 初始化Loader
     */
    private function __construct()
    {
        spl_autoload_register(array($this, 'loadClass'));
        spl_autoload_register(array($this, 'loadPSRClass'));
    }

    /**
     * 单例模式
     *
     * @return Loader
     */
    static function init()
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
     * @return mixed
     * @throws CoreException
     */
    static function import($file)
    {
        return self::requireFile(PROJECT_REAL_PATH . $file, true);
    }

    /**
     * 读取指定的单一文件
     *
     * @param string $file
     * @param bool $get_file_content 是否读取文件文本内容
     * @return mixed
     * @throws CoreException
     */
    static function read($file, $get_file_content = false)
    {
        if (!file_exists($file)) {
            throw new CoreException("{$file} 文件不存在");
        }

        static $cache = null;
        $flag = (int)$get_file_content;
        if (isset($cache[$file][$flag])) {
            return $cache[$file][$flag];
        }

        if (is_readable($file)) {
            if ($get_file_content) {
                $file_content = file_get_contents($file);
                $cache[$file][$flag] = $file_content;
                return $file_content;
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
    static function getNamespaceMap()
    {
        return self::$namespace;
    }

    /**
     * 注册命名空间
     *
     * @param string $prefix 名称
     * @param string $base_dir 源文件绝对路径
     * @param bool $prepend
     */
    static function registerNamespace($prefix, $base_dir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        if (isset(self::$namespace[$prefix]) === false) {
            self::$namespace[$prefix] = array();
        }

        if ($prepend) {
            array_unshift(self::$namespace[$prefix], $base_dir);
        } else {
            array_push(self::$namespace[$prefix], $base_dir);
        }
    }

    /**
     * 自动加载
     *
     * @param string $class_name
     * @return bool|string
     * @throws CoreException
     */
    private function loadClass($class_name)
    {
        $prefix = '';
        $pos = strpos($class_name, '\\');
        if (false !== $pos) {
            $prefix = substr($class_name, 0, $pos);
            $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        }

        $check_file_exists = true;
        if ('' !== $prefix && 0 === strcasecmp($prefix, 'cross')) {
            $check_file_exists = false;
            $class_file = CP_PATH . substr($class_name, $pos + 1) . '.php';
        } else {
            $class_file = PROJECT_REAL_PATH . $class_name . '.php';
        }

        $this->requireFile($class_file, false, $check_file_exists);
        return $class_file;
    }

    /**
     * PSR-4
     *
     * @param string $class
     * @return bool|string
     * @throws CoreException
     */
    private function loadPSRClass($class)
    {
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relative_class = substr($class, $pos + 1);

            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }
            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * 匹配已注册的命名空间,require文件
     *
     * @param $prefix
     * @param $relative_class
     * @return bool|string
     * @throws CoreException
     */
    private function loadMappedFile($prefix, $relative_class)
    {
        if (isset(self::$namespace[$prefix]) === false) {
            return false;
        }

        foreach (self::$namespace[$prefix] as $base_dir) {
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * require文件
     *
     * @param $file
     * @param bool $throw_exception
     * @param bool $check_file_exists
     * @return bool
     * @throws CoreException
     */
    private static function requireFile($file, $throw_exception = false, $check_file_exists = true)
    {
        if (isset(self::$loaded[$file])) {
            return true;
        } elseif ($check_file_exists === false) {
            require $file;
            self::$loaded[$file] = true;
            return true;
        } elseif (is_file($file)) {
            require $file;
            self::$loaded[$file] = true;
            return true;
        } elseif ($throw_exception) {
            throw new CoreException("未找到要载入的文件:{$file}");
        } else {
            return false;
        }
    }

}
