<?php
	class net_place2 extends net
	{
       public $fromNet="";
       //设置自动落点的模式,''为不使用
       public $autoMode="";
       //PV值增加时的缓存
       public $_Cacheself = array();
       //业绩功能开关
	   public $pvFun = true;
		//安置位置列表
	   public $locationList = array();
	   //当自己有已经有点位时，进行小公排处理时的额外条件
	   public $inMeWhere = "";
	   //是否属于进推荐小公排,如果是则需要填写$inrec
		public $inrec='';
		//计算结束时更新结果
		public function event_calover($tle,$caltime,$type)
		{
	//		M($this->name,'dms_')->where(array('计算'=>0))->save(array('计算'=>1));
		}
		public function event_sysclear()
		{
			M()->execute("TRUNCATE TABLE dms_".strtolower($this->name));
		}
		public function getNullRegion($name)
		{
			if($name=="")
			{
				return '';
			}
			$user=M('会员','dms_')->where(array('编号'=>$name))->find();
			foreach($this->getRegion() as $Region)
			{
				if($user[$this->name.'_'.$Region['name'].'区']=='')
				{
					return $Region['name'];
				}
			}
			return '';
		}
		//判断该会员是否属于该网络
		public function lvHave($userid)
		{
			$where['编号']=$userid;
            $where[$this->name."_层数"]=array("gt",0);
			$rs=M('会员','dms_')->lock(true)->where($where)->find();
			if($rs)
			{
				return true;
			}else{
			    return false;
			}
		}
		//取得位置设置数据
		public function getRegion()
		{
			return $this->getcon("region",array("name"=>"","regDisp"=>"true"),true);
		}
		public function getBranch()
		{
			$ret=array();
			foreach($this->getcon("region",array("name"=>"")) as $Region)
			{
				$ret[] = $Region["name"];
			}
			return $ret;
		}
		/*
		* 向下查找最近的一个空位置
		* user : 查找的起始用户
		*/
		public function findEmptyPostionByChilds($node)
		{

           $userNodeName = 'dms_' .$this->name;
			$Model			= M();
			//获取分支名称
			$locationList	= array();
			foreach($this->getcon("region",array("name"=>"")) as $val)
			{
				$locationList[] = $val['name'];
			}
			//echo "查找:".$user['编号'].'的下级空位<br />';
			$locationCount		= count($locationList);

			/****** 处理管理区位 ******/

			foreach( $locationList as $key=>$location )
			{
				$this->locationList[ $location ] = $key;
			}

			/*******************************************/
			$sql				= "select 层数 as 所在层数 from {$userNodeName} where 层数>=1 and (";
			$sql2				= '';
			foreach( $locationList as $key=>$location )
			{
				if( $key >=1 )
				{
					$sql .= " or ";
					$sql2 .= " or ";
				}
				$sql		.= "find_in_set('{$node['id']}-{$location}',网体数据 )";
				$sql2		.= "{$location}区=''";
			}

			$sql		.= " or id={$node['id']})";
			$sql		.= " and ({$sql2})";
			$sql		.= " order by 层数 asc limit 1 for update";

			//获取空位置所在的层数
			$result		= $Model->query($sql);
			$layer		= intval($result[0]['所在层数']);

			//获取找出该层的所有空位
			$field_str	= "id,编号,网体数据,层数";
			foreach( $locationList as $key=>$location )
			{
				$field_str	.= ",{$location}区";
			}
			$sql		= "select {$field_str} from {$userNodeName} where 层数={$layer} and (";

			foreach( $locationList as $key=>$location )
			{
				if( $key >=1 ) $sql .= " or ";
				$sql		.= "find_in_set( '{$node['id']}-{$location}',网体数据 )";
			}
			$sql		.= " or id={$node['id']} ) and ( ";

			foreach( $locationList as $key=>$location )
			{
				if( $key >=1 ) $sql .= " or ";
				$sql		.= "{$location}区=''";
			}
			$sql		.= ')  for update';

			//这种查询，首先要排除 空位置直接在下属子节点的情况
			$result		= $Model->query($sql);
			//空位排序, 按从左到右的顺序
			$key_order		= array();
			$result_order	= array();
			foreach($result as $key=>$vo)
			{
				$net = $vo["网体数据"] ;
				$net = preg_replace( "/[0-9]+-/",'',$net);
				$net = str_replace( ',','',$net);
				$key_sign					= str_replace( $locationList , $this->locationList , $net);
				$key_order[]				= $key_sign;
				$result_order[ $key_sign ]	= $vo;
				if( $vo['id'] == $node['id']  )
				{
					unset($result_order);
					$result_order[ $key_sign ]	= $vo;
					break;
				}
			}
			unset($result);
			//以order做为key重新创建数组
			uksort($result_order,'strnatcmp');

			$result_order		= current($result_order);
			$result=array();
			$result['up'] = $result_order['id'];
			//确定区位
			foreach( $locationList as $key=>$location )
			{
				if( $result_order["{$location}区"] == 0 )
				{
					$result['qu']	= $location;
					break;
				}
			}
			if($result_order['网体数据']!='')
			{
				$result['netdata']	= $result_order['网体数据'].','.$result_order['id'].'-'.$result['qu'];
			}
			else
			{
				$result['netdata']	= $result_order['id'] . '-' . $result['qu'];
			}
			$result['ceng']	= $result_order['层数'] + 1;
			return $result;
		}

		public function event_valadd(&$user,$val,$option)
		{
			if($val>50)
			{
				throw_exception("net_place2网体接收到大量进点需求,".$user['编号'].'会员要进'.$val.'个点位，如果此情况是不正确的。请修改addval中的from属性写死为要进点的数量');
			}
			for($i=1;$i<=$val;$i++)
			{
				$this->add($user);
			}
		}
		public function add($user)
		{
			$model  = M($this->name,'dms_');
			$menode = $model->where(array('编号'=>$user['编号']))->order('序号 desc')->find();
			//新序号
			$newxh  = ($menode) ? $menode['序号']+1 : 1;
			//如果没有找到任何人则需要进入原始点
			if(!$model->find())
			{
				$model->add(array('序号'=>1,'userid'=>$user['id'],'编号'=>$user['编号'],'上级id'=>0,'层数'=>1,'位置'=>'','网体数据'=>'','进网日期'=>systemTime()));
				return;
			}
			//
			if($this->inrec != '')
			{
				
				//第一个点
				if($newxh == 1)
				{
					$recfind = false;
					$net = X('net_rec@'.$this->inrec);
					if($net === NULL)
					{
						throw_exception($this->name."计算时网络体系获取失败,请检查其inrec设置是否正确");
					}
					$up = $net->getups($user,$minlayer=0,$maxlayer=0,"exists(select * from dms_{$this->name} where dms_{$this->name}.userid=dms_会员.id)");
					//找到推荐人
					if($up)
					{
						$upnode=$up[0];
						$upnode = $model->where(array('userid'=>$upnode['id']))->order('序号 asc')->find();
					}
					else
					{
						//从原始点开始
						$upnode=$model->where(array('层数'=>1))->find();
					}
				}
				if($newxh > 1)
				{
					$where = 'userid='.$user['id'];
					if($this->inMeWhere != '')
					{
						$where .= ' and ('.delsign($this->inMeWhere).')';
						$upnode = $model->where($where)->order('序号 asc')->find();
					}
					if(!$upnode)
					{
						$upnode = $model->where(array('userid'=>$user['id']))->order('序号 asc')->find();
					}
				}
				//根据$upnode找公排
				$setdata=$this->findEmptyPostionByChilds($upnode);
				$newid=$model->add(array('序号'=>$newxh,'userid'=>$user['id'],'编号'=>$user['编号'],'上级id'=>$setdata['up'],'层数'=>$setdata['ceng'],'位置'=>$setdata['qu'],'网体数据'=>$setdata['netdata']));
				$model->where(array('id'=>$setdata['up']))->save(array($setdata['qu'].'区'=>$newid));
			}
			else
			{
				$upnode=$model->where(array('层数'=>1))->find();
				$setdata=$this->findEmptyPostionByChilds($upnode);
				$newid=$model->add(array('序号'=>$newxh,'userid'=>$user['id'],'编号'=>$user['编号'],'上级id'=>$setdata['up'],'层数'=>$setdata['ceng'],'进网日期'=>systemTime(),'位置'=>$setdata['qu'],'网体数据'=>$setdata['netdata']));
				$model->where(array('id'=>$setdata['up']))->save(array($setdata['qu'].'区'=>$newid));
				//大公排算法
			}
		}

		//查询上级,查询用户,数量(代数),条件,是否包括用户本身
		public function getups($user,$minlayer=0,$maxlayer=0,$where='',$haveme = false)
		{
			//如果取的层数大于0，则不可能存在haveme
			if($minlayer>0) $haveme=false;
			if($user['网体数据'] == '' && !$haveme)
			return null;
			$findids=$user['网体数据'];
			$findids= preg_replace( "/-[^,]*/",'',$findids);
			if($haveme)
				$findids = ( $findids=='') ? $user['id'] : $findids.",".$user['id'];
			if($where == '')
				$where = "id in ($findids)";
			else
				$where = "id in ($findids) and ($where)";

			$limit = ($minlayer > 0)? $minlayer-1 : '0';
			
			if($maxlayer > 0){
				//设置取的记录长度
				if($minlayer>0)
					$limitLen = $maxlayer-($minlayer-1);
				else
				{
					if($haveme)
						$limitLen = $maxlayer+1;
					else
						$limitLen = $maxlayer;
				}
				$limit .=",".$limitLen;
			}else{
				$limit .=",9999999999";
			}
			$m_user=M($this->name);
			$ret = $m_user->where($where)->order('层数 DESC')->limit($limit)->select();
			if($ret === false)
			{
				throw_exception('net_place执行查下级点位失败,错误信息('.htmlentities($m_user->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			return $ret;
		}
		//查询下级
		public function getdown($user,$minlayer=0,$maxlayer=0,$where='',$haveme = false)
		{
			if($where == ''){$where = "1=1";}
			$sql="";
			foreach( $this->getBranch() as $key=>$Branch)
			{
				if( $key >=1 ) $sql .= " or ";
				$sql		.= "find_in_set( '{$user['id']}-{$Branch}',网体数据 )";
			}
			$where.=" and ($sql)";
			if($minlayer > 0)
				$where.=" and 层数-".$user["层数"]." >=".$minlayer;
			if($maxlayer > 0)
				$where.=" and 层数-".$user["层数"]." <=".$maxlayer;
			$m_user=M($this->name,'dms_');
			$ret=$m_user->where($where)->order('层数 ASC')->select();
			if($ret === false)
			{
				throw_exception('net_place执行查下级点位失败,sql信息('.htmlentities($m_user->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			return $ret;
		}
		
		//判断指定区是否存在人,如果指定
		public function nothaveRegion($username,$regionName)
		{
			$user=M('会员','dms_')->lock(true)->where(array("编号"=>$username))->find();
			if(!$user[$this->name."_".$regionName."区"])
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		//判断这个人所有区是否已经排满
		public function haveAllRegion($userid)
		{
            $user=M('会员','dms_')->lock(true)->where(array('编号'=>$userid))->find();
			$ableregion=array();
			foreach($this->getRegion() as $region)
			{
				if($user[$this->name.'_'.$region['name'].'区'] =='')
				{
					$ableregion[]=$region['name'];
				}
			}
			if(empty($ableregion))
			{
			 return false;
			}else{
			return true;
			}
		}
		//最左区判定
		public function isInLeft($placename,$recname,$regionName)
		{
			$rec=M('会员','dms_')->lock(true)->where("编号='".$recname."'")->find();
			$recid=$rec['id'];
			$Branch=$this->getBranch();
			$recnum=$rec[$this->fromNet . "_推荐人数"];
			if($recnum >0){
			  return true;
			}
			if($recname==$placename){
			  $recplace=M('会员','dms_')->lock(true)->where($this->name."_上级编号='".$recname."'")->find();
			  if($recpalce){
				  return false;
			  }elseif($regionName != $Branch[0]){
			      return false;
			  }else{
				  return true;
				}
			}
			$placenet=M('会员','dms_')->lock(true)->where("编号='".$placename."'")->getField($this->name.'_网体数据');
			if($placenet=="")
			{
				return true;
			}else{
				$branch= $this->getBranch();
				$first=$recid."-".$branch[0];
				$netarr=explode(",",$placenet);
				if(in_array($first,$netarr)){
				    return true;
				}else{
					return false;
					}
			}
		}
		//会员删除事件
		public function event_userdelete($user)
		{
			/*
			if($userdata[$this->name."_上级id"]!='' && $userdata[$this->name."_位置"]!='')
			{
				M($this->parent()->name,'dms_')->where(array("编号"=>$userdata[$this->name."_上级编号"]))->save(array($this->name."_".$userdata[$this->name."_位置"]."区"=>''));
				if($userdata['状态']=='有效')
				{
					//更新总数和已审核
					$this->set_groupnum($userdata,-1,2);
				}
				else
				{
					//只更新总数
					$this->set_groupnum($userdata,-1,0);
				}
			}
			*/
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_{$this->name} set 编号='{$newbh}' where 编号='{$oldbh}'");
		}
	}
?>