<?php
/*
* 区域管理模块
*/

class AreaAction extends CommonAction 
{
	//解析现有系统的数据输出json
	public function make_json()
	{
		//$Quxian			= M('area',null);
		$area=array();
		//前缀2名字
		$id2area=array(''=>&$area);
		$all=M('area')->order('id asc,地区级别 asc')->field('id,地区编码,上级编码,地区名称,是否末级')->select();
		foreach($all as $v0){
			//得到了当前级别对应的前缀长度
			//如国家是CN,则为2，省份为CN01则为4。。。
			//$idlength=2+$v0['地区级别']*2;
			//得到了自己所属前缀的ID 中国，得到了CN
			$id=$v0['地区编码'];
			//得到上级前缀，则相当于自己前缀缩短两位
			$pid=$v0['上级编码'];
			//
			if($v0['是否末级'])
			{
				$id2area[$pid][$v0['地区名称']]="";
			}
			else
			{
				$id2area[$pid][$v0['地区名称']]=array();
			}
			$id2area[$id]=&$id2area[$pid][$v0['地区名称']];
		}
		return json_encode($area);
	}

	//过滤查询字段
	public function index()
	{
		set_time_limit(0); 
		ini_set('memory_limit','2000M');
		$data				= '';
		$default_country	= '';
		$default_province	= '';
		$default_city		= '';
		$default_county		= '';
		$default_town		= '';

		$FConfig			= M('config');

		$countrynameary		= M("area")->where(array("地区级别"=>1))->getField("地区编码,地区名称");

		$default_country			= CONFIG('default_country');//默认国家

		$default_province			= CONFIG('default_province');//默认省份/州

		$default_city				= CONFIG('default_city');//默认城市

		$default_county				= CONFIG('default_county');//默认区/县

		$default_town				= CONFIG('default_town');//默认乡镇
		
		//如果数据库中没有,这里需要初始化
		if( !isset($countrynameary) )
		{
			M()->startTrans();
			//清空表数据
			M()->execute("truncate table `area`");
			//读取默认地区
			$datastr = file_get_contents('./Public/directSell/area.json');
			$areaary=json_decode($datastr,true);
			//插入表地区数据
			$area_json_ary=$this->addarea($areaary);
			//重写json数据
			$this->write_json($area_json_ary['country_json'],$area_json_ary['area_json']);
			M()->commit();
		}
		else
		{
			$datastr = $this->make_json();
			//是否根据数据库生成json文件
			$countryfile = "../Public/directSell/country.json";
			if(!file_exists($countryfile)){
				$areaary=json_decode($datastr,true);
				$area_json_ary=$this->addarea($areaary,false);
				$this->write_json($area_json_ary['country_json'],$area_json_ary['area_json']);
			}
		}
		$this->assign('default_country',$default_country);
		$this->assign('default_province',$default_province);
		$this->assign('default_city',$default_city);
		$this->assign('default_county',$default_county);
		$this->assign('default_town',$default_town);
		$this->assign('data',$datastr);
		$this->display();
	}
	/*
	* 保存修改
	*/
	public function update()
	{
		set_time_limit(0); 
		ini_set('memory_limit','2000M');
	
		M()->startTrans();
		$FConfig			= M('config');
	
		//保存默认地区
	
		CONFIG('default_country',I("post.default_country/s"));
	
		CONFIG('default_province',I("post.default_province/s"));
	
		CONFIG('default_city',I("post.default_city/s"));
	
		CONFIG('default_county',I("post.default_county/s"));
	
		CONFIG('default_town',I("post.default_town/s"));
	
		M()->commit();
		$this->success("修改成功");
	}
	//添加国家省份
	function country_add_save(){
		M()->startTrans();
		$areaary=M("area")->where(array("地区级别"=>I("post.lv/d")))->getField("地区编码,id,上级编码,地区名称");
		//判断订国家所在
		if(I("post.lv/d")==1){
			$country=I("post.name/s");
		}else{
			$country=I("post.upname/s");
		}
		$countrykey=$this->transCountryCode($country);
		//json文件中的内容
		$countryfile = "./Public/directSell/country.json";
		$country_json_string=file_get_contents($countryfile);
		$country_json=json_decode($country_json_string,true);
		//保存到数据库
		if(I("post.lv/d")==1 && !isset($areaary[$countrykey])){
			$data["地区编码"]=$countrykey;
			$data["上级编码"]='';
			$data["地区名称"]=$country;
			$data["地区级别"]=1;
			$data["是否末级"]=0;
			M("area")->add($data);
		}
		//写入文件
		if(!isset($country_json[$countrykey])){
			$country_json[$countrykey]['name']=$country;
		}
		if(I("post.lv/d")==2 && (!isset($country_json[$countrykey]) || !array_key_exists(I("post.name/s"),$country_json[$countrykey]))){
			if(I("post.oldname/s")!=""){
				$procincekeys=array_keys($country_json[$countrykey],I("post.oldname/s"));
				$data["地区名称"]=I("post.name/s");
				M("area")->where(array("上级编码"=>$countrykey,"地区编码"=>$procincekeys['0']))->save($data);
				$country_json[$countrykey][$procincekeys['0']]=I("post.name/s");
			}else{
				$num=count($country_json[$countrykey]);
				$provincekey=$countrykey.str_pad($num,2,'0',STR_PAD_LEFT);
				$country_json[$countrykey][$provincekey]=I("post.name/s");
				$data["地区编码"]=$provincekey;
				$data["上级编码"]=$countrykey;
				$data["地区名称"]=I("post.name/s");
				$data["地区级别"]=2;
				$data["是否末级"]=0;
				M("area")->add($data);
			}
		}
		$country_json_string=strip_tags(json_encode($country_json));
		if(!file_exists($countryfile) || md5_file($countryfile)!=md5($country_json_string)){
			$fp = fopen($countryfile,"w+",true);
			fwrite($fp,$country_json_string);
			fclose($fp);
		}
		M()->commit();
		echo json_encode(array("status"=>1,"info"=>"保存完成"));
	}
	function area_add_save(){
		M()->startTrans();
		//判断上级的编码
		$countrykey=$this->transCountryCode(I("post.country/s"));
		//获取上级编码
		$upstr=$countrykey;$uplv=2;
		$upary=explode("_",I("post.upname/s"));
		foreach($upary as $upname){
			$upstr=M("area")->where(array("地区名称"=>$upname,"上级编码"=>$upstr,"地区级别"=>$uplv))->getfield("地区编码");
			$uplv++;
		}
		//获取上级信息
		$uparea=M("area")->where(array("地区名称"=>$upname,"地区编码"=>$upstr,"地区级别"=>(I("post.lv/d")-1)))->field("id,上级编码,地区名称,地区编码")->find();
		//找出文件
		$nowk=str_replace($countrykey,'',$uparea['地区编码']);
		$areafile="./Public/directSell/area".($nowk%110).".json";
		if(file_exists($areafile)){
			$area_json_string=file_get_contents($areafile);
			$area_json=json_decode($area_json_string,true);
		}
		if(I("post.oldname/s")!=""){
			//获取数据库数据
			$olddata=M("area")->where(array("上级编码"=>$uparea['地区编码'],"地区编码"=>array("like",$countrykey."%"),"地区名称"=>I("post.oldname/s"),"地区级别"=>I("post.lv/d")))->find();
			if($olddata){
				$olddata['地区名称']=I("post.name/s");
				M("area")->save($olddata);
			}else{
				echo json_encode(array("status"=>0,"info"=>"数据错误，请刷新页面"));die;
			}
		}else{
			//找出本地区已有几个下级
			$maxarea=M("area")->where(array("上级编码"=>$uparea['地区编码'],"地区编码"=>array("like",$countrykey."%"),"地区级别"=>I("post.lv/d")))->order("地区编码 desc")->find();
			$num=str_replace($maxarea["上级编码"]," ",$maxarea["地区编码"]);
			//保存编码数据
			$areakey=$uparea['地区编码'].str_pad($num+1,2,'0',STR_PAD_LEFT);
			$data["地区编码"]=$areakey;
			$data["上级编码"]=$uparea['地区编码'];
			$data["地区名称"]=I("post.name/s");
			$data["地区级别"]=I("post.lv/d");
			$data["是否末级"]=I("post.lv/d")>=5?1:0;
			M("area")->add($data);
			$area_json[$uparea['地区编码']][$areakey]=I("post.name/s");
		}
		$area_json_string=strip_tags(json_encode($area_json));
		if(!file_exists($areafile) || md5_file($areafile)!=md5($area_json_string)){
			$fp = fopen($areafile,"w+",true);
			fwrite($fp,$area_json_string);
			fclose($fp);
		}
		M()->commit();
		echo json_encode(array("status"=>1,"info"=>"保存完成"));
	}
	function area_delete(){
		set_time_limit(0); 
		ini_set('memory_limit','2000M');
		
		M()->startTrans();
		//国家编码
		$upkey="";
		if(I("post.country/s")!=""){
			$countrykey=$this->transCountryCode(I("post.country/s"));
			$upkey=$countrykey;
			//省级编码
			if(I("post.province/s")!=""){
				$provincekey=M("area")->where(array("地区名称"=>I("post.province/s"),"上级编码"=>$upkey,"地区编码"=>array("like",$upkey."%"),"地区级别"=>2))->getField("地区编码");
				$upkey=$provincekey;
				if(I("post.city/s")!=""){
					$citykey=M("area")->where(array("地区名称"=>I("post.city/s"),"上级编码"=>$upkey,"地区编码"=>array("like",$upkey."%"),"地区级别"=>3))->getField("地区编码");
					$upkey=$citykey;
					if(I("post.county/s")!=""){
						$countykey=M("area")->where(array("地区名称"=>I("post.county/s"),"上级编码"=>$upkey,"地区编码"=>$upkey,"地区级别"=>4))->getField("地区编码");
						$upkey=$countykey;
					}
				}
			}
		}else{
			$countrykey=$this->transCountryCode(I("post.name/s"));
		}
		//找出当前地区所在的编码
		$deleteid=array();
		$areadata=M("area")->where(array("上级编码"=>$upkey,"地区级别"=>I("post.lv/d"),"地区名称"=>I("post.name/s")))->find();
		$downareas=M("area")->where(array("地区编码"=>array("like",$areadata['地区编码']."%"),"地区级别"=>array("gt",I("post.lv/d"))))->getField("id keyid,id,地区名称,地区编码,上级编码,地区级别");
		//清除上级所在的json数据
		$downareas[$areadata['id']]=$areadata;
		foreach($downareas as $downarea){
			if($downarea['地区级别']>=3){
				//找出文件
                $area_json_file=null;
				$nowk=str_replace($countrykey,'',$downarea['上级编码']);
				$areafile="./Public/directSell/area".($nowk%110).".json";
				if( isset($area_json_file[$areafile]) || file_exists($areafile)){
					$area_json_string=isset($area_json_file[$areafile])?$area_json_file[$areafile]:file_get_contents($areafile);
					$area_json=json_decode($area_json_string,true);
					if(isset($area_json[$downarea['上级编码']][$downarea['地区编码']])){
						unset($area_json[$downarea['上级编码']][$downarea['地区编码']]);
					}
					$area_json_string=strip_tags(json_encode($area_json));
					$area_json_file[$areafile]=$area_json_string;
				}
			}else{
				$countryfile = "./Public/directSell/country.json";
				$country_json_string=file_get_contents($countryfile);
				$country_json=json_decode($country_json_string,true);
				if($downarea['地区级别']==1 && isset($country_json[$downarea['地区编码']])){
					unset($country_json[$downarea['地区编码']]);
				}else if(isset($country_json[$downarea['上级编码']][$downarea['地区编码']])){
					unset($country_json[$downarea['上级编码']][$downarea['地区编码']]);
				}
				$country_json_string=strip_tags(json_encode($country_json));
				$area_json_file[$countryfile]=$country_json_string;
			}
			$deleteid[]=$downarea['id'];
		}
		foreach($area_json_file as $areafile=>$area_json_string){
			$fp = fopen($areafile,"w+",true);
			fwrite($fp,$area_json_string);
			fclose($fp);
		}
		M("area")->where(array("id"=>array("in",$deleteid)))->delete();
		M()->commit();
		echo json_encode(array("status"=>1,"info"=>"删除完成"));
	}
	function addarea($areaary,$commit=true){
		$area_json=array();$country_json=array();
		//循环添加国家
		foreach($areaary as $country=>$provinceary){
			$provincei=1;
			//国家编码
			$countrykey=$this->transCountryCode($country);
			if($commit==true){
				$this->adddata($countrykey,$country,'',1,0);
			}
			//省份
			if(isset($provinceary))
			foreach($provinceary as $province=>$cityary){
				$cityi=1;
				//省份编码
				$provincekey=$countrykey.str_pad($provincei,2,'0',STR_PAD_LEFT);
				if($commit==true){
					$this->adddata($provincekey,$province,$countrykey,2,0);
				}
				//城市
				if(isset($cityary))
				foreach($cityary as $city=>$countyary){
					$countyi=1;
					//城市编码
					$citykey=$provincekey.str_pad($cityi,2,'0',STR_PAD_LEFT);
					//数据库中已存在
					if($commit==true){
						$this->adddata($citykey,$city,$provincekey,3,0);
					}
					//区县
					if(isset($countyary))
					foreach($countyary as $county=>$townary){
						$towni=1;
						//区县编码
						$countykey=$citykey.str_pad($countyi,2,'0',STR_PAD_LEFT);
						if($commit==true){
							$this->adddata($countykey,$county,$citykey,4,0);
						}
						//街道
						if(isset($townary))
						foreach($townary as $town=>$lastary){
							//街道编码
							$townkey=$countykey.str_pad($towni,2,'0',STR_PAD_LEFT);
							if($commit==true){
								$this->adddata($townkey,$town,$countykey,5,1);
							}
							//去除国家编码后的数字判断
							$nowk=str_replace($countrykey,'',$countykey);
							$areafile="./Public/directSell/area".($nowk%110).".json";
							//街道的json数据
							$area_json[$areafile][$countykey][$townkey]=$town;
							$towni++;
						}
						//去除国家编码后的数字判断
						$nowk=str_replace($countrykey,'',$citykey);
						$areafile="./Public/directSell/area".($nowk%110).".json";
						//街道的json数据
						$area_json[$areafile][$citykey][$countykey]=$county;
						$countyi++;
					}
					//去除国家编码后的数字判断
					$nowk=str_replace($countrykey,'',$provincekey);
					$areafile="./Public/directSell/area".($nowk%110).".json";
					//街道的json数据
					$area_json[$areafile][$provincekey][$citykey]=$city;
					$cityi++;
				}
				//街道的json数据
				$country_json[$countrykey][$provincekey]=$province;
				$provincei++;
			}
			//国家的json数据
			$country_json[$countrykey]['name']=$country;
		}
		//保存文件
		M("area")->bUpdate();
		return array("country_json"=>$country_json,"area_json"=>$area_json);
	}
	function adddata($areakey,$area,$upareakey,$leve=1,$lastleve=0){
		$data["地区编码"]=$areakey;
		$data["上级编码"]=$upareakey;
		$data["地区名称"]=$area;
		$data["地区级别"]=$leve;
		$data["是否末级"]=$lastleve;
		M("area")->bAdd($data);
	}
	function write_json($country_json,$area_json){
		//写入国家的省份json数据
		if(isset($country_json)){
			$countryfile = "./Public/directSell/country.json";
			$country_json_string=strip_tags(json_encode($country_json));
			if(!file_exists($countryfile) || md5_file($countryfile)!=md5($country_json_string)){
				$fp = fopen($countryfile,"w+",true);
				fwrite($fp,$country_json_string);
				fclose($fp);
			}
		}
		if(isset($area_json)){
			//写入城市以下的json数据
			foreach($area_json as $area_file=>$area_ary){
				//转换成json数据保存到文件中
				$area_json_string=strip_tags(json_encode($area_ary));
				if(!file_exists($area_file) || md5_file($area_file)!=md5($area_json_string)){
					$fp = fopen($area_file,"w+",true);
					fwrite($fp,$area_json_string);
					fclose($fp);
				}
			}
		}
		return true;
	}
	//世界范围内的国家地区编码
	function transCountryCode($code) { 
		$index=array('AA'=>'阿鲁巴','AD'=>'安道尔','AE'=>'阿联酋','AF'=>'阿富汗','AG'=>'安提瓜和巴布达','AL'=>'阿尔巴尼亚', 'AM'=>'亚美尼亚', 'AN'=>'荷属安德列斯', 'AO'=>'安哥拉', 'AQ'=>'南极洲', 'AR'=>'阿根廷', 'AS'=>'东萨摩亚', 'AT'=>'奥地利', 'AU'=>'澳大利亚', 'AZ'=>'阿塞拜疆', 'Av'=>'安圭拉岛', 'BA'=>'波黑', 'BB'=>'巴巴多斯', 'BD'=>'孟加拉', 'BE'=>'比利时', 'BF'=>'巴哈马', 'BF'=>'布基纳法索', 'BG'=>'保加利亚', 'BH'=>'巴林', 'BI'=>'布隆迪', 'BJ'=>'贝宁', 'BM'=>'百慕大', 'BN'=>'文莱布鲁萨兰', 'BO'=>'玻利维亚', 'BR'=>'巴西', 'BS'=>'巴哈马', 'BT'=>'不丹', 'BV'=>'布韦岛', 'BW'=>'博茨瓦纳', 'BY'=>'白俄罗斯', 'BZ'=>'伯里兹', 'CA'=>'加拿大', 'CB'=>'柬埔寨', 'CC'=>'可可斯群岛', 'CD'=>'刚果', 'CF'=>'中非', 'CG'=>'刚果', 'CH'=>'瑞士', 'CI'=>'象牙海岸', 'CK'=>'库克群岛', 'CL'=>'智利', 'CM'=>'喀麦隆', 'CN'=>'中国', 'CO'=>'哥伦比亚', 'CR'=>'哥斯达黎加', 'CS'=>'捷克斯洛伐克', 'CU'=>'古巴', 'CV'=>'佛得角', 'CX'=>'圣诞岛', 'CY'=>'塞普路斯', 'CZ'=>'捷克', 'DE'=>'德国', 'DJ'=>'吉布提', 'DK'=>'丹麦', 'DM'=>'多米尼加共和国', 'DO'=>'多米尼加联邦', 'DZ'=>'阿尔及利亚', 'EC'=>'厄瓜多尔', 'EE'=>'爱沙尼亚', 'EG'=>'埃及', 'EH'=>'西撒哈拉', 'ER'=>'厄立特里亚', 'ES'=>'西班牙', 'ET'=>'埃塞俄比亚', 'FI'=>'芬兰', 'FJ'=>'斐济', 'FK'=>'福兰克群岛', 'FM'=>'米克罗尼西亚', 'FO'=>'法罗群岛', 'FR'=>'法国', 'FX'=>'法国-主教区', 'GA'=>'加蓬', 'GB'=>'英国', 'GD'=>'格林纳达', 'GE'=>'格鲁吉亚', 'GF'=>'法属圭亚那', 'GH'=>'加纳', 'GI'=>'直布罗陀', 'GL'=>'格陵兰岛', 'GM'=>'冈比亚', 'GN'=>'几内亚', 'GP'=>'法属德洛普群岛', 'GQ'=>'赤道几内亚', 'GR'=>'希腊', 'GS'=>'S. Georgia and S. Sandwich Isls.', 'GT'=>'危地马拉', 'GU'=>'关岛', 'GW'=>'几内亚比绍', 'GY'=>'圭亚那', 'HK'=>'中国香港特区', 'HM'=>'赫德和麦克唐纳群岛', 'HN'=>'洪都拉斯', 'HR'=>'克罗地亚', 'HT'=>'海地', 'HU'=>'匈牙利', 'ID'=>'印度尼西亚', 'IE'=>'爱尔兰', 'IL'=>'以色列', 'IN'=>'印度', 'IO'=>'英属印度洋领地', 'IQ'=>'伊拉克', 'IR'=>'伊朗', 'IS'=>'冰岛', 'IT'=>'意大利', 'JM'=>'牙买加', 'JO'=>'约旦', 'JP'=>'日本', 'KE'=>'肯尼亚', 'KG'=>'吉尔吉斯斯坦', 'KH'=>'柬埔寨', 'KI'=>'基里巴斯', 'KM'=>'科摩罗', 'KN'=>'圣基茨和尼维斯', 'KP'=>'韩国', 'KR'=>'朝鲜', 'KW'=>'科威特', 'KY'=>'开曼群岛', 'KZ'=>'哈萨克斯坦', 'LA'=>'老挝', 'LB'=>'黎巴嫩', 'LC'=>'圣卢西亚', 'LI'=>'列支顿士登', 'LK'=>'斯里兰卡', 'LR'=>'利比里亚', 'LS'=>'莱索托', 'LT'=>'立陶宛', 'LU'=>'卢森堡', 'LV'=>'拉托维亚', 'LY'=>'利比亚', 'MA'=>'摩洛哥', 'MC'=>'摩纳哥', 'MD'=>'摩尔多瓦', 'MG'=>'马达加斯加', 'MH'=>'马绍尔群岛', 'MK'=>'马其顿', 'ML'=>'马里', 'MM'=>'缅甸', 'MN'=>'蒙古', 'MO'=>'中国澳门特区', 'MP'=>'北马里亚纳群岛', 'MQ'=>'法属马提尼克群岛', 'MR'=>'毛里塔尼亚', 'MS'=>'蒙塞拉特岛', 'MT'=>'马耳他', 'MU'=>'毛里求斯', 'MV'=>'马尔代夫', 'MW'=>'马拉维', 'MX'=>'墨西哥', 'MY'=>'马来西亚', 'MZ'=>'莫桑比克', 'NA'=>'纳米比亚', 'NC'=>'新卡里多尼亚', 'NE'=>'尼日尔', 'NF'=>'诺福克岛', 'NG'=>'尼日利亚', 'NI'=>'尼加拉瓜', 'NL'=>'荷兰', 'NO'=>'挪威', 'NP'=>'尼泊尔', 'NR'=>'瑙鲁', 'NT'=>'中立区(沙特-伊拉克间)', 'NU'=>'纽爱', 'NZ'=>'新西兰', 'OM'=>'阿曼', 'PA'=>'巴拿马', 'PE'=>'秘鲁', 'PF'=>'法属玻里尼西亚', 'PG'=>'巴布亚新几内亚', 'PH'=>'菲律宾', 'PK'=>'巴基斯坦', 'PL'=>'波兰', 'PM'=>'圣皮艾尔和密克隆群岛', 'PN'=>'皮特克恩岛', 'PR'=>'波多黎各', 'PT'=>'葡萄牙', 'PW'=>'帕劳', 'PY'=>'巴拉圭', 'QA'=>'卡塔尔', 'RE'=>'法属尼留旺岛', 'RO'=>'罗马尼亚', 'RU'=>'俄罗斯', 'RW'=>'卢旺达', 'SA'=>'沙特阿拉伯', 'SC'=>'塞舌尔', 'SD'=>'苏丹', 'SE'=>'瑞典', 'SG'=>'新加坡', 'SH'=>'圣赫勒拿', 'SI'=>'斯罗文尼亚', 'SJ'=>'斯瓦尔巴特和扬马延岛', 'SK'=>'斯洛伐克', 'SL'=>'塞拉利昂', 'SM'=>'圣马力诺', 'SN'=>'塞内加尔', 'SO'=>'索马里', 'SR'=>'苏里南', 'ST'=>'圣多美和普林西比', 'SU'=>'前苏联', 'SV'=>'萨尔瓦多', 'SY'=>'叙利亚', 'SZ'=>'斯威士兰', 'Sb'=>'所罗门群岛', 'TC'=>'特克斯和凯科斯群岛', 'TD'=>'乍得', 'TF'=>'法国南部领地', 'TG'=>'多哥', 'TH'=>'泰国', 'TJ'=>'塔吉克斯坦', 'TK'=>'托克劳群岛', 'TM'=>'土库曼斯坦', 'TN'=>'突尼斯', 'TO'=>'汤加', 'TP'=>'东帝汶', 'TR'=>'土尔其', 'TT'=>'特立尼达和多巴哥', 'TV'=>'图瓦卢', 'TW'=>'中国台湾省', 'TZ'=>'坦桑尼亚', 'UA'=>'乌克兰', 'UG'=>'乌干达', 'UK'=>'英国', 'UM'=>'美国海外领地', 'US'=>'美国', 'UY'=>'乌拉圭', 'UZ'=>'乌兹别克斯坦', 'VA'=>'梵蒂岗', 'VC'=>'圣文森特和格陵纳丁斯', 'VE'=>'委内瑞拉', 'VG'=>'英属维京群岛', 'VI'=>'美属维京群岛', 'VN'=>'越南', 'VU'=>'瓦努阿鲁', 'WF'=>'瓦里斯和福图纳群岛', 'WS'=>'西萨摩亚', 'YE'=>'也门', 'YT'=>'马约特岛', 'YU'=>'南斯拉夫', 'ZA'=>'南非', 'ZM'=>'赞比亚', 'ZR'=>'扎伊尔', 'ZW'=>'津巴布韦'); 
		$code=strtoupper($code); 
		$name=array_keys($index,$code); 
		if(isset($name[0])){
			return $name[0];
		}
		return null;
	}
	/*
	* 生成区域选择的JS文件
	*/
	/*private function build_js($data,$default_country,$default_province,$default_city,$default_county,$default_town)
	{
		$this->assign('area_data',$data);
		$this->assign('default_country',$default_country);
		$this->assign('default_province',$default_province);
		$this->assign('default_city',$default_city);
		$this->assign('default_county',$default_county);
		$this->assign('default_town',$default_town);
		$content = $this->fetch(ROOT_PATH.'/Admin/Tpl/Area/js_demo.js');
			file_put_contents(ROOT_PATH."Public/directSell/area_select.js",$content);
		$content=preg_replace('/\$\(([^\)]+)\)/','$($1,navTab.getCurrentPanel())',$content);
		file_put_contents(ROOT_PATH."Public/directSell/area_select_dwz.js",$content);
	}*/
}
?>