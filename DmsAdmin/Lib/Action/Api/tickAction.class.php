<?php
	class tickAction extends Action {
		public function index(){
			//执行模块定时器处理
            ini_set('memory_limit','1000M');
            set_time_limit(600);
            //手自动设置功能还需要调整的.
            M()->startTrans();
            X('user')->callevent('tick',array());
            M()->commit();
		}
	}
?>