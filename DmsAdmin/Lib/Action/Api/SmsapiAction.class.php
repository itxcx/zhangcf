<?php
	class SaleapiAction extends CommonAction {
		public function index(){
			ini_set("display_errors","On");
			//执行发送程序
			M()->startTrans();
			import('COM.SMS.DdkSms');
			$surplus = DdkSms::autoSend();
			M()->commit();
		}
	}
?>