<?php
/*
	层碰奖模块只能用于net_place安置网模块,必须要填写netName
	要实现自己下边某层N个区都有人,才能产生奖金.
	本奖金只会使用订单作为产生奖金的触发,所以不存在rowfrom属性
	rowname属性指会员表中的一个字段.用于百分比的计算
	层奖分两种情况
	一种是某一层排满产生
	    这种情况下只能发放固定额度,案例,第一层排满发放500
	    <prize_layer name='xxxx' full='1'>
	    	<_con minlayer='1' maxlayer='1' val='500'/>
	    </prize_layer>
	    在这种奖金算法下rowfrom不起到作用,val也不能使用带有百分比,如10%
	第二种是某一层每个区有至少一个有效会员
	    <prize_layer name='xxxx' full='0'>
	    	<_con minlayer='2' maxlayer='2' val='500'/>
	    </prize_layer>
	    如果是2条线网络,第二层也只需要左右各自一个人即可,而不需要4个人
	    如果要是按照百分比来拿,如10%
	    计算方式默认如下
	    首先根据rowname排序,找每个区最大的人.
	    所有区中在找业绩最小的人,根据业绩特定比例来计算
	    图例
	                   A
	             B             C
	       D        E      F       G
	    假设rowname为'个人业绩'
	    D个人业绩=1000
	    E个人业绩=2000
	    F个人业绩=3000
	    G个人业绩=4000
	    那么首先取得DE之中业绩最大的,即2000,在取得FG之间业绩最大的,如4000
	    在2000和4000之间取得最小值,即2000,在比10%,即200元奖金
	    如果要是根据本层最早注册的会员两边的业绩来做计算,则设置成
	    <prize_layer orderName='审核日期 asc,id asc'>
	    	
	    </prize_layer>
		在默认情况下,如果某一层产生了奖金,则这一层不在参与计算.
		但是如果在下级报升级订单.可以根据新条件.差生差额部分的奖金,则rePrize要设置为true
		可以设置拿奖金的人的级别条件限制
		<_con minlayer='1' maxlayer='2' minlv='1' maxlv='2' val='500'/>
		如果要求左右区的最小级别(计算方式和rowname一样,只不过是参考的lvname级别字段的值),是特定级别
		如两条线至少都有一个2级会员能拿500,两条线各自都有一个3级会员能拿800,可以使用{minlv}关键字
		<_con val='500' where='{minlv}=2' minlayer='2' maxlayer='2'/>
		<_con val='800' where='{minlv}=3' minlayer='2' maxlayer='2'/>
*/
class prize_layer extends prize
{
	//产生类型
	public $prizeMode=1;
	//来源表条件
	public $where = '';
	//来源表达式
	public $rowName = "";
	//是否排满
	public $full  = 0;
	//计算过的层是否重复计算
	public $rePrize =false;
	//排序字段
	public $orderName="";
	public $netName ='';
	public $conFilter=array('con'=>array("minLayer","maxLayer","minlv","maxlv","val","where"));
	//会员被删除触发的事件
	public function event_userdelete($user)
	{
		M($this->name)->where(array("编号"=>$user["编号"]))->delete();
	}
	
	//系统数据清空事件
	public function event_sysclear()
	{
		M()->execute("TRUNCATE TABLE dms_".strtolower($this->name));
	}
    function event_rollback($time)
    {
        //删除层碰日志
        M($this->name)->where(array('时间'=>array('egt',$time)))->delete();
    }
	//秒结算驱动
	function scal($sale)
	{
		if($this->where=="")
		{
			$this->where="id=".$sale["id"];
			$this->cal();
			$this->where="";
		}
		else
		{
			$otherwhere=$this->where;
			$this->where='('.$otherwhere.") and id=".$sale["id"];
			$this->cal();
			$this->where=$otherwhere;
		}
	}
	function cal()
	{
		if(!$this->ifrun()) return;
		$net = X('*@'.$this->netName);
		if($net === NULL)
		{
			throw_exception($this->name."计算时网络体系获取失败,请检查其netName设置是否正确");
		}
		$num_ratio = false;
		$maxlayer = 0;
		$minlayer = 0;
		$cons = $this->getcon('con',array("minlayer"=>-1,"maxlayer"=>-1,"minlv"=>1,"maxlv"=>1,"val"=>"","where"=>""));
		foreach($cons as $con)
		{
			//用于优化,如果VAL全部带有%,而rowname的结果为0,则可以忽略当次计算
			if(substr($con['val'],-1,1) != '%')
			$num_ratio=true;
			//用于优化,得到最大会获取多少层的会员
			if($maxlayer <= $con['maxlayer'] || $con['maxlayer']==-1)
				$maxlayer = $con['maxlayer'];
			if($minlayer >= $con['minlayer'])
				$minlayer = $con['minlayer'];
		}
		//从订单获取奖金来源
		$sales=$this->getsale($this->where,"*");
		if(isset($sales)){
			foreach($sales as $sale)
			{
				$this->calculate($net,$sale,$sale['userid'],$sale,$minlayer,$maxlayer,$cons,$num_ratio);
			}
		}
		unset($sales);
		unset($cons);
		$this->prizeUpdate();
	}
	//计算处理
	public function calculate($net,&$from,$userid,$sale=null,$minlayer,$maxlayer,&$cons,$num_ratio)
	{
		$user =M('会员')->where(array("id"=>$userid))->lock(true)->find();
		//取得网络区域设定
		$Branch =$net->getBranch();
		//判断如果会员不在本网则跳出
		if($user[$net->name.'_层数'] <= 0) return;
		$upusers=$net->getups($user,$minlayer,$maxlayer,'',true);
		$cengset=array();
		foreach($upusers as $upuser)
		{
			$str="";
			$layer = $user[$net->name."_层数"] - $upuser[$net->name."_层数"];
			//判断当前层是否计算过,还是否需要在计算
			//$newdata=M('会员')->where(array("id"=>$upuser["id"]))->find();
			$where = array();
			$where['编号'] = $upuser["编号"];
			$where['层数'] = $layer;
			$sumnum = M($this->name)->where($where)->sum('金额');
			$sumnum === null && $sumnum = 0;
			//当前层是否重复计算
			if($sumnum && !$this->rePrize)
				continue;
			//字段
			$fields = 'id,编号,'.$this->lvName;
			if($this->rowName!="" && $this->rowName!='id'){
				$fields.=",".$this->rowName;
			}
			//查询条件
			$where=$net->name."_层数=".($upuser[$net->name.'_层数']+$layer)." and 空点=0 and 状态='有效' and 审核日期<".($this->_caltime+86400);
			//当前层是否满足条件
			$layer_have=true;
			$limit="";
			//根据排序的字段排序
			$orderName=$this->orderName;
			if($this->orderName==""){
				$orderName='审核日期 asc';
			}
			//层满才拿奖，此处只适合拿固定值，因为$bump在这里未赋值
			if($this->full == 1)
			{
				if($layer==1)
					$realnum=count($Branch);
				else
					$realnum=pow(count($Branch),$layer);
			}
			else
			{
				$limit=" limit 1";
				$realnum=count($Branch);
			}
			$minlv=1000000000;$bump=1;
			$sql="";
			//左右区的新进点位
			foreach($Branch as $key=>$qu)
			{
				$downwhere="  and  {$net->name}_网体数据 like '".trim($upuser[$net->name.'_网体数据'].",{$upuser['id']}-{$qu}%'",',');
				if($sql!='')$sql.=' UNION ALL ';
				$sql.="select * from( SELECT ".$fields.",'".$qu."' as region  FROM `dms_会员` WHERE ".$where.$downwhere." order by ".$orderName." ".$limit.") a".$key;
			}
			$findusers=M()->query($sql);
			//根据当层的下级会员数量
			if(count($findusers)>=$realnum){
				$layer_have=true;
				if($this->full==0){
					foreach($findusers as $key=>$finduser){
						$str.=",".$finduser['region']."-".$finduser['id'];
						//找出最小的值
						if($minlv>$finduser[$this->lvName])
						{
							$minlv=$finduser[$this->lvName];
						}
						if($this->rowName!=""){
							//获取计算的值  去两边最小值
							if($finduser[$this->rowName]>$bump && $key==0){
								$bump=$finduser[$this->rowName];
							}else if($bump>$finduser[$this->rowName]){
								$bump=$finduser[$this->rowName];
							}
						}
					}
				}
			}else{
				$layer_have=false;
			}
			if($layer_have)
			{
				foreach($cons as $con)
				{
					$up_rs_lv = $upuser[$this->lvName];
					$where  = $con['where'];
					$where  = str_replace("{minlv}",$minlv,$where);
					$wheredata=array('U'=>&$user,'M'=>&$upuser,'S'=>&$sale);
					//取双方最小级别,则做降级操作
					if($con['minlv'] <= $up_rs_lv && $con['maxlv'] >= $up_rs_lv && $con['minlayer'] <= $layer && ($con['maxlayer'] >= $layer || $con['maxlayer']==-1) && transform($where,array(),$wheredata))
					{
						//得到最终的奖金额
						$prizenum = getnum($bump,$con['val'],$this->decimalLen,$upuser[$this->name.'比例']);
						//减去已拿的奖金
						if($prizenum>=$sumnum){
							$prizenum-=$sumnum;
						}
						if($prizenum>0){
							if(strstr($con["val"],'%')){
								$calculateType = $bump.' * '.$con["val"]; 
							}else if(substr($con["val"],0,1)=='*'){
								$calculateType = $bump.$con["val"]; 
							}else{
								$calculateType = $con["val"];
							}
							$this->addprize($upuser,$prizenum,$user,$calculateType,$layer);
							$data = array();
							$data['编号'] = $upuser['编号'];
							$data['时间'] = $this->parent('tle')->_caltime;
							$data['层数'] = $layer;
							$data['金额'] = $prizenum;
							$data['数据'] = trim($str,',');
							M($this->name)->where(array("id"=>$upuser["id"]))->add($data);							
						}
					}
				}
			}
		}
		unset($upusers);
		unset($user);
	}
	public function event_modifyId($oldbh,$newbh)
	{ 
		M()->execute("update dms_{$this->name} set 编号='{$newbh}' where 编号='{$oldbh}'");
	}
}
?>