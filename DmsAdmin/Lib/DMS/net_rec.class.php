<?php
	class net_rec extends net
	{
		//最多允许有几个下级
		public $maxUser = 0;
		//超过下级的通知
		public $maxUserMsg ='';
		//空点是否算推荐
		public $nullRecer='true';
		public static $_cache=array();//奖金计算时的会员缓存记录的数组
		public static $_cachenum=0;//奖金计算时调用getups的次数
		public $_maxnum=10;//默认最多调用十次  启动缓存机制保存所有会员  不在查询数据库
		//如果开启此属性，则自动会将前台注册的net_rec的注册功能关闭.并以当前会员作为默认人
		public $setNowUser =false;	
		//计算推荐人数的条件
		public $sumWhere = '';
		//用户注册时的处理
		public function event_user_reg(&$user,$sale_reg)
		{	
			//更新网体数据与网体层数
			$this->set_index($user);
			//
			$this->set_groupnum($user,1,0);
		}
		//注册订单审核成功入口
		public function event_user_verify($user)
		{
			$this->set_recnum($user);
			$this->set_groupnum($user,1,1);
			//计算深度
			$this->set_Depth($user);
		}
		//如果有会员被删除
		public function event_userdelete($user)
		{
			$m_user=M('会员','dms_');
			$upuser=$m_user->where(array('编号'=>$user[$this->name.'_上级编号']))->find();
			//找到了要删除会员的推荐上级
			if($upuser)
			{
				//得到推荐人最新推荐数量
				$tjwhere="编号<>'".$user['编号']."' and ".$this->name."_上级编号='".$upuser['编号']."' and 状态='有效'";
				if($this->nullRecer=='false')$tjwhere.=" and 空点=0";
				$upuser[$this->name . '_推荐人数'] = $m_user->where($tjwhere)->count();
				$m_user->save($upuser);

				if($user['状态']=='有效')
				{
					//更新总数和已审核
					$this->set_groupnum($user,-1,2);
					$this->editTjnum($upuser['编号'],$user);
				}
				else
				{
					//只更新总数
					$this->set_groupnum($user,-1,0);
				}
			}
		}
		//查询上级并返回ID数组
		public function getupids($user,$minlayer=1,$maxlayer=0,$where=array(),$haveme = false)
		{	
			if($user[$this->name.'_网体数据']=='')
			return array();
			$ret=array_reverse(explode(',',$user[$this->name.'_网体数据']));
			
			if($minlayer<1)$minlayer=1;
			$ret=($maxlayer==0)?array_slice($ret,$minlayer-1):array_slice($ret,$minlayer-1,$maxlayer-$minlayer+1);
			if($haveme)
			array_unshift($ret,$user["id"]);
			return $ret;
		}
		public function getup($user,$fromprize=false)
		{
			if(!$user)
			{
				throw_exception('net_rec执行getup失败，参数无效');
			}
			//判定会员来源表
			if($fromprize){
				$m_user=M('user','dms_');
			}else{
				$m_user=M('会员','dms_');
			}
			$ret=$m_user->where(array('编号'=>$user[$this->name.'_上级编号']))->find();
			return $ret;
		}
		//查询上级,查询用户,数量(代数),条件,是否包括用户本身
		public function getupsByCache($row,$user,$minlayer=0,$maxlayer=0,$where='',$fromprize=false,$haveme = false)
		{
			static $thisrow='';
			static $cache=array();
			//判定会员来源表
			if($fromprize){
				$m_user=M('user','dms_');
			}else{
				$m_user=M('会员','dms_');
			}
			//加载缓存
			if($thisrow != $row)
			{
				$cache=$m_user->getField('id iskey,'.$row);
				$thisrow = $row;
			}
			if($user[$this->name.'_网体数据'] == ''){
				if($this->haveme)
				{
					return $cache[$user['编号']];
				}
				else
				{
					return array();
				}
			}
			else
			{
				$upids=$this->getupids($user,$minlayer,$maxlayer,'',$fromprize,$haveme);
				$ret = array();
				foreach($upids as $id)
				{
					$ret[] = $cache[$id];
				}
				return $ret;
			}
		}
		public function clearup($m_user,$clear=false){
			if($clear){
				if(self::$_cache){
					self::$_cachenum=0;
					self::$_cache=array();
				}
				return ;
			}
			self::$_cachenum+=1;
			$fieldsarr=$m_user->get_Property("fields");
			unset($fieldsarr['_autoinc']);
			unset($fieldsarr['_pk']);
			$fields=join(',',$fieldsarr);
			self::$_cache=$m_user->order($this->name.'_层数 DESC')->getField('id as iskey,'.$fields);
		}
		public function getups($user,$minlayer=0,$maxlayer=0,$where='',$fromprize=false,$haveme = false)
		{
			$m_user=M('会员');
			if(self::$_cachenum==$this->_maxnum){
				//获取全部会员 拉入缓存
				$this->clearup($m_user);
			}else if(self::$_cachenum<$this->_maxnum){
				self::$_cachenum+=1;
			}
			if($minlayer>0) $haveme=false;
			if($user[$this->name.'_网体数据'] == ''){
				if($haveme)
					$findids=$user['id'];
				else
					return array();
			}else{
				$findids = $user[$this->name.'_网体数据'];
				if($haveme)
					$findids.=(','.$user['id']);
			}
			$ret=array();
			//定义limit条件
			$limit = ($minlayer > 0)? $minlayer-1 : '0';
			//层数判断
			if($maxlayer > 0){
				//设置取的记录长度
				if($minlayer>0){
					$limitLen = $maxlayer-($minlayer-1);
				}else{
					if($haveme)
						$limitLen = $maxlayer+1;
					else
						$limitLen = $maxlayer;
				}
				$limit .=",".$limitLen;
			}else{
				$limit .=",9999999999";
			}
			//根据cache获取会员
			if(self::$_cache){
				//将$findids的会员找出并判断
				$findarr=explode(',',$findids);
				$findarr=array_reverse($findarr);
				$thislayer=1;
				foreach($findarr as $findid){
					$finduser=self::$_cache[$findid];
					if($finduser){
						//判断条件
						if(transform($where,$finduser) && $thislayer>=$minlayer && ($thislayer<=$maxlayer || $maxlayer<=0)){
							$ret[]=$finduser;
							$thislayer++;
						}
						unset($finduser);
					}
				}
				return $ret;
			}
			$where=delsign($where);
			//附加额外条件
			if($where!='')
				$where="($where)";
			else
				$where="1=1";
			$where.=" and id in ($findids)";
			
			//定义limit条件结束
			$ret   = $m_user->where($where)->order($this->name.'_层数 DESC')->limit($limit)->select();
			if($ret === false)
			{
				throw_exception('net_rec执行查下级点位失败,错误信息('.htmlentities($m_user->getDbError(),ENT_COMPAT ,'UTF-8').")");
			}
			if($ret === null)
			{
				$ret = array();
			}
			return $ret;
		}

		//查询下级
		public function getdown($user,$minlayer=0,$maxlayer=0,$where="",$fromprize=false,$haveme = false)
		{
			//判断会员是否是第一个会员 如果是第一个会员的话 应该是'id,%' 如果不是的话应该是'%,id,%' 
			$finwhere = $this->name . "_网体数据 like '".($user[$this->name.'_网体数据'] ? $user[$this->name.'_网体数据'].',' : '').$user['id'].",%' or ".$this->name."_上级编号='".$user['编号']."'";
			$where = ($where == '') ? " ($finwhere)":"($finwhere) and ($where)";
			if($minlayer > 0){
				$where.=" and ".$this->name."_层数"."-".$user[$this->name."_层数"]." >=".$minlayer;
			}
			if($maxlayer > 0){
				$where.=" and ".$this->name."_层数"."-".$user[$this->name."_层数"]." <=".$maxlayer;
			}
			//判断是奖金的查询还是会员的查询
			$m_user=M('会员');
			$ret   =$m_user->where($where)->order($this->name.'_层数 DESC')->select();
			if($ret === false)
			{
				throw_exception('net_rec执行查下级点位失败,sql信息('.htmlentities($m_user->where($where)->order($this->name.'_层数 DESC')->select(false),ENT_COMPAT ,'UTF-8').")");
			}
			return $ret;
		}
		//根据会员重置上级深度
		public function set_Depth(&$user)
		{
			$thislayer=$user[$this->name.'_层数'];
			$sql="update dms_会员 set {$this->name}_深度={$thislayer}-{$this->name}_层数 where {$this->name}_深度<{$thislayer}-{$this->name}_层数";
			if($user[$this->name."_网体数据"]!='')$sql.="  and id in (".$user[$this->name."_网体数据"].")";
			M()->execute($sql);
		}		
		//根据会员从新计算上级推荐人数以及其被推荐数
		public function set_recnum(&$user)
		{
			if($user[$this->name.'_上级编号'] == NULL||$user[$this->name.'_上级编号'] == ''||!transform($this->sumWhere,$user))
				return;
			if($user['空点']==1 && ($this->nullRecer=='false'))
			{
				return;
			}
			M('会员','dms_')->where(array('编号'=>$user[$this->name.'_上级编号']))->setInc($this->name."_推荐人数",1);
			$uptuijian=M('会员','dms_')->where(array('编号'=>$user[$this->name.'_上级编号']))->getField($this->name."_推荐人数");
			M('会员','dms_')->where(array('编号'=>$user['编号']))->setField($this->name."_被推荐数",$uptuijian);
			$user[$this->name.'_被推荐数']=$uptuijian;
		}
		//设置团队人数$verify=0为未审核人数,1为已审核人数,2为两类人数
		public function set_groupnum($user,$num,$verify)
		{
			//如果会员网体数据为空（表示原始点或者目前处于快速插入模式，不执行以下操作）
			if($user[$this->name.'_网体数据'] == '' || defined('BULK_INSERT'))
			return;
			$ids=$user[$this->name.'_网体数据'];
			if($verify==1||$verify==2)
			{
				$sqlstr="update dms_会员 set ".$this->name."_团队人数  =".$this->name."_团队人数  + ".$num." where id in(".$ids.")";
				M()->execute($sqlstr);
			}
			if($verify==0||$verify==2)
			{
				$sqlstr="update dms_会员 set ".$this->name."_团队总人数=".$this->name."_团队总人数 + ".$num." where id in(".$ids.")";
				M()->execute($sqlstr);
			}
		}
		//创建索引数据
		public function set_index(&$user)
		{
			$model=M('会员','dms_');
			if($user[$this->name.'_上级编号']=='')
			{
				$user[$this->name.'_层数'] = 1;
				$user[$this->name.'_网体数据'] ='';
			}
			else
			{
				$upuser = M("会员")->where(array("编号"=>$user[$this->name.'_上级编号']))->field("id,".$this->name.'_网体数据,'.$this->name.'_层数')->find();
				if($upuser[$this->name.'_网体数据']=='')
				{
					$user[$this->name.'_网体数据'] =$upuser['id'];
				}else{
					$user[$this->name.'_网体数据'] = $upuser[$this->name.'_网体数据'] .','.$upuser['id'];
				}
				$user[$this->name.'_层数']=$upuser[$this->name.'_层数']+1;
			}
			$model->save($user);
		}
		//此人是否已经推荐满
		public function isMaxuser($username)
		{
			$tjcount=M("会员",'dms_')->lock(true)->where(array($this->name."_上级编号"=>$username))->count();
			if($tjcount>=$this->maxUser){
			return false;
			}
		}
		//判断安置人是否在推荐人的网体下
        public function recLock($netname,$recname){
			$recnet=M('会员','dms_')->lock(true)->where(array("编号"=>$netname))->getField($this->name."_网体数据");
			$recid=M('会员','dms_')->lock(true)->where(array("编号"=>$recname))->getField("id");
			if(!$recnet) return true;
			$recarr=explode(",",$recnet);
			if(in_array($recid,$recarr) || $netname==$recname){
				return true;
			}else{
				return false;
			}
		}
		//修复网络体系数据.按照一个网体中只有一个原始点来看待
		public function renovate()
		{
			//得到会员model
			$m_user  = M('会员');
			//找到无上级会员
			M()->execute("update dms_会员 set ".$this->name."_上级编号='' where ".$this->name."_上级编号 is null");
			$topuser = $m_user->where(array($this->name.'_上级编号'=>''))->select();
			if(count($topuser) != 1)
			{
				die('发现两个原始点');
			}
			$topuser = $m_user->where(array($this->name.'_上级编号'=>''))->find();
			//设置索引信息
			$this->set_index($topuser);
			$handle[]=$topuser['编号'];
			while($name = array_shift($handle))
			{
				$downusers = $m_user->where(array($this->name.'_上级编号'=>$name))->select();
				foreach($downusers as $downuser)
				{	
					$this->set_index($downuser);
					$handle[]=$downuser['编号'];
					$this->set_index();
				}
			}
		}
		//根据审核日期，修改下面人的被推荐数--移网+删除正式
		public function editTjnum($upuser,$user=''){
			$where=array($this->name."_上级编号"=>$upuser,"状态"=>'有效');
			if($user!=''){
				$where['编号']=array("neq",$user['编号']);
			}
			$Downusers=M('会员')->where($where)->order("审核日期 asc")->field('id')->select();
			if($Downusers){
				$px=1;
				foreach($Downusers as $duser){
					M('会员')->where(array('id'=>$duser['id']))->setField($this->name."_被推荐数",$px);
					$px++;
				}
			}
		}
		//移动网体
		public function move($user,$newup,$region)
		{
			//如果新要求与目前网络体系一致则直接返回
			if($user[$this->name.'_上级编号'] == $newup['编号'])
			{
				return true;
			}
			//不能为自己判断
			if($user['编号']==$newup['编号'])
			{
				return $this->name."网新上级编号不能为自己";
			}
			//对新上级是否存在做判断
			if(!$newup)
			{
				return $this->name."移动网络时,新上级信息不存在";
			}
			//新上级不能在自己网络体系之下
			if(strpos($newup[$this->name.'_网体数据'],trim($user[$this->name.'_网体数据'].','.$user['id'],',')) !== false)
			{
				return "新上级不能在其网络体系之下";
			}
			//判断推荐人数
			if($this->maxUser>0){
				$tjcount=M('会员')->where(array($this->name."_上级编号"=>$newup['编号']))->count();
				if($tjcount>=$this->maxUser){
					return "新的".$this->name."上级已".$this->name."满".$this->maxUser."人";
				}
			}
			//开始更新团队人数
			$oldids = explode(',',$user[$this->name.'_网体数据']);
			$newids = explode(',',$newup[$this->name.'_网体数据']);
			//因为团队人数变更.也会对新上级本身产生影响,所以需要增加
			$newids[] = $newup['id'];
			$allnum     = $user[$this->name.'_团队总人数'] + 1;
			$comfrimnum = $user[$this->name.'_团队人数'] + ( $user['状态']=='有效' ? 1 : 0 );
			//减团队人数
			M('会员')->where(array('id'=>array('in',$oldids)))->setDec($this->name.'_团队总人数',$allnum);
			M('会员')->where(array('id'=>array('in',$oldids)))->setDec($this->name.'_团队人数'  ,$comfrimnum);
			//增加团队人数
			M('会员')->where(array('id'=>array('in',$newids)))->setInc($this->name.'_团队总人数',$allnum);
			M('会员')->where(array('id'=>array('in',$newids)))->setInc($this->name.'_团队人数'  ,$comfrimnum);
			if($user['状态']=='有效'){
				if(!($this->nullRecer=='false' && $user['空点']==1))M('会员')->where(array('编号'=>$newup['编号']))->setInc($this->name."_推荐人数",1);
				if(!($this->nullRecer=='false' && $user['空点']==1))M('会员')->where(array('编号'=>$user[$this->name.'_上级编号']))->setDec($this->name."_推荐人数",1);
			}
			//=======================================================================
			//对移网会员本身的上级信息更新
			M('会员')->where(array("编号"=>$user['编号']))->save(array( $this->name . '_上级编号' => $newup['编号']));
			//生成新网体数据
			$newnet = trim($newup[$this->name . '_网体数据'].','.$newup['id'],',');
			//得到新老层数差
			$layerdiff = $newup[$this->name.'_层数']+1 - $user[$this->name.'_层数'];
			//对下级和自身层数进行更新
			$where = "(".$this->name . "_网体数据 like '".($user[$this->name.'_网体数据'] ? $user[$this->name.'_网体数据'].',' : '').$user['id'].",%' or ".$this->name."_上级编号='".$user['编号']."')";
	        $where .= " or (编号='{$user['编号']}')";
			M('会员')->where($where)->setInc($this->name.'_层数',$layerdiff);
			//对老网体数据进行更新
			M()->execute("update `dms_会员` set ".$this->name."_网体数据=replace(".$this->name."_网体数据,'".$user[$this->name.'_网体数据'].','.$user['id'].",','".$newnet.",".$user['id'].",')");
			M()->execute("update `dms_会员` set ".$this->name."_网体数据='".$newnet.",".$user['id']."' where ".$this->name."_上级编号='".$user['编号']."'");
			M()->execute("update `dms_会员` set ".$this->name."_网体数据='".$newnet."' where id=" . $user['id']);
			//触发网体移动事件
			$oldupuser=$user[$this->name.'_上级编号'];
			$user[$this->name."_网体数据"] = $newnet;
			$user[$this->name.'_上级编号'] = $newup['编号'];
			$user[$this->name.'_层数'] += $layerdiff;
			foreach(X("fun_treenum") as $funrec){
				$funrec->event_netmove($this,$user);
			}
			//增加会员操作日志
			$data = array();
			$datalog['user_id']   = $user['id'];
			$datalog['user_name'] = $user['姓名'];
			$datalog['user_bh']   = $user['编号'];
			$datalog['admin_id']  = $_SESSION[ C('RBAC_ADMIN_AUTH_KEY')];
			$datalog['ip']        = get_client_ip();
			$datalog['content']   = '移动'.$user['编号'].$this->name.'网从'.$oldupuser.'到'.$newup['编号'];
			$datalog['create_time']=time();			
			import("ORG.Net.IpLocation");
			$IpLocation				= new IpLocation("qqwry.dat");
			$loc					= $IpLocation->getlocation();
			$country				= mb_convert_encoding ($loc['country'] , 'UTF-8','GBK' );
			$area					= mb_convert_encoding ($loc['area'] , 'UTF-8','GBK' );
			$datalog['address']		= $country.$area;
			M('log_user')->add($datalog);
			//全剧终
			return true;
		}
		//修复网体数据
		public function repair()
		{
	   	    ini_set('memory_limit','5000M');
	   	    set_time_limit(1000);
	   	    //对编号可能存在的大小写不一致的情况做修正
	   	    M()->execute("update dms_会员 a,dms_会员 b set a.".$this->name."_上级编号=b.编号 where a.".$this->name."_上级编号=b.编号");
	   	    //清空现有网体数据信息
	   	    M()->execute("update dms_会员 set {$this->name}_网体数据=''");
	   	    M()->execute("update dms_会员 set {$this->name}_层数=1 where {$this->name}_上级编号=''");
	   	    //取得需要用的信息表
	   	    $userdata = M('会员')->getField("编号,{$this->name}_上级编号 上级编号,id,状态,0 num,0 allnum");
	   	    //取得要处理会员的信息表
	   	    $upusers  = M('会员')->getField('id,编号');
	   	    //更新网体数据临时SQL
	   	    $netsql   = '';
	   	    //更新层数临时SQL
	   	    $layersql = '';
	   	    foreach($upusers as $id=>$name)
	   	    {
	   	    	$user = $userdata[$name];
	   	   		if($user['上级编号'] != '')
	   	   		{
	   	   			//定义网体数据
	   	   			$netstr = '';
	   	   			//定义层数
	   	   			$layer  = 1;
	   	   			
	   	   			//设置自己的上级
	   	   			$thisup = $user['上级编号'];
	   	   			//对上级进行链性表遍历,同时要防止死循环(存在互为上级的情况)
	   	   			
	   	   			while($thisup!='' && $layer<1000)
	   	   			{
	   	   				$layer++;
	   	   				//合成部分网体数据
	   	   				$netstr=$userdata[$thisup]['id'].','.$netstr;
	   	   				//增加团队总人数
	   	   				$userdata[$thisup]['allnum']++;
	   	   				//增加有效人数
	   	   				if($user['状态']=='有效')$userdata[$thisup]['num']++;
	   	   				$thisup = $userdata[$thisup]['上级编号'];
	   	   			}
	   	   			$netstr = trim($netstr,',');
	   	   			//判断达到1000层,认为出现死循环情况
	   	   			if($layer >= 1000)
	   	   			{
						throw_exception("出现了互为上级的情况,".$netstr);
	   	   			}
	   	   			//对网体数据的更新
	   	   			$netsql   .= ' WHEN '.$user['id']." THEN '".$netstr."'";
	   	   			//对层数的更新
	   	   			$layersql .= ' WHEN '.$user['id']." THEN '".$layer."'";
	   	   			M('会员')->bsave(array(
	   	   				'id'=>$user['id'],
	   	   				$this->name.'_网体数据'=>$netstr,
	   	   				$this->name.'_层数'    =>$layer,
	   	   				)
	   	   			);
				}
		    }
	   	   	//更新每个人的推荐人数信息
	   	   	foreach($userdata as $user)
	   	   	{
	   	   		$data=array(
	   	   			'id'=>$user['id'],
	   	   			$this->name.'_团队人数'  =>$user['num'],
	   	   			$this->name.'_团队总人数'=>$user['allnum']
	   	   		);
	   	   		M('会员')->bSave($data);
	   	   	}
	   	   	M('会员')->bUpdate();
	   	   	//更新推荐人数
	   	   	M()->execute('update dms_会员 a left join (select '.$this->name.'_上级编号 编号,count(*) num from dms_会员 where 状态=\'有效\' group by '.$this->name.'_上级编号) b on a.编号=b.编号 set a.'.$this->name.'_推荐人数 = ifnull(b.num,0)');
	   	   	//更新被推荐数
	   	   	M()->execute('update dms_会员 set '.$this->name.'_被推荐数=0');
	   	   	M()->execute("set @pdept:='';");
	   	   	M()->execute("set @rank:=0;");
	   	   	M()->execute('update dms_会员 a  join (select * from (SELECT id,if(@pdept='.$this->name.'_上级编号,@rank:=@rank+1,@rank:=1) as rank,@pdept:='.$this->name.'_上级编号  FROM `dms_会员`  where 状态=\'有效\' order by '.$this->name.'_上级编号 asc,审核日期 asc,id asc) c) b on a.id=b.id set a.'.$this->name.'_被推荐数=b.rank;');
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_会员 set {$this->name}_上级编号='{$newbh}' where {$this->name}_上级编号='{$oldbh}'");
		}
	}
?>