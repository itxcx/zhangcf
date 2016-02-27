<?php
    //此文件为一个奖金模块的基本模型
    //此奖金类需要在sqlxml目录中存在一个prize_demo.xml文件描述此奖金的字段结构信息
    
    /*标准内容如下
<table name='{X('user')->name}'>
<field name='{$this->name}'     type="numeric(10,2)" default="0"/>
<field name='{$this->name}本日' type="numeric(10,2)" default="0"/>
<field name='{$this->name}本周' type="numeric(10,2)" default="0"/>
<field name='{$this->name}本月' type="numeric(10,2)" default="0"/>
<field name='{$this->name}累计' type="numeric(10,2)" default="0"/>
<field name='{$this->name}比例' type="numeric(10,2)" default="100"/>
</table>
<table name='{X('user')->name}_{$this->parent('tle')->name}'>
<field name='{$this->name}'     type="numeric(10,2)" default="0"/>
<field name='{$this->name}本月' type="numeric(10,2)" default="0"/>
<field name='{$this->name}累计' type="numeric(10,2)" default="0"/>
</table>
    */
	class prize_demo extends prize
	{
		//定义本奖金默认的奖金发放行为，1为发放2为扣除
		public $prizeMode=1;
		
		//秒结算入口
		function scal()
		{
			$this->cal();
		}
		//日结算执行
		function cal($sale)
		{
			//用于判定该奖金是否应该执行,如果不该执行则为false
			if(!$this->ifrun()) return;
			
			
			//您可能需要的案例-------------------------------------------------------------------
			
			//当前结算时间
			$caltime = $this->_caltime;
			
			//当前会员表的model
			$m_user = M('会员')->where()->select();
			
			//取得符合条件的订单记录，如果设置了rowname等于1，则会取到符合日期范围和指定条件的订单记录
			//prize基类会根据tlemode自动处理订单的时间
			$sales = $this->getsale($this->where,"*");

			//取得奖金中的配置,并循环
			$cons=$this->getcon('con',array('minlv'=>0,'maxlv'=>0,'val'=>0,'where'=>''));
			foreach($cons as $con)
			{
    			//动态判定
    			//假设奖金计算时,存在产生奖金的人$upuser,产生业绩的人$user,订单$sale
                $user  =array();//产生业绩的会员
                $upuser=array();//产生奖金的会员
                $sale  =array();//订单信息
    			$wheredata=array('U'=>&$user,'M'=>&$upuser,'S'=>&$sale);
    			transform($con['where'],array(),$wheredata);
    			//假设奖金计算时,存在产生奖金的人$upuser,产生业绩的人$user,订单$sale
    			
    			//根据设定的比例计算实际奖金
    			//参数:getnum(参考值,比例,小数位,总比例)
    			//详情可以查阅function.php中的getnum方法
    			$prizenum=getnum($m_user['t_recnum'],$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
    			
    			//对某一个会员增加当前奖金
    			//使用单独方法封装的目的是减少数据库操作提高效率,使用缓存临时存储所有会员的收入信息.
    			//参数分别为(产生奖金的会员,奖金,产生业绩的会员,计算构成备注,层数)
                $memo='奖金注释';
                $thislayer=1;   //当前计算的层数
    			$this->addprize($upuser,$prizenum,$user,$memo,$thislayer);
    			}
			//对addprize方法进行的奖金进行批量更新入库
			$this->prizeUpdate();
		}
	}
?>