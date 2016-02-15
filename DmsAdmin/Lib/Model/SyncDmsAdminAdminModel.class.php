<?php
class SyncDmsAdminAdminModel
{
	protected $comparison = array(' nheq '=>' !== ',' heq '=>' === ',' neq '=>' != ',' eq '=>' == ',' egt '=>' >= ',' gt '=>' > ',' elt '=>' <= ',' lt '=>' < ');
	public $APP="DmsAdmin";
	
	public function getAllxml(){
		$sqlxml=$this->loadSqlxml();
		$conxml=$this->loadConfig();
		$sqlxml.=$conxml;
		$sqlxml='<?xml version="1.0" encoding="utf-8" ?><db>'.$sqlxml.'</db>';
		$xml=simplexml_load_string($sqlxml);
		return $xml;
	}
   /*
    **对xml文件中的foreach以及if 模板标签进行解析
    **
   */
	public function transTag($tagname,$str,$val)
	{
		if($tagname=='foreach')
		{
			preg_match_all('/<foreach[\s]*name=\"{((?:(?!name).)+?)}\"[\s]*item=\"((?:(?!item).)+?)\"[\s]*>((?:(?!foreach).)+?)<\/foreach>/is',$str,$transtr,PREG_SET_ORDER); 
			if(!empty($transtr))
			{
			 	for ($i = 0; $i < count($transtr); $i++)
				{
					$name=$transtr[$i][1];
					$item=$transtr[$i][2];
					$content=$transtr[$i][3];
					$transcontent='';
					eval("\$transname = ".$name.";");
					if(is_string($transname))
					{
						$pos=strpos($transname,',');
						if($pos){
							$transname=explode(',',$transname);
						}
					}
					if(is_array($transname))
					{
						foreach($transname as $v)
						{
							if(is_array($v))
							{
								preg_match_all('/{([\$|\:].*?)}/is',$content,$contents,PREG_SET_ORDER);
								if(!empty($contents))
								{
									$ncontent=$content;
									for($j=0;$j<count($contents);$j++)
									{
										$arrkey=$contents[$j][1];
										if(strstr($arrkey,":")){//判断是否是执行php函数或者方法
											$arrkey=str_replace(":","",$arrkey);
											eval("\$arrval = ".$arrkey.";");
										}else if(strstr($arrkey,"$".$item.".")){//判断是否是获取对象的值
											$arrkey=str_replace("$".$item.".","",$arrkey);
											eval("\$arrval = '".$v[$arrkey]."';");
										}else{
											$arrval=$contents[$j][0];
										}
										$ncontent=str_replace($contents[$j][0],$arrval,$ncontent);
									}
									$transcontent.=$ncontent;
								}
							}else if(is_object($v)){
								preg_match_all('/{([\$|\:].*?)}/is',$content,$contents,PREG_SET_ORDER);
								if(!empty($contents))
								{
									$ncontent=$content;
									for($j=0;$j<count($contents);$j++)
									{
										$arrkey=$contents[$j][1];
										if(strstr($arrkey,":")){//判断是否是执行php函数或者方法
											$arrkey=str_replace(":","",$arrkey);
											eval("\$arrval = ".$arrkey.";");
										}else if(strstr($arrkey,"$".$item.".")){//判断是否是获取对象的值
											$arrkey=str_replace("$".$item.".","",$arrkey);
											eval("\$arrval = '".$v->$arrkey."';");
										}else{
											$arrval=$contents[$j][0];
										}
										$ncontent=str_replace($contents[$j][0],$arrval,$ncontent);
									}
									$transcontent.=$ncontent;
								}
							}else{
								$transcontent.=str_replace('{$'.$item.'}',$v,$content);
							}
						}
					 }else{
						 echo "foreach循环不是一个有效数组或字符串";
						 die;
					 }
					 $str=str_replace($transtr[$i][0],$transcontent,$str);	
			  	}
			  	$str=$this->transTag($tagname,$str,$val); 
			}
		}
		if($tagname=='if')
		{
	        preg_match_all('/<if[\s]*condition="((?:(?!").)+?)">((?:(?!if).)+?)<\/if>/is',$str,$transtr,PREG_SET_ORDER); 
			if(!empty($transtr))
			{
				for ($i = 0; $i < count($transtr); $i++)
				{
					$all=$transtr[$i][0];
					$condition=$transtr[$i][1];
					$content=$transtr[$i][2];
					$condition   = $this->parseCondition($condition);
					eval("\$trancondition = ".$condition.";");
					if($trancondition)
					{
	                	$str=str_replace($all,$content,$str);	
					}else{
						$str=str_replace($all,'',$str);
					}
	                $str=$this->transTag($tagname,$str,$val); 
				}
			}
		}
      	return $str;
	}

	/*
	 **thinkphp 用来处理condition的函数
	 **
	 */
	 public function parseCondition($condition) {
        $condition = str_ireplace(array_keys($this->comparison),array_values($this->comparison),$condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is','$\\1->\\2 ',$condition);
       /* switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
            case 'array': // 识别为数组
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','$\\1["\\2"] ',$condition);
                break;
            case 'obj':  // 识别为对象
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','$\\1->\\2 ',$condition);
                break;
            default:  // 自动判断数组或对象 只支持二维
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ',$condition);
				
        }*/
        return $condition;
    }

	/*
	 **加载固定配置的xml文件即system.xml
	 **
	*/
	public function loadConfig()
	{
		$conarr='';
		if(file_exists(ROOT_PATH.$this->APP.'/Lib/DMS/sqlxml/system.xml'))
		{
			$conarr=file_get_contents(ROOT_PATH.$this->APP.'/Lib/DMS/sqlxml/system.xml');
		}
		$temconarr='<?xml version="1.0" encoding="utf-8" ?><db>'.$conarr.'</db>';
		$temxml=simplexml_load_string($temconarr);
		if($temxml === false)
		{
			unset($temconarr);
			echo 'system.XML文件有语法错误';
			die;
		}
		unset($temconarr);
		return $conarr;
	}
	/*
	 **加载config.xml中对应的类的xml文件
	 **
	*/
     public function loadSqlxml()
	 {
		$rs='';
		$arr=X();
		foreach($arr as $val)
		{
			$sqlxml='';
			if(file_exists(ROOT_PATH.$this->APP.'/Lib/DMS/sqlxml/'.get_class($val).'.xml'))
			{
				if($val->name=='')
				{
					echo "您有一个".get_class($val).'模块没有设置name值，建库失败';
					exit();
				}
				$sqlxml=file_get_contents(ROOT_PATH.$this->APP.'/Lib/DMS/sqlxml/'.get_class($val).'.xml');
				if($this->utf8_check($sqlxml)==4)
				{
					echo ROOT_PATH.$this->APP.'/Lib/DMS/sqlxml/'.get_class($val).'.xml不是UTF-8格式的文件';
					die();
				}
				$sqlxml=str_replace('$this','$val',$sqlxml);
				/*
				**循环模板<foreach name="{$this->..}" item="vo">
				<field name={$vo} />
				</foreach>
				替换
				**
				*/
				$sqlxml=$this->transTag('if',$sqlxml,$val);
				$sqlxml=$this->transTag('foreach',$sqlxml,$val);
				preg_match_all('/{([\$|\:].*?)}/is', $sqlxml, $match, PREG_SET_ORDER);
				for ($i = 0; $i < count($match); $i++) {
					$returnstr='';
					$match[$i][1]=str_replace(":","",$match[$i][1]);
					eval("\$returnstr = ".$match[$i][1].";");
					$sqlxml = str_replace($match[$i][0], $returnstr, $sqlxml);	
				}
				$temsqlxml='<?xml version="1.0" encoding="utf-8" ?><db>'.$sqlxml.'</db>';
				$temxml=simplexml_load_string($temsqlxml);
				if($temxml === false)
				{
					$filname=get_class($val);
					echo $filname.".XML文件有语法错误";
					if($this->utf8_check($temsqlxml)==3||$this->utf8_check($temsqlxml)==4)
					{
						echo ".可能非UTF-8编码";
					}
					unset($temsqlxml);
					die;
				}
				unset($temsqlxml);
			}
			$rs.=$sqlxml;
		}
		return $rs;
		}
	// 
	// 测试文本是否是utf8编码
	// 
	// 返回值：
	//   1 - 有BOM头的内容
	//   2 - 纯utf8的内容
	//   3 - 较可能是utf8的内容
	//   4 - 较不可能是utf8的内容
	// 
	function utf8_check($text)
	{
	  $utf8_bom = chr(0xEF).chr(0xBB).chr(0xBF);
	  
	  // BOM头检查
	  if (strstr($text, $utf8_bom) === 0)
	    return 1;
	  
	  $text_len = strlen($text);
	  
	  // UTF-8是一种变长字节编码方式。对于某一个字符的UTF-8编码，如果只有一个字节则其最高二进制位为0；
	  // 如果是多字节，其第一个字节从最高位开始，连续的二进制位值为1的个数决定了其编码的位数，其余各字节均以10开头。
	  // UTF-8最多可用到6个字节。
	  //
	  // 如表：
	  // < 0x80 1字节 0xxxxxxx
	  // < 0xE0 2字节 110xxxxx 10xxxxxx
	  // < 0xF0 3字节 1110xxxx 10xxxxxx 10xxxxxx
	  // < 0xF8 4字节 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
	  // < 0xFC 5字节 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
	  // < 0xFE 6字节 1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
	  
	  $bad   = 0; // 不符合utf8规范的字符数
	  $good  = 0; // 符号utf8规范的字符数
	  
	  $need_check = 0; // 遇到多字节的utf8字符后，需要检查的连续字节数
	  $have_check = 0; // 已经检查过的连续字节数
	  
	  for ($i = 0; $i < $text_len; $i ++) {
	    $c = ord($text[$i]);

	    if ($need_check > 0) {
	      $c = ord($text[$i]);
	      $c = ($c >> 6) << 6;
	      
	      $have_check ++;
	      
	      // 10xxxxxx ~ 10111111
	      if ($c != 0x80) {
	        $i -= $have_check;
	        $need_check = 0;
	        $have_check = 0;
	        $bad ++;
	      }
	      else if ($need_check == $have_check) {
	        $need_check = 0;
	        $have_check = 0;
	        $good ++;
	      }
	      
	      continue;
	    }
	    
	    if ($c < 0x80)      // 0xxxxxxx
	      $good ++;
	    else if ($c < 0xE0) // 110xxxxx
	      $need_check = 1;
	    else if ($c < 0xF0) // 1110xxxx
	      $need_check = 2;
	    else if ($c < 0xF8) // 11110xxx
	      $need_check = 3;
	    else if ($c < 0xFC) // 111110xx
	      $need_check = 4;
	    else if ($c < 0xFE) // 1111110x
	      $need_check = 5;
	    else
	      $bad ++;
	  }
	  
	  if ($bad == 0)
	    return 2;
	  else if ($good > $bad)
	    return 3;
	  else
	    return 4;
	}
}
?>