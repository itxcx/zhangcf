<?php
//defined('APP_NAME') || die(L('not_allow'));
class LanguageAction extends CommonAction {
	 //切换简繁
	 public function index(){
	 	$id = 0;
	 	if(I("get.id/d")>0)
	 	{
	 		$id = I("get.id/d") ;
		 	if($id==1){
		 	 	setcookie('languglo','1');
		 	 	  //redirect(__APP__.'/User/Index/index');
		 	 }
		 	 if($id==2){
		 	 	setcookie('languglo','2');
		 	 	  //redirect(__APP__.'/User/Index/index');
		 	 }
		 	 if($id==3){
		 	 	setcookie('languglo','3');
		 	 	//setcookie('think_language','zh-cn');
		 	    redirect(__APP__.'/User/Index/index');
		 	 }
	 	}
		$this->display();
	 }
}
?>