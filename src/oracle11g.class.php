<?php

/**
 * @desc php操作Oracle类
 * @version 2.0
 * @author jiangtao
 * @date 2015-11-19
 */

class Oracle11g
{
	public $conn; // 连接句柄
	public $debug = 1; // 是否输出调试信息
	                   
	// 初始化数据库信息
	function __construct($debug = 1)
	{
		$this->debug = $debug;
	}
	
	/**
	 * @desc 无参构造函数
	 */
	function Oracle11g()
	{
		$this -> __construct();
	}
	
	/**
	 * @desc 数据库连接 数据编码自行选择，本例为：AL32UTF8   GB2312
	 * @param $dbhost 主机
	 * @param $dbuser 用户名
	 * @param $dbpwd 密码
	 * @param $dbname 数据库服务名
	 * @param $charset 字符集    默认UTF8
	 * @return $conn 连接句柄
	 */
	function connect($dbhost, $dbuser, $dbpwd, $dbname = '', $charset = 'UTF8')
	{
		if (! @$this->conn = oci_connect ( $dbuser, $dbpwd, $dbhost . "/" . $dbname, $charset ))
		{
			exit('数据库连接错误！');
		}
		return $this->conn;
	}
	
	/**
	 * @desc 执行sql语句
	 * @param $sql
	 * @return resource $stmt
	 */ 
    public function query($sql)
	{
		$stmt = oci_parse ( $this->conn, $sql );
		if (! oci_execute ( $stmt ))
		{
			$this->halt ( '执行SQL语句错误', $sql, $stmt );  
			return false; 
		}
		return $stmt;
	}
	
	/**
	 * @desc 执行SELECT语句
	 * @param $sql
	 * @param $keyField  主键字段
	 * @return array 关联数组
	 */
	function select($sql, $keyField = '')
	{
		$array = array ();
		$stmt = $this->query ( $sql );
		while ( $row = oci_fetch_array ( $stmt, OCI_ASSOC))
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
	public function exec($sql)
	{
		return $this->query ($sql);
	}
	
	/**
	 * 获取一组关联数组
	 * @param resource $stmt
	 * @param string $type =1(默认)； 解释：(type=1 表示[字段]=>value,type=2表示[自增数字]=>value,type=3表示同时存在)
	 * @return $array
	 */
	public function fetch_array($stmt,$type = 1)
	{
		if($type = 1)
			$mode = OCI_ASSOC;
		elseif ($type = 2)
			$mode = OCI_NUM;
		else 
			$mode = OCI_BOTH;
		$array = array();
		while (($row = oci_fetch_array ($stmt, $mode))!= false)
		{
			$array[] = $row;
		}
		return $array;
	}
	
	/**
	 * @desc 执行SELECT语句获得一条记录
	 * @ $sql 语句
	 * @return $array
	 */
	public function get_one($sql)
	{
		$stmt = $this->query ( $sql );
		$result = $this->fetch_array($stmt);
		$this->free_result($stmt);
		return $result[0];
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
	 * @desc 执行SELECT语句获取记录数
	 * @param $sql 语句
	 */
	public function total($sql)
	{
		$stmt = $this->query($sql);
		$result = $this->fetch_array($stmt);
		$this->free_result($stmt);
		return count($result);
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
	 * @desc 获取上一语句的影响记录数
	 * @param resource $stmt
	 * @return number
	 */
	public function affected_rows($stmt)
	{
		return oci_num_rows($stmt);
	}
	
	/**
	 * @desc 获取查询语句中的字段数量
	 * @param resource $stmt
	 * @return number
	 */
	public function num_fields($stmt)
	{
		return oci_num_fields($stmt);
	}
	
	/**
	 * @desc释放关联于语句或游标的所有资源
	 * @param resource $stmt
	 * @return boolean
	 */
	public function free_result($stmt)
	{
		return oci_free_statement($stmt);
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
	
	
	/**
	 * 返回ORACLE版本
	 * @return string
	 */
	public function version()
	{
		return "Client Version: ".oci_client_version();
	}
	
	/**
	 * 返回ORACLE版本
	 * @return string
	 */
	public function server_version()
	{
		return oci_server_version($this->conn);
	}
	/**
	 * @desc 输出数据库错误
	 */ 
	public function halt($message = '', $sql = '', $stmt)
	{
		 $errormsg = "<b>Oracle Query : </b><font style='font-size:14px;color:#FF0000;'>$sql</font> <br /><b> Oracle Error : </b>" . $this->error ( $stmt ) . " <br /><b> Message : </b> $message";
		if ($this->debug)
		{
			echo '<div style="font-size:12px;text-align:left; border:1px solid #9cc9e0; padding:1px 4px;color:#000000;font-family:Arial, Helvetica,sans-serif;"><span>' . $errormsg . '</span></div>';
			exit (0);
		}  
		//header('Location:../error.html');
		// $err = 'error.html';
		// die("<script>window.location.href='$err';</script>");
	}
	
	// 获取数据库错误信息
	public function error($stmt)
	{
		$e = @oci_error ($stmt);
		return $e['message'];
	}
	
	/**
	 * 关闭连接
	 */
	public function close()
	{
		return oci_close($this->conn);
	}
	/**
	 * 为了实现insert 操作的的私有函数
	 * @param string $val
	 * @return unknown|string|number
	 */
	private function check_type($val)
	{
		//is_array()、is_bool()、is_float()、is_integer()、is_null()、is_numeric()、is_object()、is_resource()、is_scalar() 和 is_string()
		$isdate = $this->check_date($val);
		if($isdate)
			return $isdate;
		else if(stripos($val,".nextval"))
			return $val;
		else if(strtolower(trim($val))=="default")
			return "default";  //默认值
		else if(is_array($val))
			return "'".implode(";",$val)."'";
		else if(is_bool($val))
			return $val ? 1:0 ;//
		else if(is_float($val))
			return round($val, 2);//number
		else if(is_int($val))
			return $val; //int
		else if(is_null($val))
			return "null"; //null
		else if(is_string($val))
			return "'$val'"; //string
		else
			return "''";//unknown
	}
	private function check_date($str)
	{
		$patten = "/^(([1-2][0-9]{3}(-|\/))((([1-9])|(0[1-9])|(1[0-2]))(-|\/))((([1-9])|(0[1-9])|([1-2][0-9])|(3[0-1]))))( ((([0-9])|(([0-1][0-9])|(2[0-3]))):(([0-9])|([0-5][0-9]))(:(([0-9])|([0-5][0-9])))?))?$/";
		if(preg_match($patten, $str,$array)&&strlen($str)<=19)
		{
			$split = substr($array[0],4,1);
			return "to_date('".$array[0]."','".str_replace('-', $split, 'YYYY-MM-DD HH24:MI:SS')."')";//strlen($array[0])==19 ? 'ymdhms' : strlen($array[0])==16 ? 'ymdhm' : 'ymd';
		}
		else
			return 0;
	}
}

?>