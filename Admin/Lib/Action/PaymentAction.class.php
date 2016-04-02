<?php
/*
* 名称：处理支付结果 接受服务器和服务器之间的通知
* 版本：1.0v
* 修档：2015/08/02
* 开发者：0025
* 验收人：冯露露
* 开发信息：临沂市新商网络技术有限公司
*/

class PaymentAction extends Action{
	
	//处理支付运营商返回信息
	public function receive(){
		if(I("request./a")){
			M()->startTrans();
			//记录返回的数据
           	F('paytest',I("request./a"));
           	//获取支付接口的信息
			$lista=F('interface_data');
			//获取已安装的接口的直联银行信息
			//$listb=F('banklist');
			//
			$listc=$lista;//array_intersect_key ($lista,$listb);		//获取可能产生支付订单的列表（包含支付接口的订单KEY）
			//循环获得支付的订单单号
			foreach($listc as $key=>$value){
				//如果找到了订单号  则跳出执行回调处理订单状态
				if(I("request.".$value['order_key']."/s")){
					$where['orderId']=I("requset.".$value['order_key']."/s");
					import("Admin.Pay.Pay");
					//根据订单号找到要处理的订单
					$PayOrder= M('PayOrder');
					$info=$PayOrder->where($where)->find();		//根据订单号查询订单库
					if(!empty($info)){
						$payment= $info['payment_class'];		//获取支付接口名
						$pay=new Pay($payment,false);			//这里交给核心类处理
						$pay->receive($where['orderId']);				//支付接口判断支付成功还是失败
					}
					break;
				}
			}
			unset($lista,$listc,$where,$PayOrder,$info,$payment,$pay);
			$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
         	echo "<script language='javascript'>location.href='" . $http_type . $_SERVER['HTTP_HOST'] . "';</script>";
		}
	}
}
?>