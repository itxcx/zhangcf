<?php
// 支付结果模型
class PayResultModel 
{
	/*
	* 初始化回调
	*/
	public function init($orderId,$args)
	{
		$Model	= M();
		$where['orderId']	= $orderId;
		$data['memo']		= '初始化成功!';
		$Model->table('pay_order')->where($where)->save($data);
	}

	/*
	* 支付成功
	* orderId
	*/
	public function success($orderId,$args)
	{
		$PayOrder			= M('PayOrder');
		$info				= $PayOrder->where("orderId='$orderId'")->find();

		$where['orderId']	= $orderId;
		$data['memo']		= $args['userid'].' 成功充值 '.$info['money'].' 元';
		$PayOrder->where($where)->save($data);

	}
}
?>