<?php
	class net extends stru
	{
		//非正式会员是否可以作为上级
		public $nullUp = false;
		//必须存在上级
		public $mustUp = true;
		public $shopNetDisp=true;   //店铺是否显示网络图
		public $shopListDisp=true;  //店铺是否显示列表
		public $userNetDisp=true;
		public $userListDisp=true;
		public $treeDisp=array();
		public $adminNetLayer = 4;
		public $userNetLayer = 4;
		public $userLookLayer = 0;
		public $shopLookLayer = 0;//店铺前台显示层数
		public $shopNetLayer=4;  //店铺的前台显示层数
		
		public $userNameDisp = true;//用户姓名
		public $userAnotherNameDisp = true;//用户别名
		public $userauto= true;
		//判断某个注册订单是否允许这个网络体系
		public function useBySale(sale_reg $sale)
		{
			if($sale->netName == '')
				return false;
			if($sale->netName == 'all')
				return true;
			foreach(explode(",",$sale->netName) as $name)
			{
				if($name == $this->name)
					return true;
			}
			return false;
		}
		public function lvHave($userid)
		{
			$where['编号']=$userid;
            $where[$this->name."_层数"]=array("gt",0);
			$rs=M('会员','dms_')->lock(true)->where($where)->find();
			if($rs)
			{
				$_POST['net_'.$this->getPos()] = $rs['编号'];
				return true;
			}else{
			    return false;
			}
		}
		//判断特定上级编号是否不符合where的条件,用于net节点中的_lock标签的判定
		public function ifLock($userid,$where)
		{
			if($userid=='')
			{
				return true;	
			}
			$upuser=M('会员','dms_')->lock(true)->where(array('编号'=>$userid))->find();
			if(!$upuser)
			{
				return true;
			}
			else
			{
				return !transform($where,$upuser);
			}
		}
	}
?>