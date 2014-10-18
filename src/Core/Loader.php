<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.4
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
     */
    private function __construct()
    {
        spl_autoload_register(array($this, "loadClass"));
    }

    /**
     * 单例模式运行Loader
     *
     * @return Loader
     */
    public static function init()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Loader();
        }

        return self::$instance;
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
            if (isset(self::$loaded [$file])) {
                return true;
            } elseif (file_exists($file)) {
                self::$loaded [$file] = 1; //标识已载入
                require $file;

                return true;
            } else {
                throw new CoreException("未找到要载入的文件:{$file}");
            }
        }

        return true;
    }

    /**
     * 读取指定的单一文件
     *
     * @param $file Loader::parseFileRealPath()
     * @param bool $read_file 是否读取文件内容
     * @return mixed
     * @throws CoreException
     */
    static public function read($file, $read_file = true)
    {
        if (file_exists($file)) {
            $file_path = $file;
        } else {
            $file_path = Loader::getFilePath($file);
        }

        $key = crc32($file_path);
        $read_file_flag = (int) $read_file;
        if (isset(self::$loaded [$read_file_flag][$key])) {
            return self::$loaded [$read_file_flag][$key];
        }

        if (is_readable($file_path)) {
            if (false === $read_file) {
                $file_content = file_get_contents($file_path);
                self::$loaded [$read_file_flag][$key] = $file_content;

                return $file_content;
            }

            $ext = Helper::getExt($file_path);
            switch ($ext) {
                case 'php' :
                    $data = require $file_path;
                    self::$loaded [$read_file_flag][$key] = $data;
                    break;

                case 'json' :
                    $data = json_decode(file_get_contents($file_path), true);
                    self::$loaded [$read_file_flag][$key] = $data;
                    break;

                case 'ini':
                    $data = parse_ini_file($file_path);
                    self::$loaded [$read_file_flag][$key] = $data;
                    break;

                default :
                    throw new CoreException("不支持的解析格式");
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
    static function parseFileRealPath($class, $append_file_ext = ".php")
    {
        $files = $list = array();
        $_defines = array(
            'app' => APP_PATH,
            'static' => defined('STATIC_PATH') ? STATIC_PATH : '',
            'project' => PROJECT_REAL_PATH,
        );

        if (is_array($class)) {
            $files = $class;
        } elseif (false !== strpos($class, ",")) {
            $files = explode(",", $class);
        } else {
            $files[] = $class;
        }

        foreach ($files as $f) {
            if (false !== strpos($f, '::')) {

                list($path, $file_info) = explode('::', $f);
                if (!$path) {
                    $path = "project";
                }

                $file_real_path = rtrim($_defines[strtolower($path)], DS) . DS . str_replace("/", DS, $file_info);
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
     * 自动加载函数
     *
     * @param $class_name
     * @return bool
     */
    function loadClass($class_name)
    {
        if (isset(self::$loaded[$class_name])) {
            return true;
        }

        $pos = strpos($class_name, '\\');
        $prefix = '';
        if ($pos) {
            $prefix = substr($class_name, 0, $pos);
        }

        $class_name = str_replace('\\', DS, $class_name);
        if ($prefix && 0 === strcasecmp($prefix, 'cross')) {
            $class_file = CP_PATH . substr($class_name, $pos + 1) . '.php';
        } else {
            $class_file = PROJECT_REAL_PATH . $class_name . '.php';
        }

        if (!is_file($class_file)) {
            return false;
        }

        self::$loaded[$class_name] = true;
        require $class_file;

        return true;
    }
}

