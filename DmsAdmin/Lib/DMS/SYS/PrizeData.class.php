<?php
	/*奖金构成处理类
	本类要实现的功能
	1.由prize类的addprize方法和prizeUpdate调用，创建奖金构成缓存
	2.在各类action提交事物时调用或者日结完成时commit方法保存奖金构成信息
	*/
	class PrizeData
	{
		//文件缓存，避免频繁的文件写入
		private static $cache='';
		//缓存文件指针
		private static $Handle;
		private static $isin  = false;
		//增加缓存
		public static function add($tlename,$prizename,$userid,$byname,$fromid,$val,$trueval,$memo,$layer,$lvname,$lv)
		{
			$comdata  =array();
            $comdata[]=$tlename;  //0:奖金表名
            $comdata[]=$prizename;//1:奖金名
            $comdata[]=$userid;   //2:会员ID
            $comdata[]=$byname;//3:别名
            $comdata[]=$fromid;   //4:来源ID
            $comdata[]=$val;      //5:金额
            $comdata[]=$trueval;  //6:封顶金额
            $comdata[]=$memo;     //7:层数
            $comdata[]=$layer;    //8:备注
            $comdata[]=$lvname;   //9:级别名称
            $comdata[]=$lv;   //10:级别
            //对数组所有内容左右两边加双引号,对文本内部的"转义为""
            $comdata=array_map(function ($val){
            	return '"'.str_replace('"','""',$val).'"';
            	},$comdata);
            //
            $csvline = implode(",",$comdata);			
			self::$cache.=$csvline.PHP_EOL;
			self::flush();
		}
		//增加K值缓存 ktype = TK:总奖金K值； PK:独立奖金K值； KW:kwhere条件 
		public static function Kadd($tlename,$ktype,$prizename,$byname,$val,$ids='')
		{
			$comdata  =array();
            $comdata[]=$tlename;  //0:奖金表名
            $comdata[]=$ktype;   //1:K值类型
            $comdata[]=$prizename;//2:奖金名
            $comdata[]=$byname;//3:别名
            $comdata[]=$val;      //4:K值
            $comdata[]=$ids;  //6:kwhere条件下符合条件的id
            //对数组所有内容左右两边加双引号,对文本内部的"转义为""
            $comdata=array_map(function ($val){
            	return '"'.str_replace('"','""',$val).'"';
            	},$comdata);
            //
            $csvline = implode(",",$comdata);			
			self::$cache.=$csvline.PHP_EOL;
			self::flush();
		}
		//将内存中的数据写入缓存文件，
		public static function flush($total = false)
		{
			//刷新缓存到文件
			if(strlen(self::$cache)>0 && (strlen(self::$cache)>10000000 || $total))
			{
				//生成唯一文件名
				$Handle = self::getHandle();
				//写入文件
				fwrite($Handle,self::$cache);
				//清空缓存
				self::$cache='';
			}
		}
		//获得文件句柄
		public static function getHandle()
		{
			if(!self::$Handle)
			{
				//开始刷入静态内存
				//得到文件
				$filename = self::getFileName();
				//得到所在目录
	            $dir = dirname($filename);
	            //尝试创建目录
	            !is_dir($dir) && mkdir($dir,0777,true);
	            //打开文件
	            self::$Handle = fopen($filename,"w+");
	            //加锁
	            flock(self::$Handle,LOCK_EX|LOCK_NB);
			}
			return self::$Handle;
		}
		public static function getFileName()
		{
			static $name;
			$name || $name = md5(uniqid(rand(),true));
			return APP_PATH.'PrizeData/Temp/'.$name.'.data';
		}
		//确认奖金正常产生后，把数据提交到正式文件中，
		//如果执行到这之前就出错。那么不会导致奖金构成错乱
		/*
			刷写流程
			首先创建/PrizeData/2015/0313/这种目录
			然后判断其中是否有data.php文件，如果存在判断大小。
			超过1M开始散列，如果没有超过1M则直接存储
		*/
		public static function commit($caltime,$total=false)
		{
			self::flush($total);
			if(!isset(self::$Handle))return;
            //判断PrizeData文件夹是否存在 不存在创建新文件夹
            if(file_exists(APP_PATH.'PrizeData')){
                mkdir(APP_PATH.'PrizeData',0777,true);
            }
			$content = '';
			$dataname = APP_PATH.'PrizeData/'.date('Y/md',$caltime).'/data.php';
			$dir = dirname($dataname);
			!is_dir($dir) && mkdir($dir,0777,true);
			//如果存在文件则读取大小
			if(is_file($dataname))
			{
				$size = filesize($dataname);
			}
			else
			{
				$size = 0;
				$content = '<?php die;?>'.PHP_EOL;
			}
			$fp = fopen($dataname,"a");
	        //加锁
	        flock($fp,LOCK_EX|LOCK_NB);
	        
	        rewind(self::$Handle);
	        $filename = self::getFileName();
	        $content .= fread(self::$Handle, filesize($filename));
	        fwrite($fp,$content);
			//文件如果小于1M,走单文件合并流程
			/*if($size < 1024 * 1024)
			{
				rewind(self::$Handle);
				
			}
			else
			{
				
			}*/
			flock($fp,LOCK_UN);
			fclose($fp);
			flock(self::$Handle,LOCK_UN);
			fclose(self::$Handle);
			self::$Handle = null;
			if(file_exists($filename))unlink($filename);
			return $dataname;
		}
		
		public static function getprizedata($caltime,$tlename,$prizename,$userid,$byname,$unmemo)
		{
			$alldata = array();
			$redata = array();
			$upredata = array();
			$dataname = APP_PATH.'PrizeData/'.date('Y/md',$caltime).'/data.php';
			//判断奖金构成文件是否存在
			if(!is_file($dataname))
			{
				return '无奖金构成信息';
			}
			$findstr = '"'.$tlename.'","'.$prizename.'","'.$userid.'","'.$byname.'"';
			$findstr_unmemo = '"'.$tlename.'","'.$prizename.'"';
			$TKfindstr = '"'.$tlename.'","TK"';
			$PKfindstr = '"'.$tlename.'","PK","'.$prizename.'","'.$byname.'"';
			$KWfindstr = '"'.$tlename.'","KW","'.$prizename.'","'.$byname.'"';
			$fp = fopen($dataname,"rb");
			while(!feof($fp))
		    {
		  		$fstr = fgets($fp);
		  		if(strpos($fstr,$findstr)!==false){
		  			$tmpdata = explode(',',str_replace(PHP_EOL,'',$fstr));
		  			$data = array();
			  		//$data[]=$tlename;  //0:奖金表名
		            $data['name']     =trim($tmpdata[1],'"');//1:奖金名
		            $data['userid']   =trim($tmpdata[2],'"');//2:会员ID
		            $data['prizename']=trim($tmpdata[3],'"');//3:别名
		            $data['fromid']   =trim($tmpdata[4],'"');//4:来源ID
		            $data['val']      =trim($tmpdata[5],'"');//5:金额
		            $data['trueval']  =trim($tmpdata[6],'"');//6:封顶金额
		            $data['memo']     =trim($tmpdata[7],'"');//7:备注
		            $data['layer']    =trim($tmpdata[8],'"');//8:层数
		            $data['lvname']   =trim($tmpdata[9],'"');//9:级别名称
		            $data['lv']       =trim($tmpdata[10],'"');//10:级别
		            //查询来源编号
		            $data['编号'] = M('会员')->where('id='.$data['fromid'])->getField('编号');
		            $redata[] = $data;
		  		}
		  		if($unmemo && strpos($fstr,$findstr_unmemo)!==false){
		  			$tmpdata = explode(',',str_replace(PHP_EOL,'',$fstr));
		  			if($tmpdata[4]==('"'.$userid.'"')){
			  			$data = array();
				  		//$data[]=$tlename;  //0:奖金表名
			            $data['name']=$tmpdata[1];//1:奖金名
			            $data['userid']=$tmpdata[2];   //2:会员ID
			            $data['prizename']=$tmpdata[3];//3:别名
			            $data['fromid']=$tmpdata[4];   //4:来源ID
			            $data['val']=$tmpdata[5];      //5:金额
			            $data['trueval']=$tmpdata[6];  //6:封顶金额
			            $data['memo']=$tmpdata[7];     //7:备注
			            $data['layer']=$tmpdata[8];    //8:层数
			            $data['lvname']=str_replace('"','',$tmpdata[9]);   //9:级别名称
			            $data['lv']=str_replace('"','',$tmpdata[10]);   //10:级别
			            //查询来源编号
			            $data['编号'] = M('会员')->where('id='.$data['userid'])->getField('编号');
			            $upredata[] = $data;
		            }
		  		}
		  		//K值
		  		if(strpos($fstr,$TKfindstr)!==false || strpos($fstr,$PKfindstr)!==false || strpos($fstr,$KWfindstr)!==false){
		  			$tmpdata = explode(',',str_replace(PHP_EOL,'',$fstr));
		  			$data = array();
			  		//$data[]=$tlename;  //0:奖金表名
		            $data['ktype']=$tmpdata[1];//1:值类型
		            $data['prizename']=$tmpdata[2];   //2:会员奖金名
		            $data['byname']=$tmpdata[3];//3:别名
		            $data['val']=str_replace('"','',$tmpdata[4]);   //4:K值
		            $data['ids']=$tmpdata[5];      //5:kwhere条件下符合条件的id
		            $alldata[str_replace('"','',$tmpdata[1])]=$data;
		            //id是否符合条件
		            if($tmpdata[1]=='"KW"' && $tmpdata[5]!='""' && !in_array($userid,explode("|",str_replace('"','',$tmpdata[5])))){
		            	$alldata['KW'] = array();
		            }
		  		}
		    }
			fclose($fp);
			$alldata['prize']=$redata;
			$alldata['prizeup']=$upredata;
			return $alldata;
		}
	}
?>