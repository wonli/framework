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
 * Class PgSQLAssembler
 * @package Cross\DB\SQLAssembler
 */
class PgSQLAssembler extends SQLAssembler
{
    /**
     * 覆盖默认配置
     *
     * @var string
     */
    protected $fieldQuoteChar = '';

    /**
     * 生成分页SQL片段
     *
     * @param int $p
     * @param int $limit
     * @return string
     */
    protected function getLimitSQLSegment(int $p, int $limit): string
    {
        $offset = $limit * ($p - 1);
        return "LIMIT {$limit} OFFSET {$offset}";
    }

    /**
     * PgSQL的limit如果有第二个参数, 那么和mysql的limit行为保持一致, 并且offset()不生效
     *
     * @param int $start
     * @param int|null $end
     * @return string
     */
    public function limit(int $start, int $end = null): string
    {
        if (null !== $end) {
            $limit = max(1, $end);
            $offset = $limit * (max(1, $start) - 1);

            $this->offsetIsValid = false;
            return "LIMIT {$limit} OFFSET {$offset} ";
        }

        $start = (int)$start;
        return "LIMIT {$start} ";
    }
}
