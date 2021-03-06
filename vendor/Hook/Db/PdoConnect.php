<?php
namespace Hook\Db;

use Hook\Cache\Cache;

class PdoConnect extends Cache
{
    public $pdo;

    public function __construct(string $name = 'default')
    {
        $dsn = 'mysql:host='.APP_CONFIG['mysql'][$name]['host'].';port='.APP_CONFIG['mysql'][$name]['port'];
        $dsn .= ';dbname='.APP_CONFIG['mysql'][$name]['dbname'].';charset='.APP_CONFIG['mysql'][$name]['charset'];
        $this->pdo = new \PDO(
            $dsn,
            APP_CONFIG['mysql'][$name]['username'],
            APP_CONFIG['mysql'][$name]['passwd'],
            APP_CONFIG['mysql'][$name]['options']
        );
    }

    /**
     * PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE 返回 以第一列为KEY，第二列为VALUE的 数据结构【如果KEY重复，则自动去重且保留最后一个KEY的VALUE】
     * PDO::FETCH_COLUMN | PDO::FETCH_GROUP 返回 以第一列为KEY，第二列为数组VALUE的元素 数据结构【如果KEY重复，则其值自动加入二维数组中】
     * PDO::FETCH_UNIQUE 返回 以第一列为KEY，其余列为数组VALUE的 数据结构【如果KEY重复，则自动去重且保留最后一个KEY的数组VALUE】
     * PDO::FETCH_GROUP 返回 以第一列为KEY，其余列为二数组VALUE的 数据结构【如果KEY重复，则其值自动加入二维数组中】
     * PDO::FETCH_OBJ 返回对象结构
     */
    public function fetchAll(string $statement, array $parameter = [], int $type = \PDO::FETCH_ASSOC): array
    {
        $rs = $this->query($statement, $parameter);
        $results = $rs->fetchAll($type);
        
        return $results;
    }

    //@return mixed[array|object|false]
    public function fetch(string $statement, array $parameter = [], int $type = \PDO::FETCH_ASSOC)
    {
        $rs = $this->query($statement, $parameter);
        $results = $rs->fetch($type);
        
        return $results;
    }

    //@return mixed[string|false]
    public function fetchColumn(string $statement, array $parameter = [], int $column = 0)
    {
        $rs = $this->query($statement, $parameter);
        $results = $rs->fetchColumn($column);
        
        return $results;
    }

    public function insert(string $statement, array $parameter = []): array
    {
        return [
            'rowCount' => $this->query($statement, $parameter)->rowCount(),
            'lastInsertId' => (int) $this->pdo->lastInsertId()
        ];
    }

    public function update(string $statement, array $parameter = []): int
    {
        return $this->query($statement, $parameter)->rowCount();
    }

    public function delete(string $statement, array $parameter = []): int
    {
        return $this->query($statement, $parameter)->rowCount();
    }

    public function query(string $statement, array $parameter = []): \PDOStatement
    {
        $rs = $this->pdo->prepare($statement);
        $flag = isset($parameter[0]);
        foreach ($parameter as $key => $value) {
            switch (1) {
                case is_int($value):
                    $type = \PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = \PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
                    break;
            }
            $rs->bindValue($flag ? ($key + 1) : $key, $value, $type);
        }

        $rs->execute();
        return $rs;
    }
}