<?php
/*
* 支付类接口定义
*/
interface PayInterface 
{
	//返回支付接口中文名称
	public static function getName();

	//返回接口中文介绍
	public static function getMemo();

	//返回需要配置的项
	public static function getConfigInfo();

	//提供的直连银行的列表
	public static function getBankList();

	//提交表单
	public function submit();

	//处理返回结果
	public function receive();
	
	//设置支付金额
	public function setMoney($money);

	//获取支付金额
	public function getMoney();

	//设置订单id
	public function setOrderId($id);

	//获取订单id
	public function getOrderId();

	//设置支付返回地址
	public function setServerurl($url);

	//设置浏览器跳转地址
	public function setLocationUrl($url);
	
	//是否支持银行直连
	public function isSupportCredit();

	//设置直连的银行
	public function setCreditBank($bank);

	//返回当前直连银行的中文名称
	public function getCreditBankName();

	//返回支付失败的提示信息
	public function getMessage();
}
?>