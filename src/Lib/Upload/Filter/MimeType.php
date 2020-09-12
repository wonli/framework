<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\Lib\Upload\Filter;


use Cross\Lib\Upload\IFilter;

/**
 * 文件类型过滤
 *
 * @package lib\Upload\Filter
 * @author wonli <wonli@live.com>
 */
class MimeType implements IFilter
{

    /**
     * 允许上传的文件类型
     *
     * @var array
     */
    protected $allowedFileType = [];

    /**
     * 对应关系
     *
     * @var array
     */
    protected $allowMimeContentType = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        '7z' => 'application/x-7z-compressed',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    /**
     * 上传文件过滤
     *
     * @param mixed $file 文件信息
     * @param string $error 失败信息
     * @return bool 成功返回true
     */
    function filter($file, &$error = '')
    {
        $m = mime_content_type($file['tmp_name']);
        foreach ($this->allowedFileType as $suffix) {
            $contentType = &$this->allowMimeContentType[$suffix];
            if (!$contentType) {
                $error = '不支持的MimeContentType';
                return false;
            }

            if (0 === strcasecmp($m, $contentType)) {
                return true;
            }
        }

        $error = '不支持的类型';
        return false;
    }

    /**
     * 设定允许上传的文件类型
     *
     * @param string|array $type 竖线分隔, gif|jpg|jpeg|png|doc
     * @return $this
     */
    function setAllowedType($type)
    {
        $this->allowedFileType = explode('|', strtolower($type));
        return $this;
    }

    /**
     * 增加允许上传的类型
     *
     * @param string $fileSuffix
     * @param string $mimeContentType
     * @return $this
     */
    function addMimeContentType($fileSuffix, $mimeContentType)
    {
        $this->allowMimeContentType[$fileSuffix] = $mimeContentType;
        return $this;
    }
}