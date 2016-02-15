<?php
	/*网络相关自检*/
	class net_CK
	{
		public function check($netname='')
		{
			//上级编号是否存在
			$rs = array();
			$error = "";
			$rs=M('会员')->where($netname."_上级编号<>'' and binary ".$netname."_上级编号 not in(select 编号 from dms_会员)")->getField('id');
			if(!empty($rs)){
				$error .= "<span style='color:red;'>会员表中有".$netname."上级编号不存在（包含大小写问题）</span><br>";
			}
			$topuser = M('会员')->where(array($netname.'_上级编号'=>''))->select();
			if(count($topuser) > 1)
			{
				$error .= "<span style='color:red;'>".$netname."网发现两个及以上原始点</span><br>";
			}
			if($error != ""){
				return $error;
			}else{
				return 1;
			}
		}
		//推荐人数
		public function checktjs($netname='')
		{
			$rs = array();
			$rs=M('会员')->alias('a')->join(" inner join (select ".$netname."_上级编号,count(1)cnt from dms_会员 group by ".$netname."_上级编号)b on a.编号=b.".$netname."_上级编号 and a.".$netname."_".$netname."人数<>b.cnt")->getField('id');
			if(!empty($rs)){
				return "<span style='color:red;'>会员表中".$netname."人数与实际不符</span><br>";
			}else{
				return 1;
			}
		}
		//网络层数
		public function checkceng($netname='')
		{
			$rs = array();
			$rs=M('会员')->where($netname."_网体数据<>'' and ".$netname."_层数<> LENGTH(".$netname."_网体数据)- LENGTH(REPLACE(".$netname."_网体数据,',',''))+2")->getField('id');
			if(!empty($rs)){
				return "<span style='color:red;'>会员表中有".$netname."层数与实际不符</span><br>";
			}else{
				return 1;
			}
		}
		//管理_x区累计 ，新增  与 管理_业绩不符
		public function checkyj($netname='',$net)
		{
			$rs = array();
			$error = '';
			$branch = $net->getBranch();
			foreach($branch as $key=>$region)
			{
				//会员表中新增
				$rs=M('会员')->alias('a')->join(" inner join(select userid,sum(val)val from dms_".$netname."_业绩 where pid>0 and time>=".(strtotime(date("Y-m-d",systemTime())))." and region=".($key+1)." group by userid) b on a.id=b.userid and a.".$net->name.'_'.$region."区本日业绩<>b.val")->getField('id');
				if(!empty($rs)){
					$error .= "<span style='color:red;'>会员表中存在".$net->name.'_'.$region."区本日业绩与实际业绩不符的记录</span><br>";
				}
				//会员表中累计
				$rs=M('会员')->alias('a')->join(" inner join(select userid,sum(val)val from dms_".$netname."_业绩 where pid>0 and region=".($key+1)." group by userid) b on a.id=b.userid and a.".$net->name.'_'.$region."区累计业绩<>b.val")->getField('id');
				if(!empty($rs)){
					$error .= "<span style='color:red;'>会员表中存在".$net->name.'_'.$region."区累计业绩与实际业绩不符的记录</span><br>";
				}
			}
			//管理_业绩表中在会员表中找不到对应会员
			$rs=M($netname."_业绩")->where("userid not in(select id from dms_会员)")->getField('id');
			if(!empty($rs)){
				return "<span style='color:red;'>".$netname."_业绩表中在会员表中找不到对应会员</span><br>";
			}
			if($error!=''){
				return $error;
			}else{
				return 1;
			}
		}
		
		//网体数据
		public function checknetdata($netname='',$net)
		{
	   	    ini_set('memory_limit','5000M');
	   	    set_time_limit(0);
	   	    $error = '';
	   	    //对编号可能存在的大小写不一致的情况做修正
	   	    M()->execute("drop TEMPORARY table if exists netuser;");
	   	    M()->execute("create TEMPORARY table  netuser (id int(11),编号 varchar(50),".$netname."_网体数据 longtext,".$netname."_团队人数 int(11) DEFAULT 0,".$netname."_团队总人数 int(11) DEFAULT 0,PRIMARY KEY (id))");
	   	    M()->execute("insert into netuser(id,编号) select id,编号 from dms_会员");
	   	    
	   	    //取得需要用的信息表
	   	    if(get_class($net)=='net_rec'){
	   	    	$userdata = M('会员')->getField("编号,".$netname."_上级编号 上级编号,id,状态,0 num,0 allnum");
	   	    }else{
	   	    	$userdata = M('会员')->getField("编号,".$netname."_上级编号 上级编号,id,".$netname."_位置 位置,状态,0 num,0 allnum");
	   	    }
	   	    //取得要处理会员的信息表
	   	    $upusers  = M('会员')->getField('id,编号');
	   	    foreach((array)$upusers as $id=>$name)
	   	    {
	   	    	$user = $userdata[$name];
	   	   		if($user['上级编号'] != '')
	   	   		{
	   	   			//定义网体数据
	   	   			$netstr = '';
	   	   			//定义层数
	   	   			$layer  = 1;
	   	   			if(get_class($net)=='net_place'){
	   	   				//定义网络
	   	   				$region = $user['位置'];
	   	   			}
	   	   			//设置自己的上级
	   	   			$thisup = $user['上级编号'];
	   	   			//对上级进行链性表遍历,同时要防止死循环(存在互为上级的情况)
	   	   			
	   	   			while($thisup!='' && $layer<1000)
	   	   			{
	   	   				$layer++;
	   	   				//合成部分网体数据
	   	   				if(get_class($net)=='net_rec'){
	   	   					$netstr=$userdata[$thisup]['id'].','.$netstr;
	   	   				}else{
	   	   					$netstr=$userdata[$thisup]['id'].'-'.$region.','.$netstr;
	   	   				}
	   	   				//增加团队总人数
	   	   				$userdata[$thisup]['allnum']++;
	   	   				//增加有效人数
	   	   				if($user['状态']=='有效')$userdata[$thisup]['num']++;
	   	   				if(get_class($net)=='net_place'){
	   	   					$region = $userdata[$thisup]['位置'];
	   	   				}
	   	   				$thisup = $userdata[$thisup]['上级编号'];
	   	   			}
	   	   			$netstr = trim($netstr,',');
	   	   			//判断达到1000层,认为出现死循环情况
	   	   			if($layer >= 1000)
	   	   			{
	   	   				$error .= "<span style='color:red;'>出现了互为上级的情况,".$netstr."</span><br>";;
	   	   			}
	   	   			M('netuser',null)->bsave(array(
	   	   				'id'=>$user['id'],
	   	   				$netname.'_网体数据'=>$netstr,
	   	   				)
	   	   			);
				}else{
					M('netuser',null)->bsave(array(
	   	   				'id'=>$user['id'],
	   	   				$netname.'_网体数据'=>'',
	   	   				)
	   	   			);
				}
		    }
		    if($error == ''){
		    	M()->startTrans();
		   	   	//更新每个人的推荐人数信息
		   	   	foreach((array)$userdata as $user)
		   	   	{
		   	   		$data=array(
		   	   			'id'=>$user['id'],
		   	   			$netname.'_团队人数'  =>$user['num'],
		   	   			$netname.'_团队总人数'=>$user['allnum']
		   	   		);
		   	   		M('netuser',null)->bSave($data);
		   	   	}
		   	   	M('netuser',null)->bUpdate();
		   	   	M()->commit();
		   	   	unset($userdata);
		   	   	unset($user);
	   	   	}else{	   	   	
		   	   	unset($userdata);
		   	   	unset($user);
		   	   	return $error;
	   	   	}
	   	   	//网体数据出错
	   	   	$rs = array();
			$rs=M('会员')->alias('a')->join(" inner join netuser b on a.id=b.id and a.".$netname."_网体数据<>b.".$netname."_网体数据")->find();
			if(!empty($rs)){
				 $error .= "<span style='color:red;'>会员表中".$netname."网体数据与实际不符</span><br>";
			}
			$rs=M('会员')->alias('a')->join(" inner join netuser b on a.id=b.id and a.".$netname."_团队人数<>b.".$netname."_团队人数")->find();
			if(!empty($rs)){
				 $error .= "<span style='color:red;'>会员表中".$netname."团队人数与实际不符</span><br>";
			}
			$rs=M('会员')->alias('a')->join(" inner join netuser b on a.id=b.id and a.".$netname."_团队总人数<>b.".$netname."_团队总人数")->find();
			if(!empty($rs)){
				 $error .= "<span style='color:red;'>会员表中".$netname."团队总人数与实际不符</span><br>";
			}
			if($error != ''){
				 return $error;
			}else{
				return 1;
			}
	   	   	
		}
		
	}
?>