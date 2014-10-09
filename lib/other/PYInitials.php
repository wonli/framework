<?php
/**
 * created by wangbinandi@gmail.com at 2008-12-29 21:12
 * 汉字拼音首字母工具类
 *  注： 英文的字串：不变返回(包括数字)    eg .abc123 => abc123
 *      中文字符串：返回拼音首字符        eg. 王小明 => WXM
 *      中英混合串: 返回拼音首字符和英文   eg. 我i我j => WIWJ
 *  eg.
 *  $py = new PYInitials();
 *  $result = $py->getInitials('王小明');
 */
namespace Cross\Lib\Other;

class PYInitials
{
    private $_pinyins = array(
        176161 => 'A',
        176197 => 'B',
        178193 => 'C',
        180238 => 'D',
        182234 => 'E',
        183162 => 'F',
        184193 => 'G',
        185254 => 'H',
        187247 => 'J',
        191166 => 'K',
        192172 => 'L',
        194232 => 'M',
        196195 => 'N',
        197182 => 'O',
        197190 => 'P',
        198218 => 'Q',
        200187 => 'R',
        200246 => 'S',
        203250 => 'T',
        205218 => 'W',
        206244 => 'X',
        209185 => 'Y',
        212209 => 'Z',
        215249 => 'Z',
    );
    private $_charset = null;

    /**
     * 构造函数, 指定需要的编码 default: utf-8
     * 支持utf-8, gb2312
     *
     * @param string $charset
     */
    public function __construct($charset = 'utf-8')
    {
        $this->_charset = $charset;
    }

    /**
     * 中文字符串 substr
     *
     * @param string $str
     * @param int $start
     * @param int $len
     * @return string
     */
    private function _msubstr($str, $start, $len)
    {
        $start = $start * 2;
        $len = $len * 2;
        $strlen = strlen($str);
        $result = '';
        for ($i = 0; $i < $strlen; $i++) {
            if ($i >= $start && $i < ($start + $len)) {
                if (ord(substr($str, $i, 1)) > 129) $result .= substr($str, $i, 2);
                else $result .= substr($str, $i, 1);
            }
            if (ord(substr($str, $i, 1)) > 129) $i++;
        }

        return $result;
    }

    /**
     * 字符串切分为数组 (汉字或者一个字符为单位)
     *
     * @param string $str
     * @return array
     */
    private function _cutWord($str)
    {
        $words = array();
        while ($str != "") {
            if ($this->_isAscii($str)) { //非中文
                $words[] = $str[0];
                $str = substr($str, strlen($str[0]));
            }
            else {
                $word = $this->_msubstr($str, 0, 1);
                $words[] = $word;
                $str = substr($str, strlen($word));
            }
        }

        return $words;
    }

    /**
     * 判断字符是否是ascii字符
     *
     * @param string $char
     * @return bool
     */
    private function _isAscii($char)
    {
        return (ord(substr($char, 0, 1)) < 160);
    }

    /**
     * 判断字符串前3个字符是否是ascii字符
     *
     * @param string $str
     * @return bool
     */
    private function _isAsciis($str)
    {
        $len = strlen($str) >= 3 ? 3 : 2;
        $chars = array();
        for ($i = 1; $i < $len - 1; $i++) {
            $chars[] = $this->_isAscii($str[$i]) ? 'yes' : 'no';
        }
        $result = array_count_values($chars);
        if (empty($result['no'])) {
            return true;
        }

        return false;
    }

    /**
     * 获取中文字串的拼音首字符
     *
     * @param string $str
     * @return string
     */
    public function getInitials($str)
    {
        if (empty($str)) return '';
        if ($this->_isAscii($str[0]) && $this->_isAsciis($str)) {
            return $str;
        }
        $result = array();
        if ($this->_charset == 'utf-8') {
            $str = iconv('utf-8', 'gb2312', $str);
        }
        $words = $this->_cutWord($str);
        foreach ($words as $word) {
            if ($this->_isAscii($word)) { //非中文
                $result[] = $word;
                continue;
            }
            $code = (ord(substr($word, 0, 1))) * 1000 + (ord(substr($word, 1, 1)));
            //获取拼音首字母A--Z
            if (($i = $this->_search($code)) != -1) {
                $result[] = $this->_pinyins[$i];
            }
        }

        return strtoupper(implode('', $result));
    }

    /**
     * get char
     *
     * @param $ascii
     * @return string
     */
    private function _getChar($ascii)
    {
        if ($ascii >= 48 && $ascii <= 57) {
            return chr($ascii); //数字
        }
        elseif ($ascii >= 65 && $ascii <= 90) {
            return chr($ascii); // A--Z
        }
        elseif ($ascii >= 97 && $ascii <= 122) {
            return chr($ascii - 32); // a--z
        }
        else {
            return '~'; //其他
        }
    }

    /**
     * 查找需要的汉字内码(gb2312) 对应的拼音字符( 二分法 )
     *
     * @param int $code
     * @return int
     */
    private function _search($code)
    {
        $data = array_keys($this->_pinyins);

        $lower = 0;
        $upper = sizeof($data) - 1;

        // 排除非一级汉字
        if ($code < $data[0] || $code > $data[23]) return -1;

        for (; ;) {
            if ($lower > $upper) {
                return $data[$lower - 1];
            }
            $middle = (int)round(($lower + $upper) / 2);
            if (!isset($data[$middle])) {
                return -1;
            }

            if ($data[$middle] < $code) {
                $lower = (int)$middle + 1;
            }
            else if ($data[$middle] == $code) {
                return $data[$middle];
            }
            else {
                $upper = (int)$middle - 1;
            }
        }
        // end for
    }
}
