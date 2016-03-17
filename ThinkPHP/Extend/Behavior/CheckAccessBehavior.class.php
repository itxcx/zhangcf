<?php
    /**
    +------------------------------------------------------------------------------
    * 系统行为扩展 识别浏览设备
    +------------------------------------------------------------------------------
    */
    class CheckAccessBehavior extends Behavior
    {
        // 行为参数定义（默认值） 可在项目配置中覆盖
        protected $options = array();
        // 行为扩展的执行入口必须是run
        public function run(&$params)
        {
            // 开启静态缓存
            $this->is_mobile();
        }
        private function is_mobile()
        {
            //判断手机端自动登录的开关是否开启
            if(adminshow('phone_auto'))
            {
                import('ORG.Mobile.Mobile_Detect');
                $detect = new Mobile_Detect;
                $isMobile = $detect->isMobile();
                $isTablet = $detect->isTablet();
                if($isMobile)
                {
                    $_SESSION['isMobile'] = true;
                    if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')){
                        $_SESSION['iswx'] = true;
                    }
                }
                else
                {
                    $_SESSION['isMobile'] = false;
                }
            }
            else
            {
                $_SESSION['isMobile'] = false;
            }
        }
    }
?>