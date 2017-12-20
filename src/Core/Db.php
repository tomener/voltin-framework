<?php
/**
 * voltin a Fast Simple Smart PHP FrameWork
 * Author: Tommy 863758705@qq.com
 * Link: http://www.TimoPHP.com/
 * Since: 2016
 */

namespace Voltin\Core;


use Voltin\Config\Config;
use Voltin\Exception\CoreException;

/**
 * Class Db
 * @package Voltin\Core
 *
 * 1、连接一个数据库，生成$this->link连接
 * 2、生产预处理语句：$stmt = $this->link->prepare($sql);
 * 3、绑定参数[如果有参数]$stmt->execute($params);
 * 4、执行预处理语句，并返回$stmt
 * 5、操作结果集
 */
class Db
{
    /**
     * 数据库实例池，一个数据库对应一个实例
     *
     * @var array
     */
    protected static $instances = [];

    /**
     * 数据库连接池，包含多个数据库的连接
     *
     * @var array
     */
    protected static $links = [];

    /**
     * 当前数据库连接对象
     *
     * @var \PDO
     */
    protected $link = null;

    /**
     * 执行SQL语句后的返回对象
     *
     * @var \PDOStatement
     */
    protected $query = null;

    /**
     * @var bool 查询是否返回stmt
     */
    public $return_stmt = false;

    /**
     * 数据库连接参数默认值
     *
     * @var array
     */
    protected static $config = [
        'persistence' => false,
        'user' => null,
        'password' => null
    ];

    /**
     * 是否启用读写分离
     *
     * @var bool
     */
    protected $rw_separate = false;

    /**
     * @var string 当前执行的SQL语句
     */
    protected $sql = '';

    /**
     * 链接数据库
     *
     * Db constructor.
     * @param array $params
     * @param array $options
     * @param bool $rw_separate
     *
     * @throws CoreException
     */
    public function __construct(array $params, $options = [], $rw_separate = false)
    {
        $this->link = static::getDbLinkInstance($params, $options);
        $this->rw_separate = $rw_separate;
    }

    /**
     * 获取数据库实例
     *
     * @param array $params
     * @param array $options
     * @param bool $rw_separate
     * @return mixed
     */
    public static function getInstance($params = [], $options = [], $rw_separate = false)
    {
        $dsn = $params['dsn'] . implode('', $options);
        if (!isset(self::$instances[$dsn])) {
            self::$instances[$dsn] = new self($params, $options, $rw_separate);
        }

        return self::$instances[$dsn];
    }

    /**
     * 获取数据库连接
     *
     * @param array $params
     * @param array $options
     * @return \PDO
     * @throws CoreException
     */
    public static function getDbLinkInstance(array $params, $options = [])
    {
        if (!isset($params['dsn'])) {
            throw new CoreException('Database config params [dsn] not set', 60001);
        }

        $dsn = $params['dsn'] . implode('', $options);

        if (isset(self::$links[$dsn]) && is_a(self::$links[$dsn], 'PDO')) {
            $link = self::$links[$dsn];
            return $link;
        }

        $params += self::$config;

        //数据库连接
        try {
            $options += [
                \PDO::ATTR_PERSISTENT => $params['persistence'],
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];

            //实例化数据库连接
            $link = new \PDO($params['dsn'], $params['user'], $params['password'], $options);
            self::$links[$dsn] = $link;

        } catch (\PDOException $exception) {

            //当调试模式关闭时
            if (APP_DEBUG === false) {
                //记录错误日志
                Log::write("Database server connect error! Error Code:{$exception->getCode()} Error Message:{$exception->getMessage()}", 'Warning');
            }

            //抛出异常信息
            throw new CoreException('Database connect error: ' . $exception->getMessage() . ' code: ' . $exception->getCode(), 60002);
        }
        return $link;
    }

    /**
     * 获取数据库连接的实例化对象
     *
     * @return \PDO
     */
    public function getDbConnection()
    {
        return $this->link;
    }

    /**
     * 执行查询SQL语句
     * 用于执行查询性的SQL语句（需要数据返回的情况）
     *
     * @param string $sql SQL语句
     * @param array|null $params 待转义的参数值
     * @return $this|\PDOStatement
     */
    public function query($sql, array $params = null)
    {
        $this->_execute($sql, $params, true);
        return $this;
    }

    /**
     * 执行非查询SQL语句
     * 用于无需返回信息的操作，如：增加、更新、删除数据
     *
     * @param $sql
     * @param null $params
     * @param bool $is_update
     * @return bool|int
     */
    public function execute($sql, $params = null, $is_update = false)
    {
        $this->_execute($sql, $params);
        if (!$this->query) {
            return false;
        }

        //对执行成功的SQL语句进行日志记录
        $this->logSQL($sql, $params);

        if (!$is_update) {
            return true;
        } else {
            return $this->query->rowCount();
        }
    }

    /**
     * 获取一行查询信息
     *
     * @access public
     * @param int $model 返回数据的索引类型：字段型/数据型 等。默认：字段型
     * @return array|bool
     */
    public function fetchRow($model = \PDO::FETCH_ASSOC)
    {
        if (!$this->query) {
            return false;
        }

        $row = $this->query->fetch($model);
        $this->query->closeCursor();

        return $row;
    }

    /**
     * 获取全部查询信息
     *
     * @param int $model
     * @return array|bool
     */
    public function fetchAll($model = \PDO::FETCH_ASSOC)
    {
        if (!$this->query) {
            return false;
        }

        $row = $this->query->fetchAll($model);
        $this->query->closeCursor();

        return $row;
    }

    /**
     * 通过一个SQL语句获取一行数据
     *
     * @param string $sql SQL语句
     * @param array|null $params 待转义的参数值
     * @param int $fetch_mode
     * @return array|\PDOStatement
     */
    public function getOne($sql, array $params = null, $fetch_mode = \PDO::FETCH_ASSOC)
    {
        return $this->find($sql, $params, 'one', $fetch_mode);
    }

    /**
     * 通过一个SQL语句获取全部数据
     *
     * @param string $sql SQL语句
     * @param array $params 待转义的参数值
     * @param int $fetch_mode
     *
     * @return array|\PDOStatement
     */
    public function all($sql, array $params = null, $fetch_mode = \PDO::FETCH_ASSOC)
    {
        return $this->find($sql, $params, 'all', $fetch_mode);
    }

    /**
     * 获取数据
     *
     * @param $sql
     * @param null $params
     * @param null $row
     * @param int $fetch_mode
     * @return array|\PDOStatement
     */
    public function find($sql, $params = null, $row = null, $fetch_mode = \PDO::FETCH_ASSOC)
    {
        $this->query($sql, $params);
        if (!$this->query) {
            return [];
        }

        if ($this->return_stmt) {
            $this->return_stmt = false;
            return $this->query;
        }

        if ($row == 'one') {
            $rows = $this->query->fetch($fetch_mode);
        } else {
            $rows = $this->query->fetchAll($fetch_mode);
        }

        $this->query->closeCursor();
        return $rows ? $rows : [];
    }

    /**
     * 获取最新的insert_id
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->link->lastInsertId();
    }

    /**
     * 开启事务处理
     *
     * @return boolean
     */
    public function startTrans()
    {
        if (!$this->link->inTransaction()) {
            $this->link->beginTransaction();
            //SQL日志记录
            $this->logSQL('BEGIN');
        }
        return true;
    }

    /**
     * 提交事务处理
     *
     * @return boolean
     */
    public function commit()
    {
        if ($this->link->inTransaction()) {
            $this->link->commit();
            //SQL日志记录
            $this->logSQL('COMMIT');
        }
        return true;
    }

    /**
     * 事务回滚
     *
     * @return boolean
     */
    public function rollback()
    {
        if ($this->link->inTransaction()) {
            $this->link->rollBack();
            //SQL日志记录
            $this->logSQL('ROLLBACK');
        }
        return true;
    }

    /**
     * 对字符串进行转义,提高数据库操作安全
     *
     * @param string $value 待转义的字符串内容
     * @return string
     */
    public function escape($value = null)
    {
        //参数分析
        if (is_null($value)) {
            return null;
        }

        if (!is_array($value)) {
            return trim($this->link->quote($value));
        }

        //当参数为数组时
        return array_map([$this, 'escape'], $value);
    }

    /**
     * 插入单条数据
     *
     * @param string $tableName 所要操作的数据表名称
     * @param array $data 插入的数据
     * @param boolean $pkAutoIncrement 主键是否自增
     *
     * @return bool|int
     */
    public function insert($tableName, $data, $pkAutoIncrement = true)
    {
        //参数分析
        if (!$tableName || !$data || !is_array($data)) {
            return false;
        }

        //处理数据表字段与数据的对应关系
        $params = array_values($data);

        $fieldString = '`' . implode('`,`', array_keys($data)) . '`';
        $contentString = rtrim(str_repeat('?,', count($params)), ',');

        //组装SQL语句
        $sql = 'INSERT INTO ' . $tableName . ' (' . $fieldString . ') VALUES (' . $contentString . ')';

        $result = $this->execute($sql, $params);

        if ($result) {
            if ($pkAutoIncrement) {
                return $this->lastInsertId();
            }
            return true;
        }
        return false;
    }

    /**
     * 批量插入数据
     *
     * @param $tableName
     * @param $data
     * @param bool $returnId
     * @return bool|int
     */
    public function insertMulti($tableName, $data, $returnId = false)
    {
        $fieldString = '`' . implode('`,`', array_keys($data[0])) . '`';

        $contentString = '(' . rtrim(str_repeat('?,', count($data[0])), ',') . ')';
        $contentString = rtrim(str_repeat($contentString . ',', count($data)), ',');

        $params = [];
        foreach ($data as $item) {
            $params = array_merge($params, array_values($item));
        }

        //组装SQL语句
        $sql = "INSERT INTO {$tableName} ({$fieldString}) VALUES {$contentString}";

        $result = $this->execute($sql, $params);

        //当返回数据需要返回insert id时
        if ($result && $returnId === true) {
            return $this->lastInsertId();
        }

        return $result;
    }

    /**
     * 数据表数据替换操作
     *
     * @access public
     *
     * @param string $tableName 所要操作的数据表名称
     * @param array $data 所要替换的数据内容。注：数据必须为数组
     *
     * @return mixed
     */
    public function replace($tableName, $data)
    {

        //参数分析
        if (!$tableName || !$data || !is_array($data)) {
            return false;
        }

        //处理数据表字段与数据的对应关系
        $contentArray = array_values($data);

        $fieldString = implode(',', array_keys($data));
        $contentString = rtrim(str_repeat('?,', count($contentArray)), ',');

        //组装SQL语句
        $sql = "REPLACE INTO {$tableName} ({$fieldString}) VALUES ({$contentString})";

        $result = $this->execute($sql, $contentArray);

        //清空不必要的内容占用
        unset($fieldString, $contentString, $contentString);

        return $result;
    }

    /**
     * 数据表更新操作
     *
     * @param string $tableName
     * @param array $data
     * @param string $where
     * @param array $params
     * @return int|bool
     */
    public function update($tableName, array $data, $where, array $params)
    {
        $updateString = '';
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $updateString .= '`' . $key . '` = ?,';
            } else {
                $updateString .= '`' . $key . '` = ' . $key . $val[0] . '?,';
                $data[$key] = $val[1];
            }
        }
        $updateString = rtrim($updateString, ',');
        $params = array_merge(array_values($data), $params);

        $sql = 'UPDATE ' . $tableName . ' SET ' . $updateString;
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = $this->execute($sql, $params, true);
        return $result;
    }

    /**
     * 数据表删除操作
     *
     * @param $tableName
     * @param $where
     * @param array $params
     * @return bool
     */
    public function delete($tableName, $where, array $params)
    {
        if (empty($where)) {
            return false;
        }
        $sql = "DELETE FROM {$tableName}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }

        return $this->execute($sql, $params);
    }

    /**
     * 根据数据表名获取该数据表的字段信息
     *
     * @param string $tableName 数据表名
     * @param boolean $extItem 数据返回类型选项，即是否返回完成的信息(包含扩展信息)。true:含扩展信息/false:不含扩展信息
     * @return array|bool
     */
    public function getTableInfo($tableName, $extItem = false)
    {
        //参数分析
        if (!$tableName) {
            return false;
        }

        $fieldList = $this->all("SHOW FIELDS FROM {$tableName}");
        if ($extItem === true) {
            return $fieldList;
        }

        //过滤掉杂数据
        $primaryArray = [];
        $pkAutoIncrement = 0;
        $fieldArray = [];

        foreach ($fieldList as $line) {
            //分析主键
            if ($line['Key'] == 'PRI') {
                $primaryArray[] = $line['Field'];
                if ($line['Extra'] == 'auto_increment') {
                    $pkAutoIncrement = 1;
                }
            }
            //分析字段
            $fieldArray[] = $line['Field'];
        }

        return ['primaryKey' => $primaryArray, 'pkAutoIncrement' => $pkAutoIncrement, 'fields' => $fieldArray];
    }

    /**
     * 获取当前数据库中的所有的数据表名的列表
     *
     * @return array
     */
    public function getTableList()
    {
        //执行SQL语句，获取数据信息
        $tableList = $this->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        if (!$tableList) {
            return [];
        }

        return array_values($tableList);
    }

    /**
     * 获取最后执行的SQL语句
     *
     * @return string
     */
    public function getLastSql()
    {
        return $this->sql;
    }

    /**
     * 获取数据库错误描述信息
     *
     * @param \PDOStatement $query
     *
     * @return string
     *
     * @example
     * 例一：
     * $errorInfo = $this->lastError();
     *
     * 例二：
     * $stmt = $this->link->prepare('select * from table_name');
     * $stmt->execute();
     *
     * $errorInfo = $this->lastError($stmt);
     *
     */
    public function lastError(\PDOStatement $query = null)
    {
        $error = (!$query) ? $this->link->errorInfo() : $query->errorInfo();
        if (!$error[2]) {
            return null;
        }

        return $error[1] . ': ' . $error[2];
    }

    /**
     * 将执行的SQL语句进行日志记录
     *
     * @param string $sql SQL语句内容
     * @param array $params 待转义的参数值
     *
     * @return boolean
     */
    protected function logSQL($sql, $params = [])
    {
        //只有当调试模式开启时，才会将执行成功的SQL语句进行日志记录
        if (APP_DEBUG === true) {
            $sql = $this->_parseQuerySql($sql, $params);
            //记录SQL语句跟踪日志
            Log::write($sql, 'Normal', 'trace.sql' . date('Ymd', $_SERVER['REQUEST_TIME']));
        }

        return true;
    }

    /**
     * 获取执行SQL语句的返回结果$stmt
     *
     * @param string $sql SQL语句内容
     * @param array $params 待转义的参数值
     * @param bool $readonly 是读还是写
     *
     * @return \PDOStatement|bool
     */
    protected function _execute($sql, array $params = null, $readonly = false)
    {
        $sql = trim($sql);

        if (APP_DEBUG) {
            $this->sql = $this->_parseQuerySql($sql, $params);
        }

        $link = $this->link;

        if ($readonly && $this->rw_separate === true) {
            $slave_config = Config::runtime('mysql.slave');
            $slave_key = array_rand($slave_config);
            $link = self::getDbLinkInstance($slave_config[$slave_key]);
        }

        try {
            //执行SQL语句
            $this->query = $link->prepare($sql);
            $result = $this->query->execute($params);

            //分析执行结果
            if (!$result) {
                $this->query->closeCursor();
                return false;
            }

            return $this->query;
        } catch (\PDOException $e) {

            //抛出异常信息
            $this->throwException($e, $sql, $params);
        }
        return false;
    }

    /**
     * 分析组装所执行的SQL语句
     * 用于prepare()与execute()组合使用时，组装所执行的SQL语句
     *
     * @param string $sql SQL语句
     * @param array $params 参数值
     *
     * @return string
     */
    protected function _parseQuerySql($sql, array $params = null)
    {
        if (!$sql) {
            return false;
        }
        $sql = trim($sql);

        //当所要转义的参数值为空时
        if (!$params) {
            return $sql;
        }

        foreach ($params as &$param) {
            if (is_string($param)) {
                $param = sprintf("'%s'", $param);
            }
        }

        $sql = str_replace('?', '%s', $sql);
        return vsprintf($sql, $params);
    }

    /**
     * 抛出异常提示信息处理
     * 用于执行SQL语句时，程序出现异常时的异常信息抛出
     *
     * @param $exception \PDOException
     * @param $sql
     * @param array $params
     * @return bool
     * @throws CoreException
     */
    protected function throwException(\PDOException $exception, $sql, $params = [])
    {
        //参数分析
        if (!is_object($exception) || !$sql) {
            return false;
        }

        $code = 60003;
        if (strpos($exception->getMessage(), 'MySQL server has gone away')) {
            $code = 60004;
        }
        $sql = $this->_parseQuerySql($sql, $params);
        $message = 'SQL execute error: ' . $sql . ' |'. $exception->getMessage() . ' Code: ' . $exception->getCode();

        //当调试模式关闭时
        if (APP_DEBUG === false) {
            //记录错误日志
            Log::write($message);
        }

        //抛出异常信息
        throw new CoreException($message, $code);
    }

    /**
     * 销毁数据库资源
     *
     * @return bool
     */
    public static function destroy()
    {
        self::$instances = [];
        self::$links = [];
        return true;
    }

    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        if (isset($this->link)) {
            $this->link = null;
        }
    }
}
