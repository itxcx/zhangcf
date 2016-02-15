<?php
	class item extends stru
	{
		//注册时是否显示
		public $regDisp = true;
		//非正式会员是否可以作为上级
		public $nullUp = true;
		//必须存在上级
		public $mustUp = true;
		//计算推荐人数的周期,0为生效立即计算,1为订单结算周期期间计算
		public $sumMode = 0;
		//计算推荐人数的条件
		public $sumWhere = '';
	}
?>