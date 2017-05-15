<?php
/*表单类*/
class Form
{
	
	//单文本
	public function text($name, $id = '', $value = '', $type = 'text', $size = 50, $class = '', $ext = '')
	{
		return "<input type=\"$type\" name=\"$name\" id=\"$id\" value=\"$value\" size=\"$size\" class=\"$class\" $checkthis $ext/> ";
	}
	
	
	//文本域
	public function textarea($name, $id = '', $value = '', $rows = 10, $cols = 50, $class = '', $ext = '')
	{
		if(!$id) $id = $name;
		return "<textarea name=\"$name\" id=\"$id\" rows=\"$rows\" cols=\"$cols\" class=\"$class\" $ext>$value</textarea>";
	}
	
	
	//单选按钮
	public function radio($options, $name, $id = '', $value = '', $cols = 5, $class = '', $ext = '', $width = 100)
	{
		if(!$id) $id = $name;
		if(!is_array($options)) $options = form::_option($options);
		$i = 1;
		$data = '';
		if($class) $class = " class=\"$class\"";
		foreach($options as $k=>$v)
		{
			$checked = $k == $value ? 'checked' : '';
			//$data .= "<span style=\"width:{$width}px\"><input type=\"radio\" name=\"{$name}\" id=\"{$id}\" value=\"{$k}\"  $class {$ext} {$checked}/> {$v}</span> ";
			$data .= "<label><input type=\"radio\" name=\"{$name}\" id=\"{$id}\" value=\"{$k}\"  $class {$ext} {$checked}/> {$v} </label>";
			if($i%$cols == 0) $data .= "\n";
			$i++;
		}
		return $data;
	}
	
	//复选框
	
	public function checkbox($options, $name, $id = '', $value = '', $cols = 5, $class = '', $ext = '', $width = 100)
	{
		if(!$options) return '';
		if(!$id) $id = $name;
		if(!is_array($options)) $options = form::_option($options);
		$i = 1;
		if($class) $class = " class=\"$class\"";
		if($value != '') $value = strpos($value, ',') ? explode(',', $value) : array($value);
		foreach($options as $k=>$v)
		{
			$checked = ($value && in_array($k, $value)) ? 'checked' : '';
			//$data .= "<span style=\"width:{$width}px\"><input type=\"checkbox\" boxid=\"{$id}\" name=\"{$name}[]\" id=\"{$id}\" value=\"{$k}\"  $class {$ext} {$checked}/> {$v}</span>\n ";
			$data .= "<label><input type=\"checkbox\" id=\"{$id}\" name=\"{$name}[]\" value=\"{$k}\"  $class {$ext} {$checked}/> {$v}</label>";
			if($i%$cols == 0) $data .= "</br>";
			$i++;
		}
		return $data;
	}
	
	//单选select
	
	public function select($options, $name, $id = '', $value = '', $size = 1, $class = '', $ext = '')
	{
		if(!$id) 
			$id = $name;
		if(!is_array($options)) 
			$options = Form::_option($options);
		if($size >= 1) 
			$size = " size=\"$size\"";
		if($class) 
			$class = " class=\"$class\"";
		$data .= "<select name=\"$name\" id=\"$id\" $size $class $ext>";
		$data .= "<option value=\"\" >===select===</option>\n";
		foreach($options as $k=>$v)
		{
			$selected = (trim($k) == trim($value)) ? 'selected' : '';
			$data .= "<option value=\"$k\" $selected>$v</option>\n";
		}
		$data .= '</select>';
		return $data;
	}
	
	// linchangxiang 2015-5-12 start
	//单选select
	public function select_previous($options, $name, $id = '', $value = '', $size = 1, $class = '', $ext = '')
	{
		if(!$id) $id = $name;
		if(!is_array($options)) $options = Form::_option($options);
		if($size >= 1) $size = " size=\"$size\"";
		if($class) $class = " class=\"$class\"";
		$data .= "<select name=\"$name\" id=\"$id\" $size $class $ext>";
		$data .= "<option value=\"-1\" >===select===</option>\n";
		foreach($options as $k=>$v)
		{
			$selected = (trim($k) == trim($value)) ? 'selected' : '';
			$data .= "<option value=\"".($k+1)."\" $selected>$v</option>\n";
		}
		$data .= '</select>';
		return $data;
	}
	// linchangxiang 2015-5-12 end
	
	//多选select
	public function multiple($options, $name, $id = '', $value = '', $size = 3, $class = '', $ext = '')
	{
		if(!$id) $id = $name;
		if(!is_array($options)) $options = form::_option($options);
		$size = max(intval($size), 3);
		if($class) $class = " class=\"$class\"";
		$value = strpos($value, ',') ? explode(',', $value) : array($value);
		$data .= "<select name=\"$name\" id=\"$id\" multiple=\"multiple\" size=\"$size\" $class $ext>";
		foreach($options as $k=>$v)
		{
			$selected = in_array($k, $value) ? 'selected' : '';
			$data .= "<option value=\"$k\" $selected>$v</option>\n";
		}
		$data .= '</select>';
		return $data;
	}
	
	
	//$options = "0-10|0-10\n10-20|10-20\n20-30|20-30\n30-40|30-40\n40-50|40-50\n50以上|50以上\n未知|未知";
	
	//$options = array('0-10' => '0-10','10-20' => '10-20','20-30' => '20-30','30-40' => '30-40','40-50' => '40-50','50以上' => '50以上','未知' => '未知');
	
	//选项,radio,checkbox,select
	
	public function _option($options, $s1 = "\n", $s2 = '|')
	{
		$options = explode($s1, $options);
		foreach($options as $option)
		{
			if(strpos($option, $s2))
			{
				list($name, $value) = explode($s2, trim($option));
			}
			else
			{
				$name = $value = trim($option);
			}
			$os[$value] = $name;
		}
		return $os;
	}
	
	
	//上传文件
	public function file($name, $id = '', $size = 50, $class = '', $ext = '')
	{
		if(!$id) $id = $name;
		return "<input type=\"file\" name=\"$name\" id=\"$id\" size=\"$size\" class=\"$class\" $ext/> ";
	}
	
	
	
	
	
}
?>