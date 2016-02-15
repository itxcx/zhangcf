<?php
	class SaleapiAction extends CommonAction {
		public function index(){
			ini_set("display_errors","On");
			//默认返回值
			$result=array("status"=>false,"content"=>array());
			$apiget=M("apiget","dms_");
			//得到接收值  执行相关程序
			if($_REQUEST){
				M()->startTrans();
				M("会员")->lock(true)->limit(1)->select();
				//判断是否有uid的存在
				if($_REQUEST['uuid']){
					//判断uuid程序是否已执行完成
					$apipost=$apiget->lock(true)->where(array("uuid"=>$_REQUEST['uuid']))->find();
					if($apipost['status']==1){
						$result["status"]=true;$result["content"]=json_decode($apipost['result'],true);
					}
				}
				if(!$result["status"]){
					$_apiname=explode("_",$_REQUEST['_apiname']);
					//获得接口程序
					import ( 'DmsAdmin.API.'.$_apiname[0]);
					for($i=0;$i<(int)$_REQUEST['argsnum'];$i++){
						$data[]=json_decode($_REQUEST['args'.$i],true);
					}
					//插入执行的uuid记录
					if(isset($_REQUEST['uuid'])){
						$apidata=array(
							"uuid"=>$_REQUEST['uuid'],
							"post"=>json_encode($data),
							"model"=>$_apiname[0],
							"action"=>$_apiname[1],
							"addtime"=>systemTime(),
							"status"=>0
						);
						$apiget->add($apidata);
					}
					$returnlt=call_user_func_array(array($_apiname[0],$_apiname[1]),$data);
					//保存uuid的执行结果
					if(isset($_REQUEST['uuid'])){
						$apiget->where(array("uuid"=>$_REQUEST['uuid']))->save(array("status"=>'1',"acttime"=>systemTime(),"result"=>json_encode($returnlt)));
					}
					if(get_class($returnlt)===bool){
						$result["status"]=$returnlt;
					}else{
						if($returnlt["status"]==true){
							$result["status"]=true;
						}
						$result['content']=$returnlt;
					}
					if($_REQUEST['uuid'] && $result["status"]==true){
						M()->commit();
					}else{
						M()->rollback();
					}
				}
			}else{
				$result['content']="未得到传输数据";
			}
			echo json_encode($result);
		}
	}
?>