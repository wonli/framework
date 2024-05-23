<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\SQLAssembler;

/**
 * @author wonli <wonli@live.com>
 * Class OracleAssembler
 * @package Cross\DB\SQLAssembler
 */
class OracleAssembler extends SQLAssembler
{
    /**
     * 覆盖默认配置
     *
     * @var string
     */
    protected string $fieldQuoteChar = '';

    /**
     * 生成分页片段
     *
     * @param int $p
     * @param int $limit
     * @return string
     */
    protected function getLimitSQLSegment(int $p, int $limit): string
    {
        //offset 起始位置, 12c以上版本支持
        $offset = $limit * ($p - 1);
        return "OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
    }

    /**
     * @param int $start 从第几页开始取
     * @param int|null $end 每次取多少条
     * @return string
     */
    public function limit(int $start, int $end = null): string
    {
        if (null !== $end) {
            $limit = max(1, (int)$end);
            $offset = $limit * (max(1, (int)$start) - 1);

            $this->offsetIsValid = false;
            return "OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        }

        $start = (int)$start;
        return "FETCH NEXT {$start} ROWS ONLY ";
    }
}
