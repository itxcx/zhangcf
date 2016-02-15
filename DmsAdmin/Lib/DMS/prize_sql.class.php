<?php

	class prize_sql extends prize
	{
		public $prizeMode=-1;
		public $sql="";
		
		function scal()
		{
			$this->cal();
		}
		function cal()
		{
			if(!$this->ifrun()) return;
			$caltime = $this->_caltime;
			$sql=$this->sql;
			//>>
			$sql = str_replace('>>','<',$sql);
			$sql = str_replace('{caltime}' ,$caltime,$sql);
			$sql = str_replace('{calyear}' ,date('y',$caltime),$sql);
			$sql = str_replace('{calmonth}',date('m',$caltime),$sql);
			
			//prize基类的替换函数
			$sql = self::calReplace($sql,"prize_sql",$caltime);
			if(M()->execute($sql)===false)
			{
				throw_exception("警告:prize_sql节点".$sql."语句执行失败<br/>");
			}
		}
	}
?>