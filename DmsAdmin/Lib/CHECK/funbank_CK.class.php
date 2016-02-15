<?php
	/*货币相关自检*/
	class funbank_CK
	{
		//货币明细与当前货币余额不对应
		//货币明细中会员与会员表不对应
		public function check($bankname='')
		{
			$error = '';
			$rs=M('货币')->alias('a')->join("inner join (SELECT 编号,sum(金额)金额 FROM `dms_".$bankname."明细` group by 编号) b on a.编号=b.编号 and a.".$bankname."<>b.金额")->find();
			if(!empty($rs)){
				$error .= "<span style='color:red;'>".$bankname."明细与当前".$bankname."余额不对应</span><br>";
			}
			$rs=M($bankname.'明细')->where("编号 not in (select 编号 from dms_会员)")->getField('id');
			if(!empty($rs)){
				$error .= "<span style='color:red;'>".$bankname."明细表中存在与会员表不对应的会员编号</span><br>";
			}
			if($error!=''){
				return $error;
			}else{
				return 1;
			}
			
		} 

	}
?>