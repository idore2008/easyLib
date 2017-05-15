<?php

/**
 * @desc php操作sqlserver2012 工具类
 * @version 1.0
 * @author jiangtao
 * @date 2017-04-14
 */

class SQLServer2012
{
	public $conn; // 连接句柄
	
	private $serverName;//服务名
	private $port;		//端口
	private $dbName;	//数据库名
	private $userName; 	//用户名
	private $password; 	//密码
	private $charset;  	//字符集
	private $debug; // 是否输出调试信息
	
	// 初始化数据库信息
	function __construct($serverName, $dbName, $userName, $password, $port = 1433, $charset = 'utf-8', $debug = 1)
	{
		$this ->serverName = $serverName;
		$this ->dbName = $dbName;
		$this ->userName = $userName;
		$this ->password = $password;
		$this ->port = $port;
		$this ->charset = $charset;
		$this ->debug = $debug;
	}
	
	/**
	 * @desc 无参构造函数
	 */
	function SQLServer2012()
	{
		$this -> __construct();
	}
	
	/**
	 * @desc 第一步数据库连接 
	 * @return $conn 连接句柄
	 */
	function connect()
	{
		$serverInfo = $this ->serverName.",".$this ->port;
		$connectionInfo = array( "Database"=>$this ->dbName, "UID"=>$this ->userName, "PWD"=>$this ->password, 'CharacterSet' => $this ->charset);
		
		//获取链接句柄
		$this->conn = sqlsrv_connect ( $serverInfo, $connectionInfo );
		if ($conn == false) {
			$this -> errors();
		} else {
			return $this->conn;
		}
		
	}
	
	/**
	 * @desc 执行sql语句
	 * @ //sqlsrv_query 准备 Transact-SQL 查询，并将其执行。//返回resource
	 * @param $sql
	 * @return resource $stmt
	 */ 
    public function query($sql, $params = array() )
	{
		$stmt = sqlsrv_query( $this->conn, $sql, $params);
	    if( $stmt === false ) {
	    	$this -> errors();
	    	return;
		}
		return $stmt;
	}
	
	/**
	 * 准备 Transact-SQL 查询，但不执行该查询。隐式绑定参数。
	 * 绑定参数
	 * 存储过程
	 * $procedure_params = array(
		array(&$myparams['Item_ID'], SQLSRV_PARAM_OUT),
		array(&$myparams['Item_Name'], SQLSRV_PARAM_OUT)
		);
	 * @desc 执行sql语句
	 * @param $sql
	 * @return resource $stmt
	 */
	public function prepare(string $sql, $params = array() )
	{
		//sqlsrv_query 准备 Transact-SQL 查询，并将其执行。//返回resource
		$stmt = sqlsrv_prepare( $this->conn, $sql, $params );
		if( $stmt === false ) {
			$this -> errors();
			return;
		}
		return $stmt;
	}
	
	/**
	 * @desc 执行SELECT语句
	 * @param $sql
	 * @param $keyField  是否提取某键作为key
	 * @return array 关联数组
	 */
	function select($sql, $keyField = '')
	{
		$array = array ();
		$stmt = $this->query ( $sql );
		//define ('SQLSRV_FETCH_NUMERIC', 1); 数字
		//define ('SQLSRV_FETCH_ASSOC', 2);  字段
		//define ('SQLSRV_FETCH_BOTH', 3);   all
		while ( $row = sqlsrv_fetch_array ( $stmt, SQLSRV_FETCH_ASSOC))
		{
			if($keyField)
			{
				$key = $row[$keyField];
				$array[$key] = $row;
			}
			else
			{
				$array[] = $row;
			}
		}
		$this->free_result ($stmt);
		return $array;
	}
	
	/**
	 * @desc  执行INSERT语句
	 * @param $tablename 表名
	 * @param $array 字段键值对数组
	 * @return resource $stmt
	 */
	function insert($tablename, $array)
	{
		if($this->table_exists($tablename))
		{
			//return $this->query ( "INSERT INTO $tablename(" . implode ( ',', array_keys ( $array ) ) . ") VALUES('" . implode ( "','", $array ) . "')" );	
			$sql = "INSERT INTO $tablename(" . implode ( ',', array_keys ( $array ) ) . ") VALUES(";
			if(count($array)>0 && is_array($array))
			{
				foreach($array as $k=>$v)
				{
					$sql .=$this->check_type($v).",";
				}
				$sql = substr($sql, 0,(strlen($sql)-1)).")";
				return $this->query($sql);
			}
			else 
				return false;
		}
		else 
			return false;
	}
	
	/**
	 * @desc  执行UPDATE语句
	 * @param $tablename 表名
	 * @param $array 字段键值对数组
	 * @param $where 条件
	 * @return resource $stmt
	 */
	function update($tablename, $array, $where = '1=1')
	{
		if($this->table_exists($tablename))
		{
			$sql = "";
			if(count($array)>0 && is_array($array))
			{
				foreach($array as $k=>$v)
					$sql .= ",$k=".$this->check_type($v);
				$sql = substr($sql, 1);
			}
			$sql = "update $tablename set $sql where $where";
// 			return $sql;
			return $this->query ( $sql );			
// 			$sql = '';
// 			foreach ( $array as $k => $v )
// 				$sql .= ", $k='$v'";
// 			$sql = substr ( $sql, 1 );
// 			$sql = "UPDATE $tablename SET $sql WHERE $where";
// 			return $this->query ( $sql );
		}
		else 
		{
			return false;
		}
	}
	
	/**
	 * @desc 执行delete 语句
	 * @param $tablename 表名
	 * @param $where 条件
	 * @return resource $stmt
	 */
	public function delete($tablename, $where = '1=1')
	{
		if($this->table_exists($tablename))
		{
			$sql = "delete from $tablename where $where";
			return $this->query ($sql);
		}
		else
			return false;
	}
	
	/**
	 * @desc 执行单条insert update delete 语句
	 * @param $sql 语句
	 * @return resource $stmt
	 */
	public function execute($sql, $params = array() )
	{
		$stmt = $this->query ($sql, $params);
		if( sqlsrv_execute( $stmt ) === false ) {
			$this->errors();
		} else 
		return $this->num_affected($stmt);
	}
	
	/**
	 * 获取一组关联数组
	 * @param resource $stmt
	 * @param int 	1 SQLSRV_FETCH_ASSOC, 2 SQLSRV_FETCH_NUMERIC, and 3 SQLSRV_FETCH_BOTH (default)
	 * @param int 	SQLSRV_SCROLL_NEXT
					SQLSRV_SCROLL_PRIOR
					SQLSRV_SCROLL_FIRST
					SQLSRV_SCROLL_LAST
					SQLSRV_SCROLL_ABSOLUTE
					SQLSRV_SCROLL_RELATIVE
	 * @return $array
	 */
	public function fetch_array($stmt,$type = 1)
	{
		if($type = 1)
			$mode = SQLSRV_FETCH_ASSOC;
		elseif ($type = 2)
			$mode = SQLSRV_FETCH_NUMERIC;
		else 
			$mode = SQLSRV_FETCH_BOTH;
		$array = array();
		while ($row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC)!= false)
		{
			$array[] = $row;
		}
		return $array;
	}
	
	/**
	 * 获取一组关联对象
	 * @param resource $stmt
	 * @param int 	SQLSRV_SCROLL_NEXT
						SQLSRV_SCROLL_PRIOR
						SQLSRV_SCROLL_FIRST
						SQLSRV_SCROLL_LAST
						SQLSRV_SCROLL_ABSOLUTE
						SQLSRV_SCROLL_RELATIVE
	 * @return $array
	 */
	public function fetch_object($stmt)
	{
		$array = array();
		while ($obj = sqlsrv_fetch_object( $stmt) != false )
		{
			$array[] = $obj;
		}
		return $array;
	}
	
	/**
	 * 使下一行的数据可供读取。
	 * @param resource $stmt
	 * @param int 	 SQLSRV_SCROLL_NEXT
					 SQLSRV_SCROLL_PRIOR
					 SQLSRV_SCROLL_FIRST
					 SQLSRV_SCROLL_LAST
					 SQLSRV_SCROLL_ABSOLUTE
					 SQLSRV_SCROLL_RELATIVE
	 * @return $array
	 */
	public function fetch($stmt)
	{
		if( sqlsrv_fetch( $stmt ) === false) {
			die( print_r( sqlsrv_errors(), true));
		}
		
		// Get the row fields. Field indeces start at 0 and must be retrieved in order.
		// Retrieving row fields by name is not supported by sqlsrv_get_field.
		$name = sqlsrv_get_field( $stmt, 0);
		echo "$name: ";
		
		$comment = sqlsrv_get_field( $stmt, 1);
		echo $comment;
	}
	
	/**
	 * 返回字段元数据。
	 * @param unknown $stmt
	 */
	public function field_metadata($stmt)
	{
		$sql = "SELECT * FROM Table_1";
		$stmt = sqlsrv_prepare( $conn, $sql );
		
		foreach( sqlsrv_field_metadata( $stmt ) as $fieldMetadata ) {
		    foreach( $fieldMetadata as $name => $value) {
		       echo "$name: $value<br />";
		    }
		      echo "<br />";
		}
	}
	
	
	/**
	 * @desc 执行SELECT语句获得一条记录
	 * @ $sql 语句
	 * @return $array
	 */
	public function getRow($sql)
	{
		$stmt = $this->query ( $sql );
		$result = $this->fetch_array($stmt);
		$this->free_result($stmt);
		return $result[0];
	}
	
	/**
	 * @desc 执行SELECT语句获取所有记录
	 * @param $sql 语句
	 */
	public function getAll($sql)
	{
		$stmt = $this->query($sql);
		$result = $this->fetch_array($stmt);
		return $this->free_result($stmt);
		
	}
	
	/**
	 * @desc 传入表名  条件 获取记录数
	 * @param $tablename 表名
	 * @param $where 条件
	 * @return number
	 */
	public function count($tablename, $where = '1=1')
	{
		if($this->table_exists($tablename))
		{
			$sql = "select count(*) num from $tablename where $where";
			$stmt = $this->query($sql);
			$result = $this->fetch_array($stmt);
			$this->free_result($stmt);
			return $result[0]["NUM"];
		}
		else
			return false;
	}
	
	/**
	 * @param 序列名
	 * @desc 获取即将插入语句的ID
	 * @return int  number代表序列   0找不到序列
	 */
	public function insert_id($sequence_name)
	{
		$id = 0;
		$sql = "select SEQUENCE_NAME from USER_SEQUENCES";
		$stmt = $this->query($sql);
		$result = $this->fetch_array($stmt);
		if(count($result)>0)
		{
			foreach ($result as $v)
			{
				$arr[]= $v['SEQUENCE_NAME'];
			}
			if(in_array(strtoupper($sequence_name), $arr))
			{
				$sql_1 ="select ".strtoupper($sequence_name).".nextval from dual";
				$array = $this->get_one ($sql_1);
				$id = $array['NEXTVAL'];
			}
			else 
				$id = 0;
		}
		else 
			$id = 0;
		$this->free_result($stmt);
		return $id;
	}
	
	/**
	 * @desc 检查表是否存在
	 * @param $sql 语句
	 * @return boolean $flag
	 */
	public function table_exists($tablename)
	{
		$flag = false;
		$sql = "select TABLE_NAME from USER_TABLES";
		$stmt = $this->query($sql);
		$result = $this->fetch_array($stmt);
		if(count($result)>0)
		{
			foreach ($result as $v)
			{
				$arr[]= $v['TABLE_NAME'];
			}
			if(in_array(strtoupper($tablename), $arr))
				$flag = true;
			else
				$flag = false;
		}
		else
			$flag = false;
		$this->free_result($stmt);
		return $flag;
	}
	
	/**
	 * @desc 获取表的主键字段名
	 * @param $table 表名
	 * @return 字段名
	 */
	public function get_primary_key($tablename)
	{
		if($this->table_exists($tablename))
		{
			$sql = "select * from user_cons_columns where constraint_name =(select constraint_name from user_constraints where table_name = '".strtoupper($tablename)."' and constraint_type ='P')";
			$stmt = $this->query($sql);
			$result = $this->fetch_array($stmt);
			if(count($result)>0)
				$column = $result[0]['COLUMN_NAME'];
			else
				$column = "";
			$this->free_result($stmt);
			return $column;
		}
		else
			$column = "";
	}
	
	/**
	 * @param resource $stmt
	 * @desc 返回 OCI 语句的类型
	 * @return 1.SELECT 2.UPDATE 3.DELETE 4.INSERT 5.CREATE 6.DROP 7.ALTER 8.BEGIN 9.DECLARE 10.UNKNOWN
	 */
	public function statement_type($stmt)
	{
		return oci_free_statement($stmt);
	}
	
	
	
	
	
	
	
	
	
	/************************* sqlserver事务 ******************************/
	/*
		1.获取句柄
		2.开始事务
		if ( sqlsrv_begin_transaction( $conn ) === false ) {
		     die( print_r( sqlsrv_errors(), true ));
		}
		// Initialize parameter values.
		$orderId = 1; $qty = 10; $productId = 100;
		
		// Set up and execute the first query. 
		$sql1 = "INSERT INTO OrdersTable (ID, Quantity, ProductID) VALUES (?, ?, ?)";
		$params1 = array( $orderId, $qty, $productId );
		$stmt1 = sqlsrv_query( $conn, $sql1, $params1 );
		
		// Set up and execute the second query. 
		$sql2 = "UPDATE InventoryTable SET Quantity = (Quantity - ?) WHERE ProductID = ?";
		$params2 = array($qty, $productId);
		$stmt2 = sqlsrv_query( $conn, $sql2, $params2 );
		
		// If both queries were successful, commit the transaction. 
		// Otherwise, rollback the transaction. 
		if( $stmt1 && $stmt2 ) {
		     sqlsrv_commit( $conn );
		     echo "Transaction committed.<br />";
		} else {
		     sqlsrv_rollback( $conn );
		     echo "Transaction rolled back.<br />";
		}
	*/
	
	/**
	 * @desc 开始事务
	 * @param $conn 连接句柄
	 * @return boolean
	 */
	public function ​begin_​transaction()
	{
		return sqlsrv_begin_transaction($this->conn);
	}
	
	/**
	 * @desc 提交事务
	 * @param $conn 连接句柄
	 * @return boolean
	 */
	public function commit()
	{
		return sqlsrv_commit($this->conn);
	}
	
	/**
	 * @desc 回滚事务
	 * @param $conn 连接句柄
	 * @return boolean
	 */
	public function rollback()
	{
		return sqlsrv_rollback($this->conn);
	}
	
	/**
	 * 检测结果集是否具有一行或多行。
	 * 判断数据库是否有查询结果好用
	 * @param resource $stmt
	 */
	public function has_rows($stmt)
	{
		$rows = sqlsrv_has_rows( $stmt );
		if ($rows === true)
			return true;
		else
			return false;
	}
	
	/**
	 * 使下一结果可供处理。
	 * @param resource $stmt
	 * @example
	 * 		$next_result = sqlsrv_next_result($stmt);
			if( $next_result ) {
			   while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC)){
			      echo $row['id'].": ".$row['data']."<br />"; 
			   }
			} elseif( is_null($next_result)) {
			     echo "No more results.<br />";
			} else {
			     die(print_r(sqlsrv_errors(), true));
			}
	 */
	public function next_result($stmt)
	{
		$next_result = sqlsrv_next_result($stmt);
		if($next_result === true)
			return true;
		if (is_null($next_result))
			return null;
		else {
			$this->errors();
			return false;
		}	
	}
	
	/**
	 * 返回有所修改的行的数目。
	 * @desc 获取 insert update delete sql的影响记录数
	 * @param resource $stmt
	 * @return number
	 */
	public function num_affected($stmt)
	{
		return sqlsrv_rows_affected($stmt);
	}
	
	/**
	 *  报告结果集中的行数。//要求静态或键集游标；如果您使用前进游标或动态游标，将返回 false。（前进游标是默认设置。）
	 *  $stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'keyset' ));
	 *  $stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'dynamic' ));
		$stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'static' ));
		SQLSRV_CURSOR_FORWARD（默认，前进游标，该函数不可用false）此游标类型使您可以从结果集的第一行开始一次移动一行，直到到达结果集的末尾。
		SQLSRV_CURSOR_STATIC（静态游标，该函数可使用）此游标使您可按任何顺序访问行，但将不会反映数据库中的更改。
		SQLSRV_CURSOR_DYNAMIC（该函数不可用false）此游标使您可按任何顺序访问行，并且将会反映数据库中的更改。
		SQLSRV_CURSOR_KEYSET（该函数可使用）此游标使您可按任何顺序访问行。但是，如果从表中删除某一行，键集游标将不更新行计数（返回删除的行且没有任何值）。
	 * 	@desc 获取查询语句中的结果集数量
	 * 	@param resource $stmt
	 * 	@return number
	 */
	public function num_rows($stmt)
	{
		return sqlsrv_num_rows($stmt);
	}
	
	/**
	 * 检索活动结果集中的字段数。
	 * @desc 获取查询语句中的字段数量
	 * @param resource $stmt
	 * @return number
	 */
	public function num_fields($stmt)
	{
		return sqlsrv_num_fields($stmt);
	}
	
	/**
	 * 关闭语句。释放与相应语句关联的所有资源。
	 * @desc 释放关联sql语句或游标的所有资源
	 * @param resource $stmt
	 * @return boolean
	 */
	public function free_stmt($stmt)
	{
		return sqlsrv_free_stmt($stmt);
	}
	
	/**
	 * 返回SQLServer版本
	 * 打印
	 */
	public function client_info()
	{
		echo "Client Version: <br />";
		if( $client_info = sqlsrv_client_info( $this->conn)) {
			foreach( $client_info as $key => $value) {
				echo $key.": ".$value."<br />";
			}
		} else {
			echo "Error in retrieving client info.<br />";
		}
	}
	
	/**
	 * 返回SQLServer版本
	 * 打印 string
	 */
	public function server_info()
	{
		echo "Server Version: <br />";
		if( $server_info = sqlsrv_server_info ($this->conn)) {
			foreach( $server_info as $key => $value) {
				echo $key.": ".$value."<br />";
			}
		} else {
			echo "Error in retrieving server info.<br />";
		}
	}
	
	/**
	 * 获取数据库错误信息
	 */
	public function errors()
	{
		$errors = sqlsrv_errors();
		
		if( $errors != null) {
			if($this->debug) {
				echo '<p style="color:red;">错误信息：</p>';
				foreach( $errors as $error ) {
					echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
					echo "code: ".$error[ 'code']."<br />";
					echo "message: ".$error[ 'message']."<br />";
				}
			} else {
				header('Location:/error.php');
			}
			die();
		} 
	}
	
	/**
	 * @desc 更改错误处理和日志记录配置。
	 * @param $setting    
	 * f
	 * WarningsReturnAsErrors
	 * 1 (TRUE) or 0 (FALSE)
	 * 
	 * LogSubsystems
	 * SQLSRV_LOG_SYSTEM_ALL (-1) 
	 * SQLSRV_LOG_SYSTEM_CONN (2) 
	 * SQLSRV_LOG_SYSTEM_INIT (1) 
	 * SQLSRV_LOG_SYSTEM_OFF (0) 
	 * SQLSRV_LOG_SYSTEM_STMT (4) 
	 * SQLSRV_LOG_SYSTEM_UTIL (8)
	 * 
	 * LogSeverity
	 * SQLSRV_LOG_SEVERITY_ALL (-1) 
	 * SQLSRV_LOG_SEVERITY_ERROR (1) 
	 * SQLSRV_LOG_SEVERITY_NOTICE (4) 
	 * SQLSRV_LOG_SEVERITY_WARNING (2)
	 * 
	 * @return true false
	 */
	public function configure($setting ,$value)
	{
		return sqlsrv_configure ($setting ,$value );
	}
	
	/**
	 * @param resource $stmt
	 * 取消语句；并放弃相应语句的所有未决结果。
	 * @return true false
	 */
	public function cancel($stmt)
	{
		return sqlsrv_cancel($stmt);
	}
	
	/**
	 * @desc 关闭连接
	 * @param $conn 连接句柄
	 * @return 成功时返回 TRUE， 或者在失败时返回 FALSE。
	 */
	public function close()
	{
		return sqlsrv_close($this->conn);
	}

}

?>