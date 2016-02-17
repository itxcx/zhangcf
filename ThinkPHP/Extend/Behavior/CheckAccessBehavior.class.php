<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: CheckLangBehavior.class.php 2735 2012-02-15 03:11:13Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 语言检测 并自动加载语言包
 +------------------------------------------------------------------------------
 */
class CheckAccessBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options = array(
        );
    // 行为扩展的执行入口必须是run
    public function run(&$params){
        // 开启静态缓存
        $this->is_mobile();
    }
    /**
     +----------------------------------------------------------
     * 访问端检查
     * 检查是pc
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function is_mobile(){
		//判断手机端自动登录的开关是否开启
		if(adminshow('phone_auto'))
		{
			import('ORG.Mobile.Mobile_Detect');
		    $detect = new Mobile_Detect;
		    $isMobile = $detect->isMobile();
		    $isTablet = $detect->isTablet();		
		    if($isMobile)
			{
			  	$_SESSION['isMobile']=true;
			}else{
				$_SESSION['isMobile']=false;
			}
		}else{
	       $_SESSION['isMobile']=false;
		}
	}
}
?>
