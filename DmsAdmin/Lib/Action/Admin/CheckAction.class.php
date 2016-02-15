<?php
defined('APP_NAME') || die('不要非法操作哦!');
class CheckAction extends CommonAction
{
	//奖金配置节点，中的数值设置，与目前实际设置不符
	public function index()
	{
		
		$this->display();
		$con=$this->con;
		//对tleset节点遍历。寻找用户目前系统不相符或者重复的配置=================
		$xml   =$con->xml;
		$xpath = new DOMXPath($xml->ownerDocument);
		$tleset =$xpath->query('tleset//tr/*',$xml);
		
		$tlearray=array();
		$filearray=array();
		//读取默认模板设置
		$fixsets=$xpath->query('fixset',$xml);
		foreach($fixsets as $fixset)
		{
			$Theme=$fixset->getAttribute('theme');
			if($Theme!='')
			{
					$filearray['DEFAULT_THEME'] = $Theme;
					$tlearray['DEFAULT_THEME'] = array(
						'app'=>$this->APP,
						'name'=>'DEFAULT_THEME',
						'data'=>$Theme,
						);
			}
		}		
		
		$connames=array();
		foreach($tleset as $v)
		{
			if(is_numeric($v->getAttribute('xname'))){
				throw_exception("config.xml中有xname属性值为纯数字,".$v->getAttribute('xname'));
			}
			
			$nodename=$v->nodeName;//get_class($v);
			if($nodename=='num')
			{
				$xname=$v->getAttribute('xname');
				$val=$v->getAttribute('val');
				if(strpos($xname,','))
				{
					$xname=explode(',',$xname);
					$val  =explode(',',$val);
					foreach($xname as $xname_k=>$xname_v)
					{
						if(in_array($xname_v,$connames))
						{
							$this->msg($xname_v.'有重复',2);
						}
						else
						{
							$connames[]=$xname_v;
						}
						if(floatval($val[$xname_k]) != CONFIG($xname_v))
						{
							$this->msg($xname_v.'的默认值('.floatval($val[$xname_k]).')'.
							'与当前设置('.CONFIG($xname_v).')有差异',1);
						}
					}
				}
				else
				{
					if(in_array($v->getAttribute('xname'),$connames))
					{
						$this->msg('配置项'.$v->getAttribute('xname').'有重复',2);
					}
					else
					{
						$connames[]=$v->getAttribute('xname');
					}
					
					if(floatval($v->getAttribute('val')) != CONFIG($v->getAttribute('xname')))
					{
						$this->msg($v->getAttribute('xname').'的默认值('.floatval($v->getAttribute('val')).')'.
						'与当前设置('.CONFIG($v->getAttribute('xname')).')有差异',1);
					}
				}
			}
		} 
		//检查数据库是否支持innoDB===========================================================================
		$engines = M()->query("show engines");
		$find_innodb=false;
		foreach($engines as $engine)
		{
			if($engine['Engine'] == 'InnoDB' && ($engine['Support'] == 'YES' || $engine['Support'] == 'DEFAULT'))
			{
				$find_innodb=true;
			}
		}
		if(!$find_innodb)
		{
			$this->msg('当前数据库环境不支持innodb环境');
		}
		//订单节点检查======================================================
		$sales=X('sale_*');
		foreach($sales as $sale)
		{
			if($sale->accBank=='' && $sale->user != 'admin' && $sale->confirm)
			{
				if($sale->use)
				{
					$this->msg('前台存在未经货币审核并可以直接生效的订单'.$sale->name);
				}
				else
				{
					$this->msg('前台存在未经货币审核并可以直接生效的订单'.$sale->name.'但是已被禁用',1);
				}
			}
			//
		}
		//
		//时间校准检查
		
	}
	//消息显示函数
	public function msg($msg,$type=2)
	{
		if($type==1)
		{
			$icon = '/Public/Images/ExtJSicons/error.png';
		}

		if($type==2)
		{
			$icon = '/Public/Images/ExtJSicons/delete.png';
		}
		
        print "<script  type='text/javascript' charset='UTF-8'>parent.addexemsg('$msg','$caltime','$icon');</script>";
        ob_flush(); //强制将缓存区的内容输出
        flush(); //强制将缓冲区的内容发送给客户端
	}
}
?>