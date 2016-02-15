<?php
	class stru
	{
	//节点的名称
	public $name='';
	//节点对应的DOMNode
	public $xml='';
	//getcon函数所使用的缓存
	public $_Con=array();
	//多语言标签名
    public $lang='';
	//对象的显示名称，如果设置了则以设置为准，如果没有设置，会被覆盖为name属性
	public $byname='';
	public $conFilter=array();
	public $xpath='';
	//配置所依赖的name,以逗号间隔
	public $rely = '';
	/*
	方法简介
	__construct 
	构造函数，传入DOMNode节点，对对象属性进行相应修改
	
	xset($name,$val)
	对对象属性进行修改,特点是name可以进行大小写自适应，
	$val可以为文本型，根据对象属性实际类型做转换
	此属性主要应用于<sale_*>中的<_xset name='对象名' 属性名='属性值'/>这样的标签
	实现在某一些特定订单执行期间。对其他对象属性做临时性调整
	
	isobjNode($Node)
	判断一个DOMNode对象是否是可以可以可以用于实例化的节点
	
	getatt($name)
	取得特定属性，尝试从配置表中取得持久化配置，如果没有则返回当前对象属性
	
	setatt($name,$val)
	设置持久化属性,设置后，配置信息会以特定结构保存在数据库中，并生成配置缓存文件，下次访问时对象属性自动变更
	
	parent($name)
	返回自己的父类
	
	getextend($val)
	内部函数，处理属性中存在的#嵌套并得到处理之后的实际属性，如 "#top1#" 转换为  "100"
	
	callevent($action,$action_param)
	事件触发，传入事件名以及参数,会调用包含当前对象在内的所有子对象
	并判断对象中含有的event_xxxx()方法（xxxx为$action参数表示的名字）
	
	getcon($conname,$format,$all=false)
	取得配置信息，如节点内部的<_top .../><_con .../>这样的标签
	
	getPos()
	取得当前节点在父节点的相对位置，主要应用于合成表单名
	
	
	getSelRow()
	返回一个字段设置数组，根据对象内部各类属性节点所描述的
	
	haveAttr()
	判断在XML是否设置过特定节点属性
	*/
	public function __construct($xml)
	{
		$this->xml = $xml;
		$attarr=$xml->attributes;
		$atts=get_object_vars($this);
		$attslowerkeyarr=array_change_key_case($atts);
		foreach($attarr as $k=>$v)
		{
            /* //这是在实例化对象是不对带##的属性值转换
			if(isset($this->$k)){
		    $this->$k=$xml->getAttribute($k);
		    */
		    //判定attval是否存在#存在则替换
			//判定$this->$k的类型，如果是数值型，则做转换
			///*
			if(array_key_exists(strtolower($k),$attslowerkeyarr))
			{
				$objkey=$k;
				foreach($atts as $attkey=>$attval)
				{
					if(strtolower($k)==strtolower($attkey))
					{
                        $objkey=$attkey;
					   break;
					}
				}
				$val=$xml->getAttribute($k);
				 preg_match_all('/#(.*)#/U',$val,$truevals,PREG_SET_ORDER);
				 if(count($truevals)>0){
				  foreach($truevals as $trueval)
				  {
				  	  
		            if($trueval[1] !=''){
		               $replace=$this->getextend($trueval[0]);
					   $val=str_replace("#".$trueval[1]."#",$replace,$val);
			        }
			      }
				 }
					 $type=gettype($this->$objkey);
		            if($type=='integer')
		            {
		             $this->$objkey=(integer)$val;
		            }elseif($type=='boolean')
		            {
		            	   $this->$objkey=($val == 'true' )? true : false;
		            }elseif($type=="double"){
					   
					   $this->$objkey=(double)$val;
				   }else{
		            	   $this->$objkey=$val;
		            }
			}
			else
			{
				throw_exception("属性不匹配,您对".get_class($this)."设置的节点属性<b>$k</b>不存在");
			}
		}
        if(!empty($resetatts))
		{
		 foreach($resetatts as $resetatt)
			{ 
			   if($this->reSetAction($resetatt['action']) && transform($resetatt['exp'],array()))
				{
				  $type=gettype($this->$resetatt['setname']);
                    if($type=='integer')
				      {
				         $this->$resetatt['setname']=(integer)$resetatt['setval'];
				      }elseif($type=='boolean')
				      {
				         $this->$resetatt['setname']=($resetatt['setval'] == 'true' )? true : false;
				      }elseif($type=="double"){
							$this->$resetatt['setname']=(double)$resetatt['setval'];
					  }else{
				          $this->$resetatt['setname']=$resetatt['setval'];
				 }
				}
			}
	    }
		foreach($this as $k=>$v)
		{
			$val=$this->getatt($k);
			$this->$k=$val;
		}
		$this->byname = ($this->byname != '') ? $this->byname : $this->name;
		$this->xpath = str_replace('/','-',str_replace('/con/user/','',$this->xml->getNodePath()));
		if(method_exists($this,'_initialize')){
			$this->_initialize();
		}
	}
	//临时性修改属性，传入属性名和属性值，此设置尽在本次进程中有效
	public function xset($name,$val)
	{
		$atts = get_object_vars($this);
		//得到大小写相符的名称
		$name2=null;
		foreach($atts as $attname=>$v)
		{
			if(strtolower($attname) == strtolower($name))
			{
				$name2=$attname;
			}
		}
		//如果没有找到对应的属性
		if($name2 === null)
		{
			throw_exception("执行xset,未找到对应的属性".$name);
		}
		$type=gettype($this->$name2);
        if($type=='integer')
		{
		    $this->$name2 = (integer)$val;
		}elseif($type=='boolean'){
		    $this->$name2 = ($val == 'true' )? true : false;
		}elseif($type=="double"){
			$this->$name2 = (double)$val;
		}else{
		    $this->$name2 = $val;
		}
	}
	//判断节点是否是一个功能对象,而非配置
	private static function isobjNode($Node,$con=true)
	{
		if(get_class($Node)!='DOMElement')return false;
		if($con)
		{
			if(substr($Node->nodeName,0,1)=='_')return false;
		}
		if($Node->nodeName == 'con' || $Node->nodeName == 'top')
		{
			echo "您可能有设置的con配置信息或top信息没有在前边加下划线<br>";
			exit();
		}
		return true;
	}
		/*
		该函数需要在Action中调用吧；
		当要获得一个xml类中的属性时(该属性在config.xml中不存在)，判断其属性是否为可以用户自己设定的参数
		若是，则调用数据库中的内存表的信息 内存表没有则调用文件表 若还没有偶则调用类中默认值
		并创建数据库数据和配置信息
		*/
		//读取属性
		public function getatt($name,$objname = NULL)
		{
			$objname === NULL && $objname = $this->name;
			$conname = $objname . ':' . $name;
			if(CONFIG('',$conname))
			{
                return CONFIG($conname);
			}else{	
		   	    return $this->$name;
			}
		}
		//设置属性
		public function setatt($name,$val,$objname = NULL)
		{
			$objname === NULL && $objname = $this->name;
			$conname = $objname . ':' . $name;
			if(isset($this->$name))
			{
				$this->$name=$val;
			}
			CONFIG($conname,$val);
		}
		//获得config.xml中的代##的配置信息的
		public function getextend($val)
		{
		  if($val=='')
			{
			  return NULL;
			}
		  //正则算法
		   preg_match_all('/#(.*)#/U',$val,$truevals,PREG_SET_ORDER);
		  if(count($truevals)>0){
		  	$path=new DOMXPath($this->xml->ownerDocument);
		  foreach($truevals as $trueval)
			{
		     if($trueval[1] !=''){
			  if(CONFIG('',$trueval[1]))
				{
				    $replace=CONFIG($trueval[1]);
				    $val=str_replace("#".$trueval[1]."#",$replace,$val);
			    }else{
			    	$nodes = $path->query("/con/tleset//*[@name='".$trueval[1]."']");
			    	if($nodes->item(0) == null)
			    	{
			    		throw_exception('未找到name为'.$trueval[1].'的配置项');
			    	}
			    	$replace=$nodes->item(0)->getAttribute('value');
			    	//dump($nodes->item(0)->getAttribute('type'));
			    	//dump($nodes->item(0)->getAttribute('checked'));
			    	$type=$nodes->item(0)->getAttribute('type');
			    	$checked=$nodes->item(0)->getAttribute('checked');
			    	if($type=='checkbox' && $nodes->item(0)->getAttribute('checked')==='')
			    	{
			    		$replace=$nodes->item(0)->getAttribute('offvalue');
			    		if($replace=='')
			    		{
			    			throw_exception('name='.$trueval[1].'的配置节点作为checkbox类型,未找到offvalue的值');
			    		}
			    		
			    	}
			    	//判断是否可以
			    		if($type=='select'){
			    		   //判断值
			    		   
			    		}
				    $val=str_replace("#".$trueval[1]."#",$replace,$val);
				}
			}
			}
			return $val;
			}else{
				return $val;
			}
		}
		//返回上级节点对象,如果名称为字符.则返回符合特定类型的上级,如果没有则返回直接上级
		public function parent($name='')
		{
			return X('parent',$this);
			
		}
		//激发一个事件通知
		public function callevent($action,$action_param)
		{
			
			$events=$this->getcon('event',array('name'=>'','fun'=>''));
			if (count($events) > 0){
				 include_once ROOT_PATH."/DmsAdmin/config.php";
			}
			foreach($events as $event)
			{
				if($event['fun'] == '' || !function_exists($event['fun']))
				{
					throw_exception('_event标签未指定属性fun,或指定函数不存在');
				}
				if($event['name'] == $action)
				{
					call_user_func_array($event['fun'],array(&$this,&$action_param));
				}
			}
	        $arr=X('*',$this);
			$arr[]=$this;
			$action='event_'.$action;
			foreach($arr as $v)
			{
				if(method_exists($v,$action))
				{
					//透视得到的参数名表
					$para1 = array();
					//进行反射判定
					$reflector = new ReflectionClass($v);
					$parameters = $reflector->getMethod($action)->getParameters();
					foreach($parameters as $key=>$param) {
						$para1[] = $param->getName();
					}
					//根据传入参数得到的参数表
					$para2 = array();
					foreach($action_param as $key=>$val)
					{
						$para2[]=$key;
					}
					if($para1!==$para2)
					{
						$parastr='';
						foreach($para2 as $para)
						{
							$parastr.=','.$para;
						}
						throw_exception(get_class($v).'的'.$action.'方法参数不正确,应该设置为'.$action.'('.trim($parastr,',').')');
					}
					//最终调用
					call_user_func_array(array($v,$action),$action_param);
				}
			}
		}

		//取得配置信息  获取最低级节点_con _addval _top _update
		public function getcon($conname,$format,$all=false)
		{
			//定义获取到的节点主键 保存到数组中，下次再次获取时直接读取
			$token=md5($conname.serialize($format).$all);
			//已经获取过一次
			if(isset($this->_Con[$token]))
			{
				return $this->_Con[$token];
			}
			//检查过滤器 防止未设置的读取
			if(isset($this->conFilter[$conname]))
			{
				$Filter = $this->conFilter[$conname];
				foreach($Filter as $key=>$val)
				{
					$Filter[$key]=strtolower($val);
				}
			}
			else
			{
				$Filter = null;
			}
			//获取xml配置信息 获得相应的节点配置
			$result=array();
			foreach($this->xml->childNodes as $Node)
			{
				$connames=explode('-',strtolower($Node->nodeName));
				if(self::isobjNode($Node,false) && $connames[0] == '_' . strtolower($conname))
				{
					$temp_arr=$format;
					if($Filter)
					{
						//如果存在过滤器,需要先做过滤器判定
						foreach($Node->attributes as $attkey=>$attval)
						{
							if(!in_array(strtolower($attkey),$Filter))
							{
								throw_exception($this->name.'节点的'.$conname.'配置异常,使用了一个没有被声明的属性'.$attkey);
							}
						}
					}
					//做CON循环设置8
					$sid=1;
					$eid=1;
					$step=1;
					if(count($connames)>2)
					{
						$sid=(int)$connames[1];
						$eid=(int)$connames[2];
					}
					if(count($connames)==4)
					{
						if($connames[3]=='')
						{
							throw_exception($this->name.'节点的'.$conname.'配置异常,步长没有设置');
						}
						$step=(int)$connames[3];
					}
					for(;$sid <= $eid;$sid += $step)
					{
						foreach($temp_arr as $key=>$val)
						{
							foreach($Node->attributes as $attkey=>$attval)
							{
								if(strtolower($attkey)==strtolower($key))
								{
									$temp_val=$Node->getAttribute($attkey);
									$temp_val=str_replace('@',$sid,$temp_val);
									$temp_val=$this->getextend($temp_val);
									$temp_val=str_replace('{time}',systemTime(),$temp_val);
									switch( gettype($val))
									{
										case "boolean":
											if(strtolower($temp_val)=='true') $temp_arr[$key]=true;
											else $temp_arr[$key]=false;
											//$temp_arr[$key] = (bool)$temp_val;
										break;
										case "integer":
											if(!is_numeric($temp_val) && preg_match("/^[0-9\+\-\*\/\(\)]+$/",$temp_val) == 1)
											{
												$temp_val=transform($temp_val);
											}
											$temp_arr[$key] = (double)$temp_val;
										break;
										case "double":
											if(!is_numeric($temp_val) && preg_match("/^[0-9\+\-\*\/\(\)]+$/",$temp_val) == 1)
											{
												$temp_val=transform($temp_val);
											}
											$temp_arr[$key] = (double)$temp_val;
										break;
										case "string":
											$temp_arr[$key] = str_replace('>>','<',$temp_val);
										break;
								  	}
								}
							}
						}
						if(!$Filter)
						{
							$templowerkeyarr=array_change_key_case($temp_arr);
							foreach($Node->attributes as $k=>$v)
							{
								if(!array_key_exists(strtolower($k),$templowerkeyarr))
								{
									$temp_val=$Node->getAttribute($k);
									$temp_val=$this->getextend($temp_val);
		                            $temp_arr[$k] = $temp_val;
								}
							}
						}
						if($all)
						{
							$templowerkeyarr=array_change_key_case($temp_arr);
							foreach($Node->attributes as $k=>$v)
							{
								if(!array_key_exists(strtolower($k),$templowerkeyarr))
								{
									$temp_val=$Node->getAttribute($k);
									$temp_val=$this->getextend($temp_val);
		                           $temp_arr[$k] = $temp_val; 
								}
							}
						}
						$result[]=$temp_arr;
					}
				}
			}
			$this->_Con[$token]=$result;
			return $result;
		}
		//取得当前节点的位置ID
		public function getPos()
		{
			return X('pos',$this);
		}
		//obj路径
		public function Path()
		{
			return $this->xml->getNodePath();
		}
		//obj路径
		public function objPath()
		{
			return str_replace('/','-',str_replace('/con/user/','',$this->xml->getNodePath()));
		}

		public function getLang($name){
			return $this->lang."_".$name;
		}
		
		public function lang($name,$langname){
			if(C('My_LANG_SWITCH_ON')){
				return L($langname);
			}else{
	           	return $name;
		   }
		}
		public function getSelRow($ret=array())
		{
			$ret['id']='uid';
			$ret['编号']='编号';
			$ret['状态']='有效';
			foreach($this->xml->childNodes as $Node)
			{
				if(self::isobjNode($Node,false) && substr($Node->nodeName,0,1)=='_')
				{
					foreach($Node->attributes as $k=>$v)
					{
						$conval = $Node->getAttribute($k);
						if(preg_match_all('/(?<!\$_REQUEST)(?<!\$_POST)(?<!\$_GET)([UM]?)\[(.*)\]/Uis',$conval,$trform,PREG_SET_ORDER))
						{
							foreach($trform as $val)
							{
								$ret[$val[2]]=$val[2];
							}
						}
					}
				}
			}
			return $ret;
		}
		//校验对象是否存在XML的设置，在一些校验情况下，要校验一些XML选项是否需要必填
		public function haveAttr($name)
		{
			return $this->xml->hasAttribute($name);
		}
	}
	class selRow{
		public $cache=array();
		function set($rowary){
			//根据接受到的值 赋值
			if(gettype($rowary)=="string")
			{
				$this->cache[]=$rowary;
			}
			else if(gettype($rowary)=="array")
			{
				//dump($rowary);
				//数组合并
				$this->cache=array_merge($this->cache,$rowary);
			}
			$this->cache=array_unique($this->cache);
		}
		function tostring(){
			//获取当前的对象的数值
			return join(',',$this->cache);
		}
	}
?>