<?php
/**
 * Cross - a micro PHP framework
 *
 * @link        http://www.crossphp.com
 * @license     MIT License
 */

namespace Cross\DB\Drivers;

use Cross\DB\SQLAssembler\SQLAssembler;
use Cross\Exception\CoreException;
use PDO;
use Throwable;

/**
 * @author wonli <wonli@live.com>
 * Class PDOOracleDriver
 * @package Cross\DB\Drivers
 */
class PDOOracleDriver extends PDOSqlDriver
{
    /**
     * 插入数据
     *
     * @param string $table
     * @param string|array $data
     * @param bool $multi 是否批量添加
     * @param array $insert_data 批量添加的数据
     * @param bool $openTA 批量添加时是否开启事务
     * @return bool|mixed
     * @throws CoreException
     * @see SQLAssembler::add()
     */
    public function add(string $table, $data, bool $multi = false, &$insert_data = [], bool $openTA = false)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $apk = null;
        $meta = $this->getConnecter()->getMetaData($table);
        if (!empty($meta)) {
            foreach ($meta as $m => $conf) {
                if ($conf['primary'] && $conf['auto_increment']) {
                    $apk = $m;
                    break;
                }
            }
        }

        //没有自增主键处理流程不变
        if (null === $apk) {
            return parent::add($table, $data, $multi, $insert_data, $openTA);
        }

        $fields = [];
        $sqlValues = [];
        $tableData = $multi ? $data[0] : $data;
        foreach ($tableData as $key => $name) {
            $fields[] = $key;
            $sqlValues[] = ":{$key}";
        }

        $insert_sql_segment = sprintf('(%s) VALUES (%s)', implode(',', $fields), implode(',', $sqlValues));
        $rawSql = "INSERT INTO {$table} {$insert_sql_segment} RETURNING {$apk} INTO :lastInsertId";
        if ($multi) {
            $count = 0;
            if ($openTA) {
                $this->beginTA();
            }

            try {
                foreach ($data as $d) {
                    $count++;
                    $d[$apk] = $this->saveRowData($rawSql, $d);;
                    $insert_data[] = $d;
                }
            } catch (Throwable $e) {
                $insert_data = [];
                if ($openTA) {
                    $this->rollBack();
                }
                throw new CoreException($e->getMessage());
            }

            if ($openTA) {
                $this->commit();
            }

            return $count;
        } else {
            return $this->saveRowData($rawSql, $data);
        }
    }

    /**
     * 保存数据
     *
     * @param string $rawSql
     * @param array $dataRow
     * @return int
     * @throws CoreException
     */
    protected function saveRowData(string $rawSql, array $dataRow): int
    {
        $lastInsertId = 0;
        $stmt = $this->pdo->prepare($rawSql);

        foreach ($dataRow as $k => &$v) {
            if (is_numeric($v)) {
                $stmt->bindParam($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindParam($k, $v, PDO::PARAM_STR);
            }
        }

        $stmt->bindParam('lastInsertId', $lastInsertId, PDO::PARAM_INT, 20);
        $save = $stmt->execute();
        if (!$save) {
            throw new CoreException('Save data fail!');
        }

        return $lastInsertId;
    }

}
