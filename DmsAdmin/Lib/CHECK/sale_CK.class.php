<?php
	/*货币相关自检*/
	class sale_CK
	{
		
		//报单表与货币表中会员与会员表不对应
		public function check()
		{
			$error = '';
			$rs=M('报单')->where(" binary 编号 not in (select 编号 from dms_会员)")->getField('id');
			if(!empty($rs)){
				$error .= "<span style='color:red;'>报单表中存在与会员表不对应的会员编号（包含大小写）</span><br>";
			}
			$rs=M('货币')->where(" binary 编号 not in (select 编号 from dms_会员)")->getField('id');
			if(!empty($rs)){
				$error .= "<span style='color:red;'>货币表中存在与会员表不对应的会员编号（包含大小写）</span><br>";
			}
			if($error!=''){
				return $error;
			}else{
				return 1;
			}
			
		} 

	}
?>