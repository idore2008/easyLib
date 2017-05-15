<?php
/*
* 通用分页类
*/
class Page
{
	
	protected $page_size; //分页大小
	protected $rows_num; //数据总数
	protected $pages;     //分页总数
	protected $page;      //当前页
	protected $url;       //分页地址
	protected $arg;       //分页参数变量

	function __construct($url,$arg,$page,$page_size,$rows_num)
	{
		$this->url = $url;
		$this->arg = $arg;
		$this->pages = ceil($rows_num/$page_size);
		if((int)$page > $this->pages) 
			$this->page = $this->pages;
		if((int)$page < 1) 
			$page = 1;
		$this->page = (int)$page;
		$this->page_size = $page_size;
		$this->rows_num = $rows_num;
	}

	#获取偏移量
	public function offset()
	{
		return ($this->page-1)*$this->page_size;
	}

	#显示统计
	public function showTotalCount()
	{
		$nav  = '&nbsp;';
		$nav .= '<strong style="margin-left:6px;">总条数</strong>:'.$this->rows_num;
		return $nav;
	}
	
	#显示当前页码
	public function showCurrentPage()
	{
		$nav  = '&nbsp;';
		$nav .= '<strong style="margin-left:6px;">页数</strong>:'.$this->page.'/'.$this->pages;
		return $nav;
	}

	#仿搜索引擎
	public function showSpanNav( $nav_span = 5 ,$hash = '')
	{
	
		if($this->page <= $nav_span)
			$page_start = 1;
		else 
			$page_start = $this->page - $nav_span;
		
		if($this->page + $nav_span < $this->pages)
			$page_end = $this->page + $nav_span;
		else 
			$page_end = $this->pages;
		
		$nav = "";
		if($this->page != 1)
		{
			$pre_page = $this->page-1;
			$nav .= "<a href='{$this->url}?{$this->arg}=1{$hash}'><strong>首页</strong></a> ";
			$nav .= "<a href='{$this->url}?{$this->arg}={$pre_page}{$hash}'><strong>上一页</strong></a> ";
		}
		
		for($i=$page_start; $i<=$page_end; $i++)
		{
			if($this->page == $i)
				$nav .= "<a href='#' class='current'>{$i}</a> ";
			else
				$nav .= "<a href='{$this->url}?{$this->arg}={$i}{$hash}'>{$i}</a> ";
		}
		
		if($this->page!=$this->pages)
		{
			$nex_page = $this->page+1;
			$nav.= "<a href='{$this->url}?{$this->arg}={$nex_page}{$hash}'><strong>下一页</strong></a> ";
			$nav.= "<a href='{$this->url}?{$this->arg}={$this->pages}{$hash}'><strong>末页</strong></a>";
		}
		if ($this->pages <= 1) 
			$nav = "";
		return $nav;
	}

	#下拉菜单型
	public function showDropList()
	{
		$nav = "&nbsp;<select onchange=\"location.href='{$this->url}?{$this->arg}='+this.value;\">\n";
		for($i=1;$i<=$this->pages;$i++)
		{
			if($this->page == $i)
				$nav.= "<option value='{$i}' selected='selected'>Page {$i}</option>";
			else
				$nav.= "<option value='{$i}'>Page {$i}</option>";
		}
		$nav.= "</select>";		
		return $nav;
	}
	#文本框型
}
?>