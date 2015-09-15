<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */
namespace Cross\Core;

use Cross\Exception\CoreException;

/**
 * @Auth: wonli <wonli@live.com>
 * Class Loader
 * @package Cross\Core
 */
class Loader
{
    /**
     * 当前运行的app所在路径
     *
     * @var string
     */
    private static $app_path;

    /**
     * name space
     *
     * @var array
     */
    private static $name_space;

    /**
     * Loader的实例
     *
     * @var Loader
     */
    private static $instance;

    /**
     * 已加载类列表
     *
     * @var array
     */
    private static $loaded = array();

    /**
     * 初始化Loader
     *
     * @param string $app_name
     */
    private function __construct($app_name)
    {
        self::$app_path = APP_PATH_DIR . $app_name . DIRECTORY_SEPARATOR;
        spl_autoload_register(array($this, 'loadClass'));
        spl_autoload_register(array($this, 'loadPSRClass'));
        return $this;
    }

    /**
     * 单例模式运行Loader
     *
     * @param string $app_name
     * @return mixed
     */
    public static function init($app_name)
    {
        if (!isset(self::$instance[$app_name])) {
            self::$instance[$app_name] = new Loader($app_name);
        }

        return self::$instance[$app_name];
    }

    /**
     * 载入文件(支持多文件载入)
     *
     * @see Loader::parseFileRealPath()
     * @param $files
     * @return mixed
     * @throws CoreException
     */
    static public function import($files)
    {
        $list = Loader::parseFileRealPath($files);
        foreach ($list as $file) {
            self::requireFile($file, true);
        }

        return true;
    }

    /**
     * 读取指定的单一文件
     *
     * @param string $file Loader::parseFileRealPath()
     * @param bool $require_file 是否返回文件 等于false时,返回文件文本内容
     * @return mixed
     * @throws CoreException
     */
    static public function read($file, $require_file = true)
    {
        if (is_file($file)) {
            $file_path = $file;
        } else {
            $file_path = Loader::getFilePath($file);
        }

        $read_file_flag = (int)$require_file;
        if (isset(self::$loaded [$file_path][$read_file_flag])) {
            return self::$loaded [$file_path][$read_file_flag];
        }

        if (is_readable($file_path)) {
            if (false === $require_file) {
                $file_content = file_get_contents($file_path);
                self::$loaded [$file_path][$read_file_flag] = $file_content;

                return $file_content;
            }

            $ext = Helper::getExt($file_path);
            switch ($ext) {
                case 'php' :
                    $data = require $file_path;
                    self::$loaded [$file_path][$read_file_flag] = $data;
                    break;

                case 'json' :
                    $data = json_decode(file_get_contents($file_path), true);
                    self::$loaded [$file_path][$read_file_flag] = $data;
                    break;

                case 'ini':
                    $data = parse_ini_file($file_path, true);
                    self::$loaded [$file_path][$read_file_flag] = $data;
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
     * 根据给定的参数解析文件的绝对路径
     * <pre>
     *  格式如下:
     *  1 file_name 直接指定文件路径
     *  2 ::[path/]file_name 从当前项目根目录查找
     *  3 app::[path/]file_name 当前app路径
     *  4 core::[path/]file_name 核心目录
     * </pre>
     *
     * @param $class
     * @param string $append_file_ext
     * @return array
     */
    static function parseFileRealPath($class, $append_file_ext = '.php')
    {
        $files = $list = array();
        $_defines = array(
            'app' => self::$app_path,
            'static' => Request::getInstance()->getScriptFilePath() . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR,
            'project' => PROJECT_REAL_PATH,
        );

        if (is_array($class)) {
            $files = $class;
        } elseif (false !== strpos($class, ',')) {
            $files = array_map('trim', explode(',', $class));
        } else {
            $files[] = $class;
        }

        foreach ($files as $f) {
            if (false !== strpos($f, '::')) {

                list($path, $file_info) = explode('::', $f);
                if (!$path) {
                    $path = 'project';
                }

                $file_real_path = $_defines[strtolower($path)] . str_replace('/', DIRECTORY_SEPARATOR, $file_info);
                $file_path_info = pathinfo($file_real_path);
                if (!isset($file_path_info['extension'])) {
                    $file_real_path .= $append_file_ext;
                }

                $list [] = $file_real_path;
            } else {
                $list [] = $f;
            }
        }

        return $list;
    }

    /**
     * @see Loader::parseFileRealPath
     * @param $file
     * @return mixed
     */
    static function getFilePath($file)
    {
        return current(Loader::parseFileRealPath($file, ''));
    }

    /**
     * 注册命名空间和源文件路径的对应关系
     *
     * @param $prefix
     * @param $base_dir
     * @param bool $prepend
     */
    static function registerNameSpace($prefix, $base_dir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        if (isset(self::$name_space[$prefix]) === false) {
            self::$name_space[$prefix] = array();
        }

        if ($prepend) {
            array_unshift(self::$name_space[$prefix], $base_dir);
        } else {
            array_push(self::$name_space[$prefix], $base_dir);
        }
    }

    /**
     * 获取已注册的命名空间
     *
     * @return array
     */
    static function getNameSpaceMap()
    {
        return self::$name_space;
    }

    /**
     * 自动加载
     *
     * @param $class_name
     * @return bool|string
     * @throws CoreException
     */
    function loadClass($class_name)
    {
        $pos = strpos($class_name, '\\');
        $prefix = '';
        if ($pos) {
            $prefix = substr($class_name, 0, $pos);
        }

        $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        if ($prefix && 0 === strcasecmp($prefix, 'cross')) {
            $class_file = CP_PATH . substr($class_name, $pos + 1) . '.php';
        } else {
            $class_file = PROJECT_REAL_PATH . $class_name . '.php';
        }

        if (!is_file($class_file)) {
            return false;
        }

        $this->requireFile($class_file);
        return $class_file;
    }

    /**
     * PSR-4
     *
     * @param string $class
     * @return bool|string
     */
    function loadPSRClass($class)
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
    protected function loadMappedFile($prefix, $relative_class)
    {
        if (isset(self::$name_space[$prefix]) === false) {
            return false;
        }

        foreach (self::$name_space[$prefix] as $base_dir) {
            $file = $base_dir
                . str_replace('\\', '/', $relative_class)
                . '.php';

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
     * @return bool
     * @throws CoreException
     */
    static function requireFile($file, $throw_exception = false)
    {
        if (isset(self::$loaded[$file])) {
            return true;
        } else if (is_file($file)) {
            require $file;
            self::$loaded[$file] = true;
            return true;
        } else if ($throw_exception) {
            throw new CoreException("未找到要载入的文件:{$file}");
        } else {
            return false;
        }
    }

}
