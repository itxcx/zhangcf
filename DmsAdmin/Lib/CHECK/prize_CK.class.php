<?php
	/*奖金相关自检*/
	class prize_CK
	{
		//prize奖金prizemode=1的奖金，在奖金表记录中为负数
		//prize奖金prizemode=2的奖金，在奖金表记录中为正数
		public function check($tlename='',$prizename='',$prizemode=0)
		{
			$rs = array();
			if($prizemode==1){
				$rs=M($tlename)->where($prizename."<0")->find();
				if(!empty($rs)){
					return "<span style='color:red;'>".$prizename."的prizemode为".$prizemode."，但是在奖金表记录中存在负数</span><br>";
				}else{
					return 1;
				}
			}elseif($prizemode==2){
				$rs=M($tlename)->where($prizename.">0")->find();
				if(!empty($rs)){
					return "<span style='color:red;'>".$prizename."的prizemode为".$prizemode."，但是在奖金表记录中存在正数</span><br>";
				}else{
					return 1;
				}
			}else{
				return 1;
			}
			
		} 
		//各个奖金累计与会员表中的累计不符
		public function checklj($tlename='',$prizename='',$prizemode=0)
		{
			$rs = array();
			if(is_array($tlename)){
				$tblstr='';
				foreach($tlename as $tbl){
					if($tblstr !='' )$tblstr .= " union all ";
					$tblstr .= "select * from dms_".$tbl;
				}
				$rs=M('会员')->alias('a')->join("inner join (SELECT 编号,sum(收入)cnt FROM (".$tblstr.")c  group by 编号)b on a.编号=b.编号 and a.累计收入<>b.cnt")->getField('id');
				// dump(M()->_sql());
				if(!empty($rs)){
					return "<span style='color:red;'>会员表中累计收入与实际收入不符</span><br>";
				}else{
					return 1;
				}
			}else{
				if($prizemode==1){
					$rs=M('会员')->alias('a')->join("inner join (SELECT 编号,sum(".$prizename.")cnt FROM `dms_".$tlename."`  group by 编号)b on a.编号=b.编号 and a.".$prizename."累计<>b.cnt")->getField('id');
					if(!empty($rs)){
						return "<span style='color:red;'>会员表中".$prizename."与奖金记录不符</span><br>";
					}else{
						return 1;
					}
				}elseif($prizemode==2){
					$rs=M('会员')->alias('a')->join("inner join (SELECT 编号,sum(".$prizename.")*(-1) cnt FROM `dms_".$tlename."`  group by 编号)b on a.编号=b.编号 and a.".$prizename."累计<>b.cnt")->getField('id');
					if(!empty($rs)){
						return "<span style='color:red;'>会员表中".$prizename."与奖金记录不符</span><br>";
					}else{
						return 1;
					}
				}else{
					return 1;
				}
			}
			
		}
		//奖金明细中奖金或者收入为负
		public function checkTle($tlename='')
		{
			$rs = array();
			$rs=M($tlename)->where("奖金<0 or 收入<0")->find();
			if(!empty($rs)){
				return "<span style='color:red;'>在".$tlename."表中存在奖金或收入为负数的记录</span><br>";
			}else{
				return 1;
			}
			
		}
		
		//结转
		public function checkTlePv($tlename,$netname,$cons)
		{	
			set_time_limit(0);
			ini_set('memory_limit','500M'); 
			$db = Db::getInstance();
			$db->query('select 1');

			$error="";
			/*$bumpset = array();
			foreach(explode(':',$bump) as $k=>$val)
			{ 
				if($val!="")
				{
					$bumpset[] =(float)$val;
				}
			} */
			
			$net = X($netname.'@');
			$branch = $net->getBranch();
			$newrows = '';
			foreach($branch as $key=>$region)
			{
				$newrows .= ','.$net->name.'_'.$region.'区本日业绩';
				$newrows .= ','.$net->name.'_'.$region.'区累计业绩';
				$newrows .= ','.$net->name.'_'.$region.'区结转业绩';
				//销售表中新增
				$rs=M($tlename)->alias('a')->join(" inner join(select userid,sum(val)val from dms_".$net->name."_业绩 where pid>0 and region=".($key+1)." group by userid) b on a.id=b.userid and a.".$net->name.'_'.$region."区本日业绩<>b.val")->getField('id');
				if(!empty($rs)){
					$error .= "<span style='color:red;'>".$tlename."表中存在".$net->name.'_'.$region."区本日业绩与实际业绩不符的记录</span><br>";
				}
			}
			
			$username='';//记录当前用户
			$prejz = array();
			$prelj = array();
			foreach($branch as $region){
				$prejz[$region]=0;
				$prelj[$region]=0;
			}
			//按照 编号,时间排序查奖金表
			$olddata=mysql_unbuffered_query("select id,编号,会员级别,计算日期".$newrows." from dms_".$tlename." order by 编号,计算日期",$db->_linkID);
			while($user = mysql_fetch_assoc($olddata))
			{
				
				$t_cons=array();
				//得到符合当前会员条件的CONS
				foreach($cons as $con)
				{
					if(transform($con["where"],$user))
					{
						$bump=$con['bump'];
					}
				}
				//根据con找到对碰比例
				$bumpset = array();
				foreach(explode(':',$bump) as $k=>$val)
				{ 
					if($val!="")
					{
						$bumpset[] =(float)$val;
					}
				} 

				//通过变量根据奖金表的新增业绩，计算累计和结转业绩
				$bumpval = array();
				$bumpval2 = array();
				if($username!=$user['编号']){
					foreach($branch as $region){// 结转业绩 累计业绩
						$prejz[$region]=0;
						$prelj[$region]=0;
					}
				}
				$finderr=false;
				foreach($branch as $region)
				{
					$bumpval[$region]=$user[$net->name.'_'.$region.'区本日业绩']+$prejz[$region];
					$bumpval2[$region]=$bumpval[$region];
					$prelj[$region] = $user[$net->name.'_'.$region.'区本日业绩']+$prelj[$region];//累计业绩
					
					if($user[$net->name.'_'.$region.'区结转业绩']!=$prejz[$region]){//比较结转业绩
						$finderr=true;
						$user[$net->name.'_'.$region.'区结转业绩']=$prejz[$region];
						$error .=date('Y-m-d',$user['计算日期']).' '.$user['编号'].$region."区结转业绩出错".$prejz[$region];
					}
					if($prelj[$region] != $user[$net->name.'_'.$region.'区累计业绩']){
						$finderr=true;
						$user[$net->name.'_'.$region.'区累计业绩']=$prelj[$region];
						$error .= date('Y-m-d',$user['计算日期']).' '.$user['编号'].$region."累计业绩出错";
					}
				}
				

				//定义可能出现最大扫单量
				$bump=floor($bumpval[$branch[0]]/$bumpset[0]);
				//判定扫单量
				foreach($bumpset as $k=>$val)
				{
					if($bumpset[$k]!=0 && $bump>floor($bumpval2[$branch[$k]]/$bumpset[$k]))
					{
						$bump=floor($bumpval2[$branch[$k]]/$bumpset[$k]);
					}
				 }
				 foreach($bumpset as $k=>$val)
				 {
				 	 //设置被使用数量
					$bumpval2[$branch[$k]] = $bumpset[$k] * $bump;
					//计算结转
					$tmp = 'prejz_'.$branch[$k];
					$prejz[$branch[$k]] = $bumpval[$branch[$k]]-$bumpval2[$branch[$k]];				
				 }
				 
				if($finderr)
				{
					break;
				}			
				 $username=$user['编号'];
			}
			//关闭数据库
			$db->close();
			if($error != ""){
				return $tlename."表中".$error;
			}else{
				return 1;
			}
		
		} 


	}
?>