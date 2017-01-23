<?php
 
/**
 * SqlServer操作类(mssql)
 * Class MsSQL
 */
class MsSQL
{
    private $dbhost;
    private $dbuser;
    private $dbpw;
    private $dbname;
    private $pconnect;
    private $result;
    private $querynum = 0;
    private $connid = 0;
    private $insertid = 0;
    private $cursor = 0;
    public static $instance = null;
 
    public function __construct($db)
    {
        function_exists("mssql_connect") or die("<pre>请先安装 MsSQL 扩展。");
 
        $this->dbhost = !empty($db['hostname']) ? $db['hostname'] : 'localhost';
        $this->dbuser = $db['username'];
        $this->dbpw = $db['password'];
        $this->dbname = $db['dbname'];
        $this->pconnect = !empty($db['pconnect']) ? $db['pconnect'] : 0;
        $this->connect();
    }
 
    public static function getdatabase($db){
        if(empty(self::$instance)){
            self::$instance = new MsSQL($db);
        }
        return self::$instance;
    }
 
    /**
     * 连接数据库
     * @return int
     */
    private function connect()
    {
        $func = $this->pconnect == 1 ? 'mssql_pconnect' : 'mssql_connect';
        if(!$this->connid = @$func($this->dbhost, $this->dbuser, $this->dbpw)){
            $this->halt('Can not connect to MsSQL server');
        }
         
        if(!empty($this->dbname)){
            if(!$this->select_db($this->dbname)){
                $this->halt('Cannot use database '.$this->dbname);
            }
        }
 
        return $this->connid;
    }
 
    /**
     * 选择数据库
     * @param $dbname
     * @return bool
     */
    public function select_db($dbname)
    {
        return @mssql_select_db($dbname , $this->connid);
    }
 
    /**
     * 直接执行sql
     * @param $sql
     * @return mixed
     */
    public function query_simple($sql)
    {
        if(empty($sql)){
            $this->halt('SQL IS NULL!');
        }
 
        $result = mssql_query($sql, $this->connid);
 
        if(!$result){  //调试用，sql语句出错时会自动打印出来
            $this->halt('MsSQL Query Error', $sql);
        }
 
        $this->result = $result;
 
        return $this->result;
    }
 
    /**
     * 过滤执行sql
     * @param $sql
     * @return array|mixed
     */
    public function query($sql)
    {
        $this->querynum++;
        $sql = trim($sql);
        if(preg_match("/^(select.*)limit ([0-9]+)(,([0-9]+))?$/i", $sql, $matchs)){
            $sql = $matchs[1];
            $offset = $matchs[2];
            $pagesize = $matchs[4];
            $this->result = mssql_query($sql, $this->connid) or $this->halt('MsSQL Query Error', $sql);
            return $this->limit($this->result, $offset, $pagesize);
        }elseif(preg_match("/^insert into/i", $sql)){
            $sql = "$sql; Select @@identity as insertid";
            $this->result = mssql_query($sql, $this->connid) or $this->halt('MsSQL Query Error', $sql);
            $insid = $this->fetch_row($this->result);
            $this->insertid = $insid[0];
            return $this->result;
        }else{
            $this->result = mssql_query($sql, $this->connid) or $this->halt('MsSQL Query Error', $sql);
            return $this->result;
        }
    }
 
    /**
     * 获取一条数据（一维数组）
     * @param $sql
     * @return array|bool
     */
    public function find($sql)
    {
        $this->result = $this->query($sql);
        $args = $this->fetch_array($this->result);
        return $args ;
    }
 
    /**
     * 获取多条（二维数组）
     * @param $sql
     * @param string $keyfield
     * @return array
     */
    public function findAll($sql, $keyfield = '')
    {
        $array = array();
        $this->result = $this->query($sql);
        while($r = $this->fetch_array($this->result)){
            if($keyfield){
                $key = $r[$keyfield];
                $array[$key] = $r;
            }else{
                $array[] = $r;
            }
        }
        return $array;
    }
 
    public function fetch_array($query, $type = MSSQL_ASSOC)
    {
        if(is_resource($query)) return mssql_fetch_array($query, $type);
        if($this->cursor < count($query)){
            return $query[$this->cursor++]; 
        }
        return FALSE; 
    }
 
    public function affected_rows()
    {
        return mssql_rows_affected($this->connid);
    }
 
    public function num_rows($query)
    {
        return is_array($query) ? count($query) : mssql_num_rows($query);
    }
 
    public function num_fields($query)
    {
        return mssql_num_fields($query);
    }
 
    public function get_result($query, $row)
    {
        return @mssql_result($query, $row);
    }
 
    /**
     * 释放连接资源
     * @param $query
     */
    public function free_result($query)
    {
        if(is_resource($query)) @mssql_free_result($query);
    }
 
    public function insert_id()
    {
        return $this->insertid;
    }
 
    public function fetch_row($query)
    {
        return mssql_fetch_row($query);
    }
 
    /**
     * 关闭数据库连接
     * @return bool
     */
    public function close()
    {
        return mssql_close($this->connid);
    }
 
    /**
     * 抛出错误
     * @param string $message
     * @param string $sql
     */
    public function halt($message = '', $sql = '')
    {
        $_sql = !empty($sql) ? "MsSQL Query:$sql <br>" : '';
        exit("<pre>{$_sql}Message:$message");
    }
 
    public function limit($query, $offset, $pagesize = 0)
    {
        if($pagesize > 0){
            mssql_data_seek($query, $offset);
        }else{
            $pagesize = $offset;
        }
 
        $info = array();
        for($i = 0; $i < $pagesize; $i++){
            $r = $this->fetch_array($query);
            if(!$r) break;
            $info[] = $r;
        }
 
        $this->cursor = 0;
        return $info;
    }
 
    /**
     * 初始化存储过程
     * @param $proNme
     * @return resource
     */
    public function init_pro($proNme)
    {
        return mssql_init($proNme, $this->connid);
    }
 
    /**
     * 开始一个事务.
     */
    public function begin()
    {
        $this->query('begin tran');
    }
 
    /**
     * 提交一个事务.
     */
    public function commit()
    {
        $this->query('commit tran');
    }
 
    /**
     * 回滚一个事务.
     */
    public function rollback()
    {
        $this->query('rollback tran');
    }
 
    /**
     * 析构函数,关闭数据库,垃圾回收
     */
    public function __destruct()
    {
        if(!is_resource($this->connid)){
            return;
        }
 
        $this->free_result($this->result);
        $this->close();
    }
}
