<?php
	class fun_val extends stru
	{
		public $regDisp = false;
		//是否需要检查会员编号是否存在
		public $usernameLock = false;
		public $resetrequest='';
		public $mode = 'numeric(10,2)';
		public $required = false;
		public $default = '0';
		public $per = false;
		//后台会员资料可修改
		public $adminEdit=false;
		//后台会员资料可查看
		public $adminView=false;
		public function event_scal()
		{
			//$this->runexp();
		}
		public function event_cal($tle,$caltime)
		{
			$this->runexp();
		}
		
		private function runexp()
		{
			$cons = $this->getcon("con",array('val'=>'','where'=>''));
			$sql='';
			foreach($cons as $con)
			{
				//此设定是否为增加
				$addstate=false;
				if(substr($con['val'],0,1) == '+')
				{
					$con['val']=substr($con['val'],1);
					$addstate=true;
				}
				$sql='update dms_会员';
				
				if(preg_match("/\{([^,]+),(.*)\}/U",$con['val'],$exp))
				{
					$username='会员';
					//特别处理
					switch($exp[1])
					{
						//下级业绩数据
						case 'per':	
						//$sql.='left join ';
						list($net,$row,$con1) = explode(',',$exp[2]);
						//特定团队数
						if(is_numeric($con1))
						{
							$tmpstr="select {$net}_上级编号 编号,{$row} val from ( 
									 select heyf_tmp.编号,heyf_tmp.{$net}_上级编号,heyf_tmp.$row,@rownum:=@rownum+1 , 
									 if(@pdept=heyf_tmp.{$net}_上级编号,@rank:=@rank+1,@rank:=1) as rank, 
									 @pdept:=heyf_tmp.{$net}_上级编号 
									 from (  
									 select 编号,{$net}_上级编号,$row from dms_会员 order by {$net}_上级编号 asc ,$row desc  
									 ) heyf_tmp ,(select @rownum :=0 , @pdept := null ,@rank:=0) a ) result where rank={$con1}";
							$sql .= " left join ({$tmpstr}) b on b.编号=dms_会员.编号";
							$valsql ='ifnull(b.val,0)';
						}
						if(strpos($con1,'-')!==false)
						{
							list($num1,$num2) = explode('-',$con1);
							if(empty($num2))$num2=99999999;
							$tmpstr="select {$net}_上级编号 编号,sum({$row}) val from ( 
											 select heyf_tmp.编号,heyf_tmp.{$net}_上级编号,heyf_tmp.$row,@rownum:=@rownum+1 , 
											 if(@pdept=heyf_tmp.{$net}_上级编号,@rank:=@rank+1,@rank:=1) as rank, 
											 @pdept:=heyf_tmp.{$net}_上级编号 
											 from (  
											 select 编号,{$net}_上级编号,$row from dms_会员 order by {$net}_上级编号 asc ,$row desc  
											 ) heyf_tmp ,(select @rownum :=0 , @pdept := null ,@rank:=0) a ) result where  rank>={$num1} and rank<={$num2} group by {$net}_上级编号";
							$sql .= " left join ({$tmpstr}) b on b.编号=dms_会员.编号";
							$valsql ='ifnull(b.val,0)';
						}
						break;
						//下级业绩数据
						case 'down':	
						list($net,$con1) = explode(',',$exp[2]);
							if($con1=='')
								$con1='1=1';
							//$tmpstr="select {$net}_上级编号 编号,count(1) val  from `dms_会员` where {$con1} group by {$net}_上级编号";
							$tmpstr="select a.{$net}_上级编号 编号,count(1) val  from `dms_会员` a inner join dms_会员 ck on a.{$net}_上级编号=ck.编号 where {$con1} group by a.{$net}_上级编号";
							//strpos	查找字符是否在字符串里出现
							//str_replace	替换字符
							if(strpos($tmpstr,'[')!==false){
								$tmpstr=str_replace("U[","a.",$tmpstr);
								$tmpstr=str_replace("M[","ck.",$tmpstr);
								$tmpstr=str_replace("]","",$tmpstr);
							}
							$sql .= " d  inner join ({$tmpstr})b";
							$valsql ='ifnull(b.val,0) where d.编号=b.编号';
							/*$sql .= " left join ({$tmpstr}) b on b.编号=dms_会员.编号";
							$valsql ='ifnull(b.val,0)';*/
						break;
						//统计符合条件的团队数
						//{推荐,[级别]>=2 and U[推荐_层数]-M[推荐_层数]<=3,>3}
						//伞下团队人数
						case 'allsum':
							list($net,$where,$minlayer,$maxlayer) = explode(',',$exp[2]);
							$data=M('会员')->where($where)->getField('id,'.$net.'_网体数据');
							$update=array();
							foreach($data as $key=>$d)
							{
								if($d!='')
								{
									$ret=array_reverse(explode(',',$d));
									$ret=($maxlayer==0)?array_slice($ret,$minlayer-1):array_slice($ret,$minlayer-1,$maxlayer-$minlayer+1);
									foreach($ret as $ret2)
									{
										if(!isset($update[$ret2]))
											$update[$ret2]=0;
										$update[$ret2]+=1;
									}
								}
							}
							asort($update);
							$data1=array();
							$i=1;
							foreach($update as $key=>$val)
							{
								if($i>1 && $val==$data1[$i-1]['val']){
				                	$data1[$i-1]['id'][]=$key;
								}else{
								    $data1[$i]['val']=$val;
								    $data1[$i]['id'][]=$key;
									$i++;
								}
							}
							foreach($data1 as $data)
							{
								if($addstate)
									M('会员')->where(array('id'=>array('in',$data['id'])))->setInc($this->name,$data['val']);
								else
									M('会员')->where(array('id'=>array('in',$data['id'])))->save(array($this->name=>$data['val']));
							}
							continue 2;
						break;
					}
				}
				else
				{
					$valsql=delsign($con['val']);
				}
				if($addstate)
				{
					$sql.=' set '.$this->name."=".$this->name.'+'.$valsql;
				}
				else
				{
					$sql.=' set '.$this->name . "=" . $valsql;
				}
				if($con['where'] != '')
				{
					$sql .= ' where '.delsign($con['where']);
				}
				M()->execute($sql);
			}
		}
		public function event_valadd($user,$val,$option)
		{
			if($this->per){
				//获取订单的ID
				$saleid = isset($option['saleid'])?$option['saleid']:0;
				//自己产生的业绩
				$indata = array(
					'userid'=>$user['id'],
					'val'=>$val,
					'saleid'=>$saleid,
					'time'=>systemTime(),
				);
				//插入自己的业绩
				M($this->name.'_业绩')->add($indata);
			}
			//更新会员表
			$data[$this->name]=$user[$this->name]+$val;
			M("会员")->where(array("id"=>$user['id']))->save($data);
			//$this->update();
		}
	}
?>