<?php
// 支付结果模型
class Fun_payModel 
{
	/*
	* 支付成功
	* orderId
	*/
	private $config=array();
	public function _initialize(){
		
	}
	
	public function success($orderId,$args)
	{
		//获取配置
		if(empty($this->config)){
			$this->config=require ROOT_PATH.'DmsAdmin/Conf/config.php';
		}
		$PayOrder	= M();
		//获取订单信息
		$info		= $PayOrder->table("pay_order")->lock(true)->where(array('orderId'=>$orderId))->find();
		$user=M()->table('dms_货币');
		//锁表
		$re = $user->where(array("编号"=>$args['userid']))->lock(true)->find();
		
		if($re){
			$memo= $args['userid'].'通过'.$args['payment'].'成功充值 '.$args['money'].$args['type'];
			$PayOrder->table("pay_order")->where(array('orderId'=>$orderId))->save(array('memo'=>$memo));
			$data=array();
			$data['余额'] = floatval($re[$args['type']]) + floatval($args['money']);
			$res=M()->table('dms_货币')->where(array("编号"=>$args['userid']))->save(array($args['type']=>$data['余额']));
			$data['备注'] = $memo;	
			$data['来源'] = $args['userid'];
			$data['时间'] = time();
			$data['编号'] = $args['userid'];
			$data['类型'] = '在线支付';
			$data['金额'] = $args['money'];
			$bank =M()->table('dms_'.$args['type'].'明细');
			$bank -> add($data);
			$updata=array();
			$updata['订单号']=$orderId;
			$updata['编号']=$args['userid'];
			$updata['金额']=$args['money'];
			$updata['支付方式']=$args['payment'];
			$updata['支付时间']= time();
			$updata['备注']=$memo;
			$updata['状态']=1;
			M()->table('dms_onlinepay')->add($updata);
            //修改会员的到帐金额
            if($args['paytypes']){
                //读取接口表中的pay_type
		        $arr = M('pay_onlineaccount',' ')->lock(true)->where(array('pay_type'=>$args['paytypes']))->order("pay_amount asc,id desc")->find();
		        $amount = $arr['pay_amount']+$args['money'];
	           	M('pay_onlineaccount',' ')->where(array('id'=>$arr['id']))->save(array('pay_amount'=>$amount));
           	}
            	M()->commit();
		}
	}
	//支付失败
	public function fail($orderId,$args)
	{
		$PayOrder	= M();
		$info		= $PayOrder->table("pay_order")->lock(true)->where("orderId='$orderId'")->find();
		if( $info['status']==0 )
		{
			$where['orderId']	= $orderId;
			$data['memo']		= $args['userid'].'通过'.$args['payment'].' 充值 '.$args['money'].$args['type'].'失败';
			$PayOrder->table("pay_order")->where($where)->save($data);
			$updata=array();
				$updata['订单号']=$orderId;
				$updata['编号']=$args['userid'];
				$updata['金额']=$args['money'];
				$updata['支付方式']=$args['payment'];
				$updata['支付时间']=time();
				$updata['备注']=$data['memo'];
				$updata['状态']=2;
				M()->table($this->config['DB_PREFIX'].'onlinepay')->add($updata);
		}
	}
}
?>