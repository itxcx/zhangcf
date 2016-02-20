<?php
defined('APP_NAME') || die('不要非法操作哦!');
class TableListAction extends Model{
    
    public $showAll = true;						 //是否开启查询
    
    public $setShow = array();                   // 列表显示定义
    
    public $setButton = array();                 // 操作按钮定义
    
    public $editList = true;                     // 是否开启编辑功能
    
    public $showPage = true;                     // 是否显示分页 
       
    public $numPerPage = 25;                     // 每页显示行数
    
    public $pageNumShown = 10;                   //页标数字多少个
    
    public $showSearch = true ;                  // 是否显示开启搜索
    
    public $extraSearch ='';                     // 是否显示开启搜索
    
    public $excel = true;                        // 是否开启导出excel功能
    
    public $autoLoad = true;                     // 是否开启自动加载
    
    public $showNum = false;                     // 是否显示编号列
    
    public $tl_id = 'id';
    
    public $tableWidth = '100%';			     // table列表宽度
    
    public $listLayoutH = 111;                   // 列表工具栏高度

	public $rowClick = '';						 // 行点击事件

	public $arrayData = array();				 // 数组数据

	protected $setSearch = array();              // 搜索字段定义

	public $selectSql = '';
	
	public $sumPoint = 10000;					//汇总统计分界点

	public $sumFields = '';

	public $sumResult = '';

	public $_searchstr='';

	public $hint = '';

    private $addexcel = array();

    // 获取html代码 _conformEditList
    public function getHtml($actionobj=null){
        $this->resetShow();
        if($this->excel && I("get.excel/s") == '1'){
            $this->excel($actionobj);
        }else if($this->showSearch && I("get.search/s") == '1'){
            $this->searchHtml();
        }else if(I("get._editList/s") == '1'){
            $this->editList();
        }else if(I("get._conformEditList/s") == '1'){
            $this->conformEditList();
        }else if(I("get._resetEditList/s") == '1'){
            $this->resetEditList();
        }else if(I("get._getsumList/s") == '1'){
            $this->getsumList();
        }else{
			if(count($this->arrayData)>0){
				return $this->showTableData();
			}else{
				return $this->showTable($actionobj);
			}
        }
    }
        
	public function getsumList($actionobj=null){
    	$fields = "";
		foreach($this->setShow as $show){
			if(isset($show['sum']) && $show['sum']!='' ){

				if(preg_match('/\[(.*)\]/',$show['sum'])){
					preg_match_all('/\[(.*)\]/U',$show['sum'],$matchs);
					for($i=0;$i<count($matchs[0]);$i++){
						$field = $matchs[1][$i];
						
						if(strpos($field,'.')!==false){
							$fieldArr = explode('.',$field);
							$fields .= ',sum('.$field.')'.$fieldArr[1];
						}else{
							$fields .= ',sum('.$field.')'.$field;
						}
					}
				}else{
					$field = $show['sum'];
					if(strpos($field,'.')!==false){
						$fieldArr = explode('.',$field);
						$fields .= ',sum('.$field.')'.$fieldArr[1];
					}else{
						$fields .= ',sum('.$field.')'.$field;
					}
				}
				
			}
		}
		$this->sumFields = trim($fields,',');
    	$options=$this->options;     // Model中 查询表达式参数
        //获得加入搜索条件后 新的查询表达式参数和搜索条件数
        $searchResult = $this->doSearch($this->setSearch,$options); 
    	$lineSum=array();
        $parseSum	= '<tr id="sum" target="tl_id">';
        if($this->showNum)  $lineSum[]= '<td>&nbsp;</td>';
        $i=0;
        foreach($this->setShow as $value){
            
            if($value){
                if(isset($value["sum"])){
                    $sum=$this->getSum($value["sum"],$searchResult[0]);
                    if($sum == ""){
                        $lineSum[$i] = '<td>&nbsp;</td>';
                    }else{
                        $lineSum[$i] = '<td>'.$sum.'</td>';
                    }
                }else{
                    $lineSum[$i]= '<td>&nbsp;</td>';
                }
                $i++;
            } 
        }
        foreach($lineSum as $sk=>$sv){
            if($sv=="<td>&nbsp;</td>" && $sk+1<$i && $lineSum[$sk+1] != "<td>&nbsp;</td>" ){
                $lineSum[$sk] = '<td style="text-align:right">汇总：</td>';break;
            }
        }
        foreach($lineSum as $sv){
            $parseSum	.= $sv;
        }
        $parseSum	.= '</tr>'."\r\n";
        if(empty($lineSum)){
        	echo '获取汇总失败';
        }else{
        	echo $parseSum;
        }
        die;
    }
    //  判断数据库中是否有标题数据，如果有则按照数据库中的设置显示，没有则保存到数据库
    protected function resetShow(){
        $m=M('xtable_set',' ');
        //$m->table("xtable_set");
        
        //$url=$_SERVER["PHP_SELF"];
		if(defined('GROUP_NAME')){
			$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}else{
			$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}
		$url = $actionUrl;
        $result=$m->where("地址='$url'")->find();
		$md5fun=function ($val){
            static $data='';
            if($val===null)
            {
                return $data;
            }
            $data.=$val;
        };
        array_walk_recursive($this->setShow,function ($md5value,$md5key,$md5fun){
        	if(is_object($md5value)){
        		$md5fun(get_class($md5value));
        	}
            else
            {
                $md5fun($md5value);
            }
        },$md5fun);
		$md5setShow = MD5($md5fun(null));
		if($result && $md5setShow != $result['数组MD5']){
			//echo 1111;
			M()->startTrans();
			$m->delete($result['id']);
			M()->commit();
			$result = false;
		}
        if(!$result){
            $this->setSearch=$this->setShow;
		
            $data=array("地址"=>$url,"标题"=>"","显示"=>"","排序"=>"");
            $i=0;
            foreach($this->setShow as $showkey=>$showval){
					
                $data["标题"].=",".$showkey;
                if(isset($showval["hide"])){
                    $data["显示"].=",0";
                    $this->setShow[$showkey]=false;
                }else{
                    $data["显示"].=",1";
                }
                $data["排序"].=",".$i;
                $i++;
            }
            $data["标题"]=trim($data["标题"],",");
            $data["显示"]=trim($data["显示"],",");
            $data["排序"]=trim($data["排序"],",");
			$data['数组MD5'] = $md5setShow;
            $m->add($data);
        }else{
            $titleList=explode(',',$result["标题"]);
            $showList =explode(',',$result["显示"]);
            $sortList =explode(',',$result["排序"]);
            $i=0;$j=0;$show1=array();$setSearch=array();
            foreach($this->setShow as $skey=>$sval){
                if($showList[$i]=="1"){
                    $show1[$titleList[$i]]=$sval;
                }else{
                    $show1[$titleList[$i]]=false;
                }
                $setSearch[$titleList[$i]]=$sval;
                $i++;
            }
            $this->setShow=$this->sortByArray($show1,$sortList);
            $s1=array();$s2=array();
            foreach($this->setShow as $key=>$value){
                if($value){
                    $s1[$key]=$value;
                }else{
                    $s2[$key]=$value;
                }
            }
            $this->setShow=array_merge($s1,$s2);
            $this->setSearch=$this->sortByArray($setSearch,$sortList);
        }
    }
	// 添加excel导出选项
	function addExcel($title,$url){
		$this->addexcel[$title] = $url;
	}
    // 搜索数组处理
    function setSearch(){
		$setSearch = array();
        foreach($this->setShow as $key => $val){
            if(isset($val["searchMode"])){
                $setSearch[$key] = $val;
            }
       }
       return $setSearch;
    }
    public function shiftWhere($idstr){
    	//获取tablelist 的页面列表字段
    	$searchArray=$this->setSearch();
    	//获取对应的内容  以及主键值
    	if(!isset($searchArray[$idstr]))
    		return ;
    	$skey=$idstr;
    	$svalue=$searchArray[$idstr];
    	//数据在数组中排列数
    	$keyArray=array_keys($searchArray);
    	$i=array_search($idstr,$keyArray);
    	//循环REQUEST的表单数据
        foreach(I("request./a") as $key=>$value){
            if($value == "" || substr($key,0,7) != '_search'){
            	continue;
            }
            if(substr($key,-4)=='mohu'){continue;}
            $k = substr($key,7,2);
            $k = intval(trim($k,"_"));
            if($i == $k){
            	 //对空格做处理
            	 $value = trim($value);
                if($svalue["searchMode"] == 'date'){
                    $value=strtotime($value);
                    if(!$value){
                    	$value=0;
                    }
                    if(substr($key,-3)=='end'){
                        $value = $value + 24*60*60 - 1;
                    }
                }
                if(isset($svalue["searchSelect"])){
                    if(!is_numeric($value)){
                        $value = $svalue["searchSelect"][$value];
                    }
                }
                $row = isset($svalue["searchRow"]) ? $svalue["searchRow"] : $svalue["row"] ;
                $row = preg_replace("/.*\[/","",$row);
                $row = preg_replace("/\].*/","",$row);
                $rowarray=explode('.',$row);
                if(count($rowarray)>1){
                	$row=$rowarray[1];
                }
                if(isset($svalue['searchSql'])){
					$stringWhere = str_replace('[*]',$value,$svalue['searchSql']);
					$whereArr['_string'] = $stringWhere;
                    $whereStr .=  $stringWhere." AND ";
				}else if(substr($key,-5)=='start'){
					$whereStr .=  $row . ">=" . $value . " AND ";
                    if(I("request.".substr($key,0,-5)."end/s") == ''){
						$whereArr[$row] = array("egt",$value);
					}else{
						$whereArr[$row] = array('between',array($value));
					}
                }else if(substr($key,-3)=='end'){
					if(!isset($whereArr[$row])){
						$whereArr[$row] = array("elt",$value);
					}else{
						array_push($whereArr[$row][1],$value);
					}
                    $whereStr .=  $row . "<=" . $value . " AND ";
                    
                }else if(substr($key,-5)=='other'){
                    if(strstr($value,',')){
                        $value=str_replace(",","','",$value);
                        $whereArr[$row] = array("in",$value);
                        $whereStr .=  " IN('$value')";
                    }else{
                        $mohu="";
                        foreach(I("request./a") as $key1=>$value1){
                            if($key1 == substr($key,0,strlen($key)-5).'mohu'){
                                $mohu = " LIKE '%$value%' AND ";
                                $arrmohu = array('like',"%$value%");
                            }
                        }
                        if($mohu==""){
                            $whereArr[$row] = array('eq',$value);
                            $whereStr .=  $row . "='$value' AND ";
                            
                        }else{
                            $whereArr[$row] = $arrmohu;
                            $whereStr .=  $row . $mohu;
                        }
                    }
                }
				if(!isset($svalue['searchShow']) || $svalue['searchShow']){
					$this->setShow[$skey] =    $svalue;
				}
            }
        }
        if(isset($options["where"]) && is_array($options["where"])){
        	$where=$options["where"];
        	if(isset($where['_logic']) and $where['_logic']=='or')
        	{
        		$whereArr['_complex']=$where;
        		$options["where"] = $whereArr;
        	}
        	else
        	{
        		$options["where"] = array_merge($options["where"],$whereArr);
        	}
        }else{
            $options['where'] =trim($whereStr,'AND ');
        }
        $options['where']=trim($options['where'],'AND ');
        $this->setShow[$idstr]['select']=false;
    	return $options['where'];
    }
    //  添加列表显示列 
    public function addShow($string,$array){
        $this->setShow[$string]=$array;
    }
    //  table显示条件、搜索条件等处理
    protected function showTable($actionobj){
        //  排序
        if(I("post._order/s") !="" && I("post._sort/s") != ""){
            $this->order(I("post._order/s")." ".I("post._sort/s"));
        }
        $options=$this->options;                                        // Model中 查询表达式参数
        //获得加入搜索条件后 新的查询表达式参数和搜索条件数
        $searchResult = $this->doSearch($this->setSearch,$options); 
        $this->options = $searchResult[0];                              //  带搜索条件的查询表达式参数
		if(I("post.searchExcel/s") == '1'){
			$this->excel();
		}
        $con = $searchResult[1];                                        //  搜索条件总数
        $selectSql  = $this->select(false);                             //  列表总条数
        //使用正则.对查询语句进行处理.得到
		$this->selectSql = $selectSql;
		$fields = "";
		foreach($this->setShow as $show){
			if(isset($show['sum']) && $show['sum']!='' ){
				if(preg_match('/\[(.*)\]/',$show['sum'])){
					preg_match_all('/\[(.*)\]/U',$show['sum'],$matchs);
					for($i=0;$i<count($matchs[0]);$i++){
						$field = $matchs[1][$i];
						
						if(strpos($field,'.')!==false){
							$fieldArr = explode('.',$field);
							$fields .= ',sum('.$field.')'.$fieldArr[1];
						}else{
							$fields .= ',sum('.$field.')'.$field;
						}
					}
				}else{
					$field = $show['sum'];
					if(strpos($field,'.')!==false){
						$fieldArr = explode('.',$field);
						$fields .= ',sum('.$field.')'.$fieldArr[1];
					}else{
						$fields .= ',sum('.$field.')'.$field;
					}
				}
				
			}
		}
		//判断打开页面是否袭击显示内容
		$search=false;
		if($this->showAll==false){
			foreach(I("request./a") as $rfkey=>$rfval){
				if(substr($rfkey,0,7) == '_search'){
					$search=true;
					break;
				}
			}
		}
		if($search || $this->showAll){
			if($fields!='')
			{
				$fields=trim($fields,',');
				$this->options = $searchResult[0];
				$this->sumFields = $fields;
				//dump($fields);
				//$this->field($fields);
				//$this->sumResult = $this->find();
			}
			
            $p = I("request.p/d",1);
            $firstRow = ($p-1) * $this->numPerPage; 
            $this-> options = $searchResult[0];
            if($this->showPage){
            	$listsql = $this->limit($firstRow.','.$this->numPerPage)->select(false);
            	///////////判定是否使用SQL_CALC_FOUND_ROWS///////////////
            	$usindex=false;
            	$rss = M()->query("EXPLAIN ".$listsql);
            	foreach($rss as $item){
            		if(strpos($item['Extra'],'Using index')!==false)$usindex=true;
            	}
            	////////////////////////////////////////////////////////
            	if($usindex){
	            	$listsql='( SELECT SQL_CALC_FOUND_ROWS'.substr($listsql,8);
	                $list = M()->query($listsql);
	                $numdata = M()->query('select FOUND_ROWS() num');
            	}else{
	                $list = M()->query($listsql);
	                $countsql='select count(1) num ';
	                if(stripos($listsql,"ORDER ")){
	                	$countsql.=" ".substr($listsql,stripos($listsql,"from"),stripos($listsql,"ORDER ")-stripos($listsql,"from"));
	                }elseif(stripos($listsql,"limit")){
	                	$countsql.=" ".substr($listsql,stripos($listsql,"from"),stripos($listsql,"limit")-stripos($listsql,"from"));
	                }else{
	                	$countsql.=" ".substr($listsql,stripos($listsql,"from"));
	                }
	                $numdata = M()->query($countsql);
                }
                $count=$numdata[0]['num'];
                /*
	                SQL_CALC_FOUND_ROWS性能,在进行复杂查询时,要比COUNT高
	                但是如果是无条件.查询记录比较多的情况下COUNT的效率要比SQL_CALC_FOUND_ROWS高
	                TABLELIST要能够根据目前情况自动选择使用COUNT或者SQL_CALC_FOUND_ROWS.
                */
            }else{
                $list = $this->select();
                $count= count($list);
            }
        }else{
        	$count=0;
        }
        if(I("request.loadNextPage/d")==1){
            $tableResult = json_encode($this->getTbodyData($list,$count,$searchResult[0]));
            echo $tableResult;
            exit();
        }else{
            $tableResult=$this->tableList(1,$count,$firstRow,$list,$actionobj,$searchResult[0]);   //  调用table列表生成
        }
        return $tableResult;
    }

	public function showTableData(){

		$arrayData = $this ->arrayData;
		
		$count = count($arrayData);
		$p = I("request.p/d")>=1?I("request.p/d"):1;
        $firstRow = ($p-1) * $this->numPerPage;
		if($this->showPage){
			$arrayData = array_slice($arrayData,$firstRow,$this->numPerPage);
		}
		if(I("request.loadNextPage/d")==1){
			$tableResult = json_encode($this->getTbodyData($arrayData,$count,$searchResult[0]));
			echo $tableResult;
			exit();
		}else{
			$tableResult=$this->tableList($con,$count,$firstRow,$arrayData);   //  调用table列表生成
		}
		return $tableResult;
		
	}
    // table 组件
    public function tableList($con,$count,$firstRow,$list,$actionobj=null,$options1=null){
        $title      = $this->title;                         //显示标题名称
        $search     = $this->setSearch;                     //搜索框列表 
        $datasource = $list;                                //数据资源
        //$show       = $this->setShow;                     //显示列表项
        $firstRow   =$firstRow?$firstRow:0;                 //当前页第一个编号
        $p=I("request.p/d")>=1?I("request.p/d"):1;        //当前页数
        $setButton  = $this->setButton;                     //操作按钮
        $totalpage = ceil($count/$this->numPerPage);        //总页数
        //计算表格的列数
        $i=0;
        foreach($this->setShow as $value){
            if($value){$i++;}
        }
        $colNum     = $i;
        if($this->showNum)  $colNum++;
		if(defined('GROUP_NAME')){
			$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}else{
			$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}
        $parseStr  = '<form id="pagerForm" action="'.$actionUrl.'" method="post">'."\r\n";
	    $parseStr  .= '<input type="hidden" name="p" value="'.$p.'"/>'."\r\n";
	    $parseStr  .= '<input type="hidden" name="_order" value="'.I("request._order/s").'"/>'."\r\n";
	    $parseStr  .= '<input type="hidden" name="_sort" value="'.I("request._sort/s").'"/>'."\r\n";
        $_search = "";$searchGet = array();
		foreach($search as $kk=>$vv){
			if(isset($vv['searchGet']) && (isset($vv['searchGetStart']) || isset($vv['searchGetEnd']))){
				$searchGet[] = $vv['searchGet'];
			}
		}
		$searchCondition = '';$_searchstr='';
        foreach(array_merge(I("post."),I("get.")) as $key => $val){
            if($val !="" && $key !="p" && $key !="_order" && $key !="_sort" && $key != "_" && $key !="_URL_" && substr($key,0,7) != '_search' && !in_array($key,$searchGet)){
                $parseStr  .= '<input type="hidden" name="'.$key.'" value="'.trim($val).'"/>'."\r\n";
				if(substr($key,0,7) != '_search'){
                	$_search .= "/$key/".urlencode(trim($val));
				}
            }else if( (substr($key,0,7) == '_search'|| $key =="_order"|| $key =="_sort") && $val !=""){
            	if($key !="_order"&& $key !="_sort"){
                	$parseStr  .= '<input type="hidden" name="'.$key.'" value="'.trim($val).'"/>'."\r\n";
                }
				$searchCondition .= "/$key/".urlencode(trim($val));
			}
			if(!is_array($val)){
				$_searchstr.="&".$key."=".$val;
			}
        }
        $parseStr  .= '</form>'."\r\n";
        /************************头部 查询、检索**********************/
        $parseStr  .= '<div class="pageHeader">'."\r\n";
        $parseStr  .= '<form rel="pagerForm" onsubmit="return navTabDialogSearchXs(this);" action="'.$actionUrl.$_search.'" method="post">';
        $parseStr  .= '<div class="searchBar">';
        $topSearch = "";$i=0;
        foreach($search as $key=>$value){
            if(isset($value["searchPosition"]) && $value["searchPosition"] == "top"){
                if($value["searchMode"]=="date" ){
                    $topSearch  .= '<li><label>'.$key.'：</label><input type="text" name="_search'.$i.'_start" class="date" style="width:90px" value="'.I("request._search".$i."_start/s").'"> - <input style="width:90px" type="text" name="_search'.$i.'_end" class="date" value="'.I("request._search".$i."_end/s").'"></li>';  
                   
                }else if(isset($value["searchMode"]) && $value["searchMode"]=="num"){
                    if(isset($value["searchSelect"])){
                        $topSearch  .= '<li><label>'.$key.'：</label>';
                        $topSearch  .= '<select name="_search'.$i.'_start"><option value="">请选择</option>';
                        foreach($value["searchSelect"] as $selectkey => $selectvalue){
                            if(I("request._search".$i."_start/s")!="" && I("request._search".$i."_start/s")==$selectvalue){
                                $topSearch .='<option value="'.$selectvalue.'" selected>'.$selectkey.'</option>';
                            }else{
                                $topSearch .='<option value="'.$selectvalue.'">'.$selectkey.'</option>';
                            }
                        }
                        $topSearch  .= "</select> - ";
                        //$topSearch  .= "";
                        $topSearch  .= '<select name="_search'.$i.'_end"><option value="">请选择</option>';
                        foreach($value["searchSelect"] as $selectkey => $selectvalue){
                            if(I("request._search".$i."_end/s")!=""&& I("request._search".$i."_end/s")==$selectvalue){
                                $topSearch .='<option value="'.$selectvalue.'" selected>'.$selectkey.'</option>';
                            }else{
                                $topSearch .='<option value="'.$selectvalue.'">'.$selectkey.'</option>';
                            }
                        }
                        $topSearch  .= "</select>";
                        
                    }else{
                        $topSearch  .= '<li><label>'.$key.'：</label><input style="width:90px" type="text" name="_search'.$i.'_start" value="'.I("request._search".$i."_start/s").'"> - <input style="width:90px" type="text" name="_search'.$i.'_end" value="'.I("request._search".$i."_end/s").'"></li>'."\r\n";  
                    }
                }else if(isset($value['searchMode']) && $value["searchMode"]=="text"){
					$inputval = '';
                    if(false and isset($value['searchGet'])){
						$inputName = $value['searchGet'];
						if(I("get.".$inputName."/s")){
							$isutf8 = preg_match('/^.*$/u', I("get.".$inputName."/d")) > 0;
							if(!$isutf8){
								$inputval =  iconv('GB2312','UTF-8',I("get.".$inputName."/d"));
							}else{
								$inputval = I("get.".$inputName."/d");
							}
						}
					}else{
						$inputName = '_search'.$i.'_other';
						$inputMohu = '_search'.$i.'_mohu';
						if(I("request.".$inputName."/s")){
							$inputval = trim(I("request.".$inputName."/s"));
						}
					}
                    if(isset($value['searchSelect'])){
                        $topSearch  .= '<li><label>'.$key.'：</label>';
                        $topSearch  .= '<select class="combox" name="'.$inputName.'"><option value="">请选择</option>';
                        foreach($value["searchSelect"] as $selectkey => $selectvalue){
                            if(I("get.".$inputName."/s")!=""&& I("get.".$inputName."/s")==$selectvalue){
                                $topSearch .='<option value="'.$selectvalue.'" selected>'.$selectkey.'</option>';
                            }else{
                                $topSearch .='<option value="'.$selectvalue.'">'.$selectkey.'</option>';
                            }
                        }
                        $topSearch  .= "</select>";
                    }else{
						if(isset($value["searchFuzzy"])){
							if(urldecode(I("get._search".$i."_mohu/s"))){$mohu=checked;}else{$mohu="";}
							$topSearch  .= '<li><label>'.$key.'：</label><input style="width:90px" type="text" name="'.$inputName.'"  value="'.$inputval.'"><input type="checkbox" name="_search'.$i.'_mohu" style="background-color:#FFF;border:0;width:30px" '.$mohu.' />模糊</nobr></td></li>';
						}else{
							$topSearch  .= '<li><label>'.$key.'：</label><input style="width:90px" type="text" name="'.$inputName.'"  value="'.$inputval.'"></li>';
						}
                    }
                }
            }
            $i++;
        }
        if($this->extraSearch!=''){
        	$topSearch.=$this->extraSearch;
        }
        if($topSearch!=""){
            $topSearch  = '<ul class="searchContent" style="float:left">'.$topSearch.'</ul>';
        }
        if($this->showSearch || $this->editList){
            $parseStr  .= '<div class="subBar">'.$topSearch.'<ul>';
            if($topSearch!=""){
                $parseStr  .= '<li><div class="buttonActive"><div class="buttonContent"><button type="submit">查询</button></div></div></li>';
            }
            if($this->showSearch && count($this->setSearch())>0){//必须是豪华版的才能享有高级检索
				$searchHeight = count($this->setShow)*14 + 220;
				$parseStr  .= '<li><a class="button xsSearchButton" href="javascript:getSearchPageData(\''.$searchHeight.'\',\''.$actionUrl.'/search/1'.$_search.urlencode($searchCondition).'\')"><span>高级检索</span></a></li>';
            }
            if($this->editList){
                $parseStr  .= '<li><a class="button" href="'.$actionUrl.'/_editList/1" target="dialog" mask="true" title="编辑列表" width="320" height="360"  rel="editListXs"><span>编辑</span></a></li>';
            }
            if($this->autoLoad){
                $parseStr  .= '<li><a class="button" href="javascript:reloadXs(\''.$actionUrl.$_searchstr.'\')"><span>刷新</span></a></li>';
            }
            $parseStr  .= '</ul></div>';
        }
        $parseStr  .= '</div></form>';
        $parseStr  .= '</div>';
        $parseStr  .= '<div class="pageContent">'."\r\n";
        
        /************************操作按钮**********************/

		$excelStr = "";
		
		$excelOptions =urlencode(base64_encode(serialize($options1))); 
		
		$excelWhere =urlencode(base64_encode(serialize($options1["where"]))); 
		 
		$parseStr  .= '<div class="panelBar"><ul class="toolBar">'."\r\n";
        if($this->setButton){
            
            foreach($this->setButton as $k=>$v){
                $toolBarStr = "";
                /*------------------------------------
                	对rel自动赋值,rel属性表示DWZ中打开窗口的唯一标示符，如果打开同一个rel的窗口，则会相互覆盖
                	判断要是准备打开navTab或dialog窗口，同时也有href链接，但是没有设置rel属性，则根据href自动赋予
                -------------------------------------*/
                if(isset($v['target']) && isset($v['href']) && !isset($v['rel']) && ($v['target'] == 'navTab' || $v['target'] == 'dialog'))
                {
                	$v['rel']=md5($v['href']);
                }
				if(isset($v['_where']) && $v['_where'] == true){
					$v['href'] .= '/_where/'.$excelWhere;
				}
				$spanstyle='';
                foreach($v as $key=>$val){
					if($key !== '_where' and $key!=='icon' and ($key != 'class' || !isset($v['icon']))){
						$toolBarStr .= $key.'="'.$val.'" ';
					}
					if($key=='icon')
					{
						$spanstyle='style="background: url(\''.$val.'\') no-repeat 2px 3px"';
					}
                }
                $parseStr  .= '<li><a '.$toolBarStr.' ><span '.$spanstyle.'>'.$k.'</span></a></li>'."\r\n";
            }
        }
        if($this->excel ){
        	$excelstyle='style="background: url(\'/Public/Images/ExtJSicons/page/page_excel.png\') no-repeat 2px 3px"';
            if(count($this->addexcel)==0){
				if(strlen($actionUrl.'/excel/1'.$_search.'/_where/'.$excelWhere) <= 200){
					$parseStr  .= '<li><a class="icon"  target="dwzExport" href="'.$actionUrl.'/excel/1'.$_search.'/_where/'.$excelWhere.'"><span '.$excelstyle.'>导出EXCEL</span></a></li>'."\r\n";
				}else{
					$excelWhere =base64_encode(serialize($options1["where"])); 
					$parseStr  .= '<li><a class="icon" target="dwzExport" href="#"  onclick="$(\'#_whereForm\',navTab.getCurrentPanel()).submit();return false"><span '.$excelstyle.'>导出EXCEL</span></a></li>'."\r\n";
					$parseStr  .='<form action="'.$actionUrl.'/excel/1'.$_search.'" method="post" id="_whereForm"><input type="hidden" name="_where" value="'.$excelWhere.'"></form>';
				}
            }else{
                $href = $actionUrl.'/excel/1'.$_search.'/_where/'.$excelWhere;
				if(strlen($href) <= 200){
					$excelStr .= '<div class="selectListDiv" style="background-color:#FFF;width:100px;border:1px solid #B8D0D6;display:none;position:absolute;z-index:200"><ul style="padding:5px"><a href="'.$href.'" style="text-decoration:none"><li  style="padding:5px 5px 5px 25px;background:url(__PUBLIC__/Images/excel.jpg) no-repeat scroll 0 2px;">普通</li></a>';
					foreach($this->addexcel as $k=>$v){
						$excelStr .= '<a href="'.$v["url"].'/_where/'.$excelWhere.'" style="text-decoration:none"><li style="padding:5px 5px 5px 25px;background:'.$v["background"].'">'.$k.'</li></a>';
					}
					$excelStr .= '</ul></div>';
					$parseStr  .= '<li><a class="icon" target="dwzExport" href="#"><span onclick="location.href=\''.$href.'\'">导出EXCEL</span><span class="panelBarSel" onmouseover="$(this).css(\'background\',\'url(/Public/dwz/themes/default/images/selectButRight1.gif) no-repeat scroll 3px 7px\')" onmouseout="$(this).css(\'background\',\'url(/Public/dwz/themes/default/images/selectButRight.gif) no-repeat scroll 3px 7px\')" onclick="selectList(this,event)"></span></a></li>'."\r\n";
				}else{
					$excelWhere =base64_encode(serialize($options1["where"])); 
					$excelStr .= '<div class="selectListDiv" style="background-color:#FFF;width:100px;border:1px solid #B8D0D6;display:none;position:absolute;z-index:200"><ul style="padding:5px"><a href="#"  onclick="$(\'#_whereForm\').submit();return false" style="text-decoration:none"><li  style="padding:5px 5px 5px 25px;background:url(__PUBLIC__/Images/excel.jpg) no-repeat scroll 0 2px;">普通</li></a>';
					$excelStr  .='<form action="'.$actionUrl.'/excel/1'.$_search.'" method="post" id="_whereForm"><input type="hidden" name="_where" value="'.$excelWhere.'"></form>';
					foreach($this->addexcel as $k=>$v){
						$excelStr .= '<a href="#" onclick="$(\'#_whereForm'.$k.'\').submit();return false" style="text-decoration:none"><li style="padding:5px 5px 5px 25px;background:'.$v["background"].'">'.$k.'</li></a>';
						$excelStr  .='<form action="'.$v["url"].'" method="post" id="_whereForm'.$k.'"><input type="hidden" name="_where" value="'.$excelWhere.'"></form>';
					}
					$excelStr .= '</ul></div>';
					$parseStr  .= '<li><a class="icon" target="dwzExport" href="#"><span onclick="$(\'#_whereForm\').submit();">导出EXCEL</span><span class="panelBarSel" onmouseover="$(this).css(\'background\',\'url(/Public/dwz/themes/default/images/selectButRight1.gif) no-repeat scroll 3px 7px\')" onmouseout="$(this).css(\'background\',\'url(/Public/dwz/themes/default/images/selectButRight.gif) no-repeat scroll 3px 7px\')" onclick="selectList(this,event)"></span></a></li>'."\r\n";
					
				}
                
            }
        }
        
        $parseStr .= "</ul>";
        if($this->hint != '')
        {
        	$parseStr .= "<span style=\"background: url('/Public/Images/ExtJSicons/information.png') no-repeat 2px 3px;float:right;color:#15428B;padding: 5px 10px 5px 25px;\">".$this->hint."</span>";
        }
        $parseStr .= "</div>".$excelStr."\r\n";
         
        /*********************************列表内容*******************************/
        $parseStr  .= '<table class="table" width="'.$this->tableWidth.'" layoutH="'.$this->listLayoutH.'">'."\r\n";
        $parseStr  .= '<thead >'."\r\n";
        $parseStr  .= '<tr>'."\r\n";
        if($this->showNum) {
            $parseStr .= '<th style="width:30px">序号</th>'."\r\n";
        }
        if(isset($options1["order"])){
			$_order = explode(" ",$options1["order"]);
			$_order[1] = isset($_order[1])? strtolower($_order[1]):"asc";
        }
        foreach($this->setShow as $showtitle=>$showval) {
			if(!$showval){continue;}
			if(isset($showval["css"])){
				preg_match("/width\:(.*)\;/U",$showval["css"].";",$thwidth);
			}else{
				if(isset($showval['format']))
				{
					switch ($showval['format'])
					{
						case 'time':
							$thwidth[0]='width:120px';
						break;
						case 'date':
							$thwidth[0]='width:60px';
						break;
						case 'bool':
							$thwidth[0]='width:20px';
						break;
					}
				}
				else
				{
					$thwidth[0]='width:'.(strlen($showtitle)*5).'px';
				}
			}
			if(isset($showval["order"])){
				$orderClass = "";
				$showvalOrder = trim(trim($showval["order"],"["),"]");
				if(isset($_order[0]) && $_order[0] == $showvalOrder){
					$orderClass = 'class="'.$_order[1].'"';
				}
				$parseStr .= '<th style="color:#316EDA;'.$thwidth[0].'" orderField="'.$showvalOrder.'" '.$orderClass.'>';
		   }else{
               $parseStr .= '<th style="'.$thwidth[0].'">';
           }
           
           $parseStr .= $showtitle.'</th>'."\r\n";
        }
        $parseStr .= '</tr></thead><tbody class="xtabletbody">'."\r\n";
		
        //dump($dispdata);
        $parseStr   .= '</tbody></table>'."\r\n";
        if($this->showPage){
            $parseStr   .= '<div class="panelBar" id="panelBar">';
            $parseStr   .= '<div class="pages"><span>共'.$count.'条记录</span></div>';
    		$parseStr   .= '<div class="pagination" rel="panelBar" targetType="navTab" totalCount="'.$count.'" numPerPage="'.$this->numPerPage.'" pageNumShown="'.$this->pageNumShown.'" currentPage="'.$p.'"></div>';
    		$parseStr   .= '</div>';
        }
    	$parseStr   .= '</div>';
        $parseStr   .= '<div class="searchForm" rel="pagerForm"><input type="hidden" name="name" value="xxx" />

</div>';
        
        $request = array();
        foreach(array_merge(I("post."),I("get.")) as $rk=>$req){
            if($req !="")
            $request[$rk] = $req;
        }
        $request["loadNextPage"] = 1;
        $dispdata = $this->getTbodyData($datasource,$count,$options1,$colNum);
        
        if($this->autoLoad){
            $autoLoad = 1;
        }else{
            $autoLoad = 0;
        }
		//$parseStr   .='<script>window.onload=displine("'.json_encode($dispdata).'","'.$pagenum.'","'.json_encode($request).'","'.$actionUrl.'");</script>';
        $parseStr   .='<script>$(function(){displine('.json_encode($dispdata).',"'.$p.'",'.json_encode($request).',"'.$actionUrl.'","'.$totalpage.'",'.$autoLoad.',"'.$actionUrl.'/search/1'.$_search.urlencode($searchCondition).'");resetpage("'.$count.'","'.$this->numPerPage.'","'.$this->pageNumShown.'","'.$p.'",'.$autoLoad.');});</script>';
        return $parseStr;
    }
    
    
    
    // 获取列表tbody 的json数据
    public function getTbodyData($datasource,$count,$options1,$colNum=1){
        $dispdata =array();
		//json_encode
		if(isset($options1['order']) && strstr($options1['order'],'desc')) 
    		$sort="desc";
    	else 
    		$sort="asc";
    	if(I("post._sort/s")!="") $sort=I("post._sort/s");
    	$p=I("request.p/s")>0?I("request.p/s"):'1';//页数
        $firstRow=($p-1)*$this->numPerPage;
		//遍历行数据
        foreach($datasource as $i=>$dataval){
			
			$linedata=array();
            //$parseStr .= '<tr target="tl_id" rel="'.$dataval['id'].'">'."\r\n";
            if($this->showNum) {//序号的计算规则
                if($sort=="asc")
                    $linedata[] = $i+$firstRow+1;
                else    
                    $linedata[] = $count-$firstRow-$i;
            }
            //显示定义的列表字段
            foreach($this->setShow as $showkey=>$showval) {
                if(!$showval){continue;}
                //$parseStr   .=  '<td style="'.$showval["css"].'">';
                if(is_string($showval["row"]) && strpos($showval["row"],'detailView(')===false && preg_match("/\(.*\)/",$showval["row"])){
                    $showval["row"]=str_replace('"[','[',$showval["row"]);
                    $showval["row"]=str_replace(']"',']',$showval["row"]);
                    $showval["row"]=str_replace('[','$dataval["',$showval["row"]);
                    $showval["row"]=str_replace(']','"]',$showval["row"]);
                    
                    $showval["row"]=str_replace('$this','$actionobj',$showval["row"]);
                    eval('$showrow='.$showval["row"].';');
                }else if(is_array($showval["row"])){
                    $value=array();$i=0;
                    foreach($showval["row"] as $val){
                        if($i>0){
                            preg_match_all('/\[(.*)\]/U',$val,$matchs);
                            for($j=0;$j<count($matchs[0]);$j++){
                                $str1=$matchs[0][$j];
                                $str2=$matchs[1][$j];
								if(!is_numeric($str2)){
									$val=str_replace($str1,$dataval[$str2],$val);
								}
                            }
                            if($val=='$dataval'){
                                $val = $dataval;
                            }
                            $value[($i-1)]=$val;
                        }
                        $i++;
                    }
                    $showrow= call_user_func_array($showval["row"][0],$value);
                    
                }else{
                    $showrow=$showval["row"];
                    preg_match_all('/\[(.*)\]/U',$showrow,$matchs);
                    for($i=0;$i<count($matchs[0]);$i++){
                        $str1=$matchs[0][$i];
                        $str2=$matchs[1][$i];
						if(!is_numeric($str2)){
							$showrow=str_replace($str1,$dataval[$str2],$showrow);
						}
                    }
                }
                if(isset($showval["format"]) && $showval["format"]=="bool"){
                    if((bool)$showrow){
                        $showrow='<img src="/Public/Images/ExtJSicons/arrow/accept.png" border="0">';
                    }else{
                        $showrow='<img src="/Public/Images/ExtJSicons/delete.png" border="0"/>';
                    }
                }else if(isset($showval["format"]) && $showval["format"]=="time"){
                    $showrow= !$showrow ? '' : date('Y-m-d H:i:s',$showrow);
                }else if(isset($showval["format"]) &&$showval["format"]=="date"){
                    $showrow= !$showrow ? '' : date('Y-m-d',$showrow);
                }
                if(isset($showval["url"])){
                    preg_match_all('/\[(.*)\]/U',$showval["url"],$matchs);
                    $showurl=$showval["url"];
                    for($i=0;$i<count($matchs[0]);$i++){
                        $str1=$matchs[0][$i];
                        $str2=$matchs[1][$i];
						if(!is_numeric($str2)){
							$showurl=str_replace($str1,$dataval[$str2],$showurl);
						}
                    }
                    //if($page){
                    //    $showurl=$showurl.'/page/'.$p;
                    //}
                    $showrow = '<a href="'.$showurl.'" target="'.$showval["target"].'" '.$showval["urlAttr"].'>'.$showrow.'</a>';
                }else{
                    if($showrow==""){$showrow="&nbsp;";}
                    
                }   
				$linedata[]=$showrow;
            }
			$linedatas = array();
			$linedatas[] = $linedata;
			// tr 事件绑定
			$rowClick = $this->rowClick;
			if($rowClick != ""){
				preg_match_all('/\[(.*)\]/U',$rowClick,$matchs);
				for($i=0;$i<count($matchs[0]);$i++){
					$str1=$matchs[0][$i];
					$str2=$matchs[1][$i];
					if(!is_numeric($str2)){
						$rowClick=str_replace($str1,$dataval[$str2],$rowClick);
					}
				}
				$linedatas[] = $rowClick;
			}
            $tl_id = $this->tl_id;
            $dispdata[]=array($dataval[$tl_id]=>$linedatas);
            
            //$parseStr .= '<td>'.$showrow.'</td>'."\r\n";
        }
        if($count == 0){
            $dispdata["nodata"] = '<tr><td colspan="'.$colNum.'">[没有查找到任何数据]</td></tr>';
            
            return $dispdata;
        }
        $i=0;
        foreach($this->setShow as $value){
            if(isset($value["sum"])){
                $i++;
            }
        }
        if($i>0 && $count>0){
        	if(defined('GROUP_NAME')){
				$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
			}else{
				$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
			}
            $lineSum=array();
            //$parseSum	= '<tr id="sum" target="tl_id">';
            if($this->showNum)  $lineSum[]= '&nbsp;';
            $i=0;$m=0;
            foreach($this->setShow as $value){
                
                if($value){
                    if(isset($value["sum"])){
                    	$sum="";
                    	if($count<=$this->sumPoint){//查询结果小于10000条自动汇总
                        	$sum=$this->getSum($value["sum"],$options1);
                        }else{
                        	if($m==0)$sum='<a style="cursor:pointer;color:#316eda;" onclick="getsumList(\''.$actionUrl.'/args/'.I("request.args/s").'/_getsumList/1\')"><span>刷新</span></a>';
                        }
                        //$sum=$this->getSum($value["sum"]);
                        if($sum == ""){
                            $lineSum[$i] = '&nbsp;';
                        }else{
                            $lineSum[$i] = $sum;
                        }
                        $m++;
                    }else{
                        $lineSum[$i]= '&nbsp;';
                    }
                    $i++;
                } 
            }
            foreach($lineSum as $sk=>$sv){
                if($sv=="&nbsp;" && $sk+1<$i && $lineSum[$sk+1] != "&nbsp;" ){
                    $lineSum[$sk] = "汇总:";break;
                }
            }
            /*
            foreach($sumarr as $suk=>$suv){
                if($suv == '<td style="border-right:0">&nbsp;</td>' && $sumarr[$suk+1] != '<td style="border-right:0">&nbsp;</td>' && !in_array('<td style="text-align:right">汇总：</td>',$sumarr) && $suk !=$i-1){
                    $sumarr[$suk] = '<td style="text-align:right">汇总：</td>';
                }elseif($suk == $i-1){
                    $sumarr[$suk] = '<td>&nbsp;</td>';
                }
            }
            
            foreach($sumarr as $sv){
                $parseSum	.= $sv;
            }
            $parseSum	.= '</tr>'."\r\n";
            */
            $dispdata[][""]= array($lineSum);
        }
        return $dispdata;
        
    }
    //  获得字段总和
    protected function getSum($str,$options1){
		if($this->sumResult==''){
	        $this->options = $options1 ;
	    	$this->field($this->sumFields);
			$this->sumResult = $this->find();
        }
        if(preg_match('/\[(.*)\]/',$str)){
            preg_match_all('/\[(.*)\]/U',$str,$matchs);
            for($i=0;$i<count($matchs[0]);$i++){
                $str1=$matchs[0][$i];
                $str2=$matchs[1][$i];
				if(strpos($str2,'.')){
					$str2Arr = explode('.',$str2);
					$str2=$this->sumResult[$str2Arr[1]];
					$result=str_replace($str1,$str2,$str);
				}else{
					$str2=$this->sumResult[$str2];
					$result=str_replace($str1,$str2,$str);
				}
            }
        }else{
			if(strpos($str,'.')){
				$strArr = explode('.',$str);
				$result=$this->sumResult[$strArr[1]];
			}else{
				$result=$this->sumResult[$str];
			}
        }
        return $result;
    }
    //  搜索表单处理
    protected function doSearch($searchArray,$options){
        $whereArr = array(); $whereStr = "";
		foreach($this ->setShow as $kk => $vv){
			$row = isset($vv["searchRow"]) ? $vv["searchRow"] : $vv["row"];
			if(is_string($row))
			{
	            $row = preg_replace("/.*\[/","",$row);
	            $row = preg_replace("/\].*/","",$row); 
            }
			if(isset($vv['searchGet']) && I("request.".$vv['searchGet']."/s")!=""){
				//$reGet =isset($_REQUEST[$vv['searchGet']])?base64_decode(str_replace(' ','+',$_REQUEST[$vv['searchGet']])): $_POST[$vv['searchGet']];
				if(I("request.".$vv['searchGet']."/s")!=""){
					$isutf8 = preg_match('/^.*$/u', I("request.".$vv['searchGet']."/s")) > 0;
					if(!$isutf8){
						$reGet =  iconv('GB2312','UTF-8',I("request.".$vv['searchGet']."/s"));
					}else{
						$reGet = I("request.".$vv['searchGet']."/s");
					}
				}else{
					$reGet = I("post.".$vv['searchGet']."/s");
				}
				$whereArr[$row] = array('eq',$reGet);
                $whereStr .=  $row . "='$reGet' AND ";
			}
			if(isset($vv['searchGetStart']) && I("request.".$vv['searchGetStart']."/s")!=""){
				$whereArr[$row][] = array("egt",I("request.".$vv['searchGetStart']."/s"));
				$whereStr .=  $row . ">='".I('request.'.$vv['searchGetStart'].'/s')."' AND ";
			}
			if(isset($vv['searchGetEnd']) && I('request.'.$vv['searchGetEnd'].'/s') != ""){
				$whereArr[$row][] = array("elt",I('request.'.$vv['searchGetEnd'].'/s'));
				$whereStr .=  $row . "<='".I('request.'.$vv['searchGetEnd'].'/s')."' AND ";
			}
		}
		$i = 0;$con=array();
        foreach($searchArray as $skey=>$svalue){
            foreach(I("request./a") as $key=>$value){
                if($value == "" || substr($key,0,7) != '_search'){continue;}
                if(substr($key,-4)=='mohu'){continue;}
                $k = substr($key,7,2);
                $k = intval(trim($k,"_"));
                $con[$k] = 1;
                if($i == $k){
                	 //对空格做处理
                	 $value = trim($value);
                     if($value=='')continue;
                	 //对'做转义处理
                	 $value = str_replace("'","\'",$value);
                    if($svalue["searchMode"] == 'date'){
                        $value=strtotime($value);
                        if(!$value){
	                    	$value=0;
	                    }
                        if(substr($key,-3)=='end'){
                            $value = $value + 24*60*60 - 1;
                        }
                    }
                    if(isset($svalue["searchSelect"])){
                        if(!is_numeric($value)){
                            $value = isset($svalue["searchSelect"][$value])?$svalue["searchSelect"][$value]:'';
                        }
                    }
                   
                    $row = isset($svalue["searchRow"]) ? $svalue["searchRow"] : $svalue["row"] ;
                    $row = preg_replace("/.*\[/","",$row);
                    $row = preg_replace("/\].*/","",$row);
                    if(isset($svalue['searchSql'])){
						$stringWhere = str_replace('[*]',$value,$svalue['searchSql']);
						$whereArr['_string'] = $stringWhere;
                        $whereStr .=  $stringWhere." AND ";
					}else if(substr($key,-5)=='start'){
						//dump($_REQUEST[substr($key,0,-5).'end']);
						$whereStr .=  $row . ">=" . $value . " AND ";
                        if(I('request.'.substr($key,0,-5).'end/s') == ''){
							$whereArr[$row] = array("egt",$value);
						}else{
							$whereArr[$row] = array('between',array($value));
						}
                    }else if(substr($key,-3)=='end'){
						if(!isset($whereArr[$row])){
							$whereArr[$row] = array("elt",$value);
						}else{
							array_push($whereArr[$row][1],$value);
						}
                        $whereStr .=  $row . "<=" . $value . " AND ";
                        
                    }else if(substr($key,-5)=='other'){
                        if(strstr($value,',')){
                            $value=str_replace(",","','",$value);
                            $whereArr[$row] = array("in",$value);
                            $whereStr .=  " IN('$value')";
                        }else{
                            $mohu="";
                            foreach(I("request./a") as $key1=>$value1){
                                if($key1 == substr($key,0,strlen($key)-5).'mohu'){
                                    $mohu = " LIKE '%$value%' AND ";
                                    $arrmohu = array('like',"%$value%");
                                }
                            }
                            if($mohu==""){
                                $whereArr[$row] = array('eq',$value);
                                $whereStr .=  $row . "='$value' AND ";
                                
                            }else{
                                $whereArr[$row] = $arrmohu;
                                $whereStr .=  $row . $mohu;
                            }
                        }
                    }
					if(!isset($svalue['searchShow']) || $svalue['searchShow']){
						$this->setShow[$skey] =    $svalue;
					}
                }
                
            }
            $i++;
        }
        if(isset($options["where"]) && is_array($options["where"])){
        	$where=$options["where"];
        	
        	if(isset($where['_logic']) and $where['_logic']=='or')
        	{
        		$whereArr['_complex']=$where;
        		$options["where"] = $whereArr;
        	}
        	else
        	{
        		$options["where"] = array_merge($options["where"],$whereArr);
        	}
        	
        }else{
            if($whereStr==""){$whereStr = 1;}
            if(!isset($options['where']) || $options['where']==""){$options['where'] = 1;}
            $whereStr = trim(trim($whereStr),'AND');
            $options['where'] =trim(trim($whereStr. ' AND (' .$options['where']. ')','AND'));
        }
        return array($options,count($con));
    }
    
    // 编辑列表显示项
    public function editList(){
		if(defined('GROUP_NAME')){
			$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}else{
			$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}
		$url = $actionUrl;
        $m=M()->table("xtable_set");
        $result=$m->where("地址='$url'")->find();
        //dump($result);
        $titleList=explode(',',$result["标题"]);
        $showList =explode(',',$result["显示"]);
        $sortList =explode(',',$result["排序"]);
        $titleList=$this->sortByArray($titleList,$sortList);
        $showList=$this->sortByArray($showList,$sortList);
        echo '<div class="pageContent">';
        echo '<div layoutH="40" style="border-top:1px solid #B8D0D6">';
        echo '<div style="padding:5px;color:#555">注：拖动列表进行排序,双击更改标题名称</div>';
        echo '<div style="border:1px solid #B8D0D6;padding:5px;margin:5px;"><div style="width:170px;float:left;font-size:12px"><b>标题</b></div><div style="width:50px;float:left;font-size:12px;"><b>状态</b></div><div style="float:left;font-size:12px"><b>操作</b></div>&nbsp;</div>'."\r\n";
        
        echo '<ul id="sortable">'."\r\n";
	    foreach($titleList as $titleListkey=>$titleListval){
            if($showList["$titleListkey"]=="1"){$sta="显示";$do="隐藏";$color="green";}else{$sta="隐藏";$do="显示";$color="red";}
            echo '<li style="border:1px solid #B8D0D6;padding:5px;margin:5px" id="titleList_'.$titleListkey.'"><div style="width:170px;float:left;font-size:12px" ondblclick="setTitleName(this,\''.$titleListkey.'\')"><input type="text" value="'.$titleList["$titleListkey"].'" style="display:none" id="title_'.$titleListkey.'" onblur="outTitleName(\''.$titleListkey.'\')"><span id="span_'.$titleListkey.'">'.$titleList["$titleListkey"].'</span></div><div style="width:50px;float:left;font-size:12px;color:'.$color.'" id="titleStatus_'.$titleListkey.'">'.$sta.'</div><div style="float:left;font-size:12px"><a href="javascript:;" onclick="setListStatus(this,\''.$titleListkey.'\')">'.$do.'</a></div>&nbsp;</li>';
        }
        echo '</ul>'."\r\n";
        
        echo '</div>';
        echo '<div class="formBar"><ul>';
		echo '<li><div class="buttonActive"><div class="buttonContent"><button  type="button" onclick="conformEditList(\''.$actionUrl."/_conformEditList/1".'\',this)">确定</button></div></div></li>';
		echo '<li><div class="button"><div class="buttonContent"><button type="button" onclick="resetEditList(\''.$actionUrl."/_resetEditList/1".'\')">重置</button></div></div></li>';
        echo '<li><div class="button"><div class="buttonContent"><button type="button" class="close">取消</button></div></div></li>';
        //echo '<li><div class="button"><div class="buttonContent"><button id="reloadList" type="button" onclick="navTabSearch(this)">生效</button></div></div></li>';
		echo '</ul></div>';
        
        echo '</div>'."\r\n";
        echo '<script>$(function(){$("#sortable" ).sortable();});</script>';
        exit();
       
    }  
    // 重置列表显示项
    public function resetEditList(){
		
        if(defined('GROUP_NAME')){
			$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}else{
			$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}
		$url = $actionUrl;
        $m=M()->table("xtable_set");
        M()->startTrans();
        $result=$m->where("地址='$url'")->delete();
        if(!$result){
            echo "重置失败";
        }
        M()->commit();
		die;
    }
    
    // 保存用户更改的列表显示
    public function conformEditList(){
		if(defined('GROUP_NAME')){
			$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}else{
			$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}
		$url = $actionUrl;
        $data=array();
        $showList=trim(I("post.showList/s"),',');
        $sortList=trim(I("post.sortList/s"),',');
        $titleList=trim(I("post.titleList/s"),',');
        $m=M()->table("xtable_set");
        $data["显示"]=$showList;
        $data["排序"]=$sortList;
        $data["标题"]=$titleList;
        M()->startTrans();
        $result=$m->where(array("地址"=>$url))->save(array("显示"=>$showList,"排序"=>$sortList,"标题"=>$titleList));
        if(!$result){
            echo "没有发生更改！";
        }
        M()->commit();
		die;
    }
    
    // 一个数组$array1按另一个数组$array2排序
    protected function sortByArray($array1,$array2){
        $result=array();
        for($j=0;$j<count($array1);$j++){
            $i=0;
            foreach($array1 as $array1key=>$array1val){
                if($array2[$j]==strval($i))
                $result[$array1key]=$array1val;
                $i++;
            }
        }
        return $result;
    }
    
    // 高级搜索列表项
    function searchHtml(){
        if(defined('GROUP_NAME')){
			$actionUrl = __APP__.'/'.GROUP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}else{
			$actionUrl = __APP__.'/'.MODULE_NAME.'/'.ACTION_NAME;
		}
        $_search = "";$_searchstr="";
        foreach(array_merge(I("post./a"),I("get./a")) as $key => $val){
            if($val !="" && $key !="p" && $key !="_order" && $key !="_sort" && $key != "_" && $key !="_URL_" && $key !="search" && substr($key,0,7) != '_search'){
                $_search .= "/$key/".urlencode($val);
            }
			if(!is_array($val)){
				$_searchstr.="&".$key."=".$val;
			}
        }
        $i=0;$d=0;$n=0;$o=0;$selectstr1="";
        $datestr = "" ;$otherstr  = ""; $numstr  = "";
        foreach($this->setSearch as $key=>$value){
            if(isset($value["searchMode"]) && $value["searchMode"]=="date" ){
                if($d % 2 == 0){$datestr  .= '<tr>'."\r\n";}
                $datestr  .= '<td style="width:80px;text-align:right;padding-right:3px">'.$key.'</td><td style="width:220px;text-align:left"><input type="text" name="_search'.$i.'_start" class="date" style="width:90px" value="'.urldecode(I("get._search{$i}_start/s")).'"> - <input style="width:90px" type="text" name="_search'.$i.'_end" class="date" value="'.urldecode(I("get._search{$i}_end/s")).'"></td>'."\r\n";
                 if($d % 2 == 1){$datestr  .= '</tr>'."\r\n";}
                 $d++;
            }else if(isset($value["searchMode"]) && $value["searchMode"]=="num"){
                if($n % 2 == 0){$numstr  .= '<tr>'."\r\n";}
                if(isset($value["searchSelect"])){
                    $numstr  .= '<td style="width:80px;text-align:right;padding-right:3px">'.$key.'</td><td style="width:220px;text-align:left"><nobr><input style="width:90px" type="text" name="_search'.$i.'_start" value="'.urldecode(I("get._search{$i}_start/s")).'" onfocus="selectstr(\'_selectsearch'.$i.'_start\',event)"> - <input style="width:90px" type="text" name="_search'.$i.'_end" value="'.urldecode(I("get._search{$i}_end/s")).'" onfocus="selectstr(\'_selectsearch'.$i.'_end\',event)"></nobr></td>'."\r\n";  
                    $numstr  .= '<div id="_selectsearch'.$i.'_start" style="width:94px;border:1px solid #B8D0D6;display:none;position:absolute;z-index:1011"><ul>';
                    foreach($value["searchSelect"] as $selectkey => $selectvalue){
                        $numstr .='<li onclick="selectfun1(\''.$selectkey.'\',\'_search'.$i.'_start\')" style="cursor:pointer;padding-left:8px;line-height:20px">'.$selectkey.'</li>';
                    }
                    $numstr  .= "</ul></div>";
                    $numstr  .= '<div id="_selectsearch'.$i.'_end" style="width:94px;border:1px solid #B8D0D6;display:none;position:absolute;z-index:1011"><ul>';
                    foreach($value["searchSelect"] as $selectkey => $selectvalue){
                        $numstr .='<li onclick="selectfun1(\''.$selectkey.'\',\'_search'.$i.'_end\')" style="cursor:pointer;padding-left:8px;line-height:20px">'.$selectkey.'</li>';
                    }
                    $numstr  .= "</ul></div>";
                    
                }else{
                    $numstr  .= '<td style="width:80px;text-align:right;padding-right:3px">'.$key.'</td><td style="width:220px;text-align:left"><nobr><input style="width:90px" type="text" name="_search'.$i.'_start" value="'.urldecode(I("get._search{$i}_start/s")).'"> - <input style="width:90px" type="text" name="_search'.$i.'_end" value="'.urldecode(I("get._search{$i}_end/s")).'"></nobr></td>'."\r\n";  
                }
                if($n % 2 == 1){$numstr  .= '</tr>'."\r\n";}
                $n++;
            }else if(isset($value["searchMode"]) && $value["searchMode"]=="text"){
                if($o % 2 == 0){$otherstr  .= '<tr>'."\r\n";}
                if(isset($value["searchSelect"])){
                    $otherstr  .= '<td style="width:80px;text-align:right;padding-right:3px">'.$key.'</td><td style="width:220px;text-align:left"><nobr><input style="width:90px" type="text" name="_search'.$i.'_other"  value="'.urldecode(I("get._search{$i}_other/s")).'" onfocus="selectstr(\'_selectsearch'.$i.'_other\',event)"></td>';
                    $otherstr  .= '<div id="_selectsearch'.$i.'_other" style="width:94px;border:1px solid #B8D0D6;display:none;position:absolute;z-index:1011"><ul>';
                    foreach($value["searchSelect"] as $selectkey => $selectvalue){
                        $otherstr .='<li onclick="selectfun1(\''.$selectkey.'\',\'_search'.$i.'_other\')" style="cursor:pointer;padding-left:8px;line-height:20px">'.$selectkey.'</li>';
                    }
                    $otherstr  .= "</ul></div>";
                    
                }else{
                    $otherstr  .= '<td style="width:80px;text-align:right;padding-right:3px">'.$key.'</td><td style="width:220px;text-align:left"><nobr><input style="width:90px" type="text" name="_search'.$i.'_other"  value="'.urldecode(I("get._search{$i}_other/s")).'">';
                    if(!isset($value["searchFuzzy"])||$value["searchFuzzy"]){
                        if(urldecode(I("get._search{$i}_mohu/s"))){$mohu="checked";}else{$mohu="";}
                        $otherstr  .= '<input type="checkbox" name="_search'.$i.'_mohu" style="background-color:#FFF;border:0;width:30px" '.$mohu.' checked="true"/>模糊</nobr></td>'."\r\n";
                    }else{
                        $otherstr  .= '</nobr></td>'."\r\n";
                    }
                }
                if($o % 2 == 1){$otherstr  .= '</tr>'."\r\n";}
                $o++ ;
            } 
            $i++;  
            
        }
        
        if( $d % 2 == 1 ){$datestr  .= '<td>&nbsp;</td><td>&nbsp;</td>'."\r\n".'</tr>'."\r\n";}
        if( $n % 2 == 1 ){$numstr   .= '<td>&nbsp;</td><td>&nbsp;</td>'."\r\n".'</tr>'."\r\n";}
        if( $o % 2 == 1 ){$otherstr .= '<td>&nbsp;</td><td>&nbsp;</td>'."\r\n".'</tr>'."\r\n";}
        
        if($d > 0){
            $datestr  = '<tr><td colspan="4" style="text-align:left;"><b>日期时间</b></td></tr>'."\r\n" .$datestr;
        }
        if($n > 0)
            $numstr  = '<tr><td colspan="4" style="text-align:left;"><b>数字金额</b></td></tr>'."\r\n" . $numstr;
        if($o > 0)
            $otherstr  = '<tr><td colspan="4" style="text-align:left;"><b>文本类型</b></td></tr>'."\r\n" . $otherstr;
		if($d + $n + $o >0){
			$parseStr  = '<div class="pageContent" id="_searchContent"><form method="post" action="'.$actionUrl.$_search.'" class="pageForm" onsubmit="return navTabSearchXs(this);">';
			$parseStr  .= '<div layoutH="40" style="border-top:1px solid #B8D0D6"><table style="margin-left:10px;">'.$datestr.$numstr.$otherstr.'</table></div>'; 
			$parseStr  .= '<div class="formBar"><ul>';
			$parseStr  .= '<li><div class="buttonActive"><div class="buttonContent"><button type="submit" onclick="_doSearchFun()">搜索</button></div></div></li>';
			$parseStr  .= '<li><div class="button"><div class="buttonContent"><button type="button" onclick="resetfun()">清空</button></div></div></li>';
			$parseStr  .= '<li><div class="button"><div class="buttonContent"><button type="button" class="close">取消</button></div></div></li>';
			
			if($this->excel){
				$parseStr  .= '<input type="hidden" name="searchExcel" value="0">';
				$parseStr  .= '<li><div class="button"><div class="buttonContent"><button  type="button" onclick="_searchExcelfun()">导出EXCEL</button></div></div></li>';
			}
			$parseStr  .= '</ul></div>';
			$parseStr  .= '</form></div>'."\r\n";
			echo $parseStr;
		}
		
        exit();
    }
    
    // 导出excel表
    protected function excel_old($actionobj){

        if(I("post.searchExcel/s")=='' || I("post.searchExcel/s")!='1'){
			$this->options['where'] = unserialize(base64_decode(I("post._where/a")));
		}
		//dump($_POST['_where']);
		//dump(base64_decode($_POST['_where']));
		//dump(unserialize(base64_decode($_POST['_where'])));
		//dump($this->options['where']);die;
        ini_set('memory_limit','500M');
		set_time_limit(300);
        $datasource = $this->select();
        $show = $this->setShow;
        foreach($show as $showkey=>$showval) {
        	if(!$showval || (isset($showval["excel"]) && !$showval["excel"]))
        	{
        		unset($show[$showkey]);
        	}
        }
        if(Extension_Loaded('zlib')){Ob_Start('ob_gzhandler');}
		//G('begin');
        
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        $title =date("YmdHis");
        header("Content-Disposition: attachment; filename=\"excel_{$title}.xls\"");
        //for($i=1;$i<10000;$i++)
        //{
        //echo "f4ewfwecvojrwsgp4rt435t4g4438rt43t4ty4g44t4t4tg4tg";
        //}

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>Untitled Document</title>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '</head>';
        echo '<body>';
       // echo '<table style="width:auto;border:1px solid #99CCFF" cellspacing="0" cellpadding="1" bandno="0">';
	   echo '<table cellspacing="0" cellpadding="3" rules="all" bordercolor="#93BEE2" border="1" style="background-color:White;border-color:#93BEE2;border-width:1px;border-style:None;width:auto;border-collapse:collapse;">';
		//echo '<tr style="background-color:#666699;color:#FFFFFF;border-color:#99CCFF">';
		echo '<tr align="center" style="color:white;background-color:#337FB2;font-weight:bold;">';
        foreach($show as $showtitle=>$showval) {
           if(!$showval || (isset($showval["excel"]) && !$showval["excel"])){continue;}
           echo '<th>';
           echo strval($showtitle).'</th>';
            
        }
        echo '</tr>';
        
        foreach((array)$datasource as $i=>$dataval){
            echo '<tr align="Center">'."\r\n";
            
            foreach($show as $showkey=>$showval) {
                if(!is_array($showval["row"]) && preg_match("/\(.*\)/",$showval["row"]) ){
                    $showval["row"]=str_replace('"[','[',$showval["row"]);
                    $showval["row"]=str_replace(']"',']',$showval["row"]);
                    $showval["row"]=str_replace('[','htmlspecialchars($dataval["',$showval["row"]);
                    $showval["row"]=str_replace(']','"])',$showval["row"]);
                    $showval["row"]=str_replace('$this','$actionobj',$showval["row"]);                
                    eval('$showrow='.$showval["row"].';');
                }else if(is_array($showval["row"])){
                    $value=array();$i=0;
                    foreach($showval["row"] as $val){
                        if($i>0){
                            preg_match_all('/\[(.*)\]/U',$val,$matchs);
                            for($j=0;$j<count($matchs[0]);$j++){
                                $str1=$matchs[0][$j];
                                $str2=$matchs[1][$j];
                                $val=str_replace($str1,$dataval[$str2],$val);
                                
                            }
                            if($val=='$dataval'){
                                $val = $dataval;
                            }
                            $value[($i-1)]=$val;
                        }
                        $i++;
                    }
                    $showrow= call_user_func_array($showval["row"][0],$value);
                }else{
                   $showrow=$showval["row"];
                    preg_match_all('/\[(.*)\]/U',$showrow,$matchs);
                    for($i=0;$i<count($matchs[0]);$i++){
                        $str1=$matchs[0][$i];
                        $str2=$matchs[1][$i];
                        $showrow=str_replace($str1,htmlspecialchars($dataval[$str2]),$showrow);
                    }
                };
                if(isset($showval["format"]))
                {
					if($showrow){
						if($showval["format"]=="time"){
							$showrow=date('Y-m-d H:i:s',$showrow);
						}else if($showval["format"]=="date"){
							$showrow=date('Y-m-d',$showrow);
						}
					}else{
	                    $showrow="&nbsp;";
	                }
                }
                $showrow = preg_replace("/\<(.*)\>/U","",$showrow);
                if(isset($showval["excelMode"]))
                {
                    echo "<td style='vnd.ms-excel.numberformat:".str_replace('text','@',$showval["excelMode"])."'>".$showrow."</td>\r\n";
                }
                else
                {
                	echo "<td>".$showrow."</td>\r\n";
                }
                //if( === "text"){
                   //echo "<td style='vnd.ms-excel.numberformat:@'>".$showrow."</td>\r\n";

                    //echo "<td style='vnd.ms-excel.numberformat:".$showval["excelMode"]."'>".$showrow."</td>\r\n";
                //}else{
                    //
                //}                
                
                // ********************导出格式**********************
                //1） 文本：vnd.ms-excel.numberformat:@
               // 2） 日期：vnd.ms-excel.numberformat:yyyy/mm/dd
                //3） 数字：vnd.ms-excel.numberformat:#,##0.00
                //4） 货币：vnd.ms-excel.numberformat:￥#,##0.00
               // 5） 百分比：vnd.ms-excel.numberformat: #0.00%
                // **********************************************
                
            }
            
            echo '</tr>'."\r\n";
        }
        echo '</table>';
        echo '</body>';
        echo '</html>';
        //G('end');
        //echo G('begin','end').'s';
		//if(Extension_Loaded('zlib'))  
		if(Extension_Loaded('zlib')) Ob_End_Flush();
        exit();
    }
    // 导出excel表
    protected function excel($actionobj){
        ini_set('memory_limit','500M');
		set_time_limit(0);

		include_once(THINK_PATH.'Extend/Vendor/Excel/PHPExcel/Writer/IWriter.php') ;
        include_once(THINK_PATH.'Extend/Vendor/Excel/PHPExcel/Writer/Abstract.php');
        include_once(THINK_PATH.'Extend/Vendor/Excel/PHPExcel/Writer/Excel2007.php');
        include_once(THINK_PATH.'Extend/Vendor/Excel/PHPExcel.php') ;
        include_once(THINK_PATH.'Extend/Vendor/Excel/PHPExcel/IOFactory.php') ;
		/*$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
		$cacheSettings = array('memoryCacheSize'=>'16MB'); 
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);*/
		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;  
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod);  

	    $objPHPExcel = new PHPExcel();
	    $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
	    ->setLastModifiedBy("Maarten Balliauw")
	    ->setTitle("Office 2007 XLSX Test Document")
	    ->setSubject("Office 2007 XLSX Test Document")
	    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
	    ->setKeywords("office 2007 openxml php")
	    ->setCategory("Test result file");
	    $objPHPExcel->getActiveSheet()->getStyle('G')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        if(I("post.searchExcel/s")=="" || I("post.searchExcel/s")!='1'){
			$this->options['where'] = unserialize(base64_decode(I("request._where/a")));
		}

        $datasql = $this->select(false);
        $show = $this->setShow;
        foreach($show as $showkey=>$showval) {
        	if(!$showval || (isset($showval["excel"]) && !$showval["excel"]))
        	{
        		unset($show[$showkey]);
        	}
        }
        //第一行标题
        $objPHPExcel->setActiveSheetIndex(0);
		$asc = 65;
		$pre = '';
		$i = 1; 
        foreach($show as $showtitle=>$showval) {
    		if($i>26){
        		$pre = chr(64+ floor($i/26));
        		$asc = 64+$i%26;
        	} 
           if(!$showval || (isset($showval["excel"]) && !$showval["excel"])){continue;}
           $objPHPExcel->setActiveSheetIndex(0)->setCellValue($pre.chr($asc).'1', strval($showtitle));
           $objPHPExcel->getActiveSheet()->getStyle($pre.chr($asc).'1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE); 
           $objPHPExcel->getActiveSheet()->getStyle($pre.chr($asc).'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);   
           $objPHPExcel->getActiveSheet()->getStyle($pre.chr($asc).'1')->getFill()->getStartColor()->setARGB('0098BEEE'); 
           $asc++;
           $i++;
        }
			//$objFill = $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill();
           //	$objFill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);   
		//	$objFill->getStartColor()->setARGB('FFEEEEEE'); 
	    //导出数据内容
        $no=2;
	    $objPHPExcel->setActiveSheetIndex(0);
	    $datalist = mysql_unbuffered_query($datasql,$this->db->_linkID);
		do{
			$dataval = mysql_fetch_assoc($datalist);
			if(!$dataval)break;
			$pre = '';
        	$asc = 65;
        	$ii = 1;     
            foreach($show as $showkey=>$showval) {
            	if($ii>26){
            		$pre = chr(64+ floor($ii/26));
            		$asc = 64+$ii%26;
            	} 
                if(!is_array($showval["row"]) && preg_match("/\(.*\)/",$showval["row"]) ){
                    $showval["row"]=str_replace('"[','[',$showval["row"]);
                    $showval["row"]=str_replace(']"',']',$showval["row"]);
                    $showval["row"]=str_replace('[','htmlspecialchars($dataval["',$showval["row"]);
                    $showval["row"]=str_replace(']','"])',$showval["row"]);
                    $showval["row"]=str_replace('$this','$actionobj',$showval["row"]);                
                    eval('$showrow='.$showval["row"].';');
                }else if(is_array($showval["row"])){
                    $value=array();$i=0;
                    foreach($showval["row"] as $val){
                        if($i>0){
                        	if(!strpos($val,'[')){//比如说传参类似fun_bank[1]这种形式 不做处理
	                            preg_match_all('/\[(.*)\]/U',$val,$matchs);
	                            for($j=0;$j<count($matchs[0]);$j++){
	                                $str1=$matchs[0][$j];
	                                $str2=$matchs[1][$j];
	                                $val=str_replace($str1,$dataval[$str2],$val);
	                                
	                            }
                            }
                            if($val=='$dataval'){
                                $val = $dataval;
                            }
                            $value[($i-1)]=$val;
                        }
                        $i++;
                    }
                    $showrow= call_user_func_array($showval["row"][0],$value);
                }else{
                   $showrow=$showval["row"];
                    preg_match_all('/\[(.*)\]/U',$showrow,$matchs);
                    for($i=0;$i<count($matchs[0]);$i++){
                        $str1=$matchs[0][$i];
                        $str2=$matchs[1][$i];
                        $showrow=str_replace($str1,htmlspecialchars($dataval[$str2]),$showrow);
                    }
                };
                if(isset($showval["format"]))
                {
					if($showrow){
						if($showval["format"]=="time"){
							$showrow=date('Y-m-d H:i:s',$showrow);
						}else if($showval["format"]=="date"){
							$showrow=date('Y-m-d',$showrow);
						}
					}else{
	                    $showrow="";
	                }
                }
                $showrow = preg_replace("/\<(.*)\>/U","",$showrow);
                if(isset($showval["excelMode"]))
                {
                	if($showval["excelMode"]=='text'){
                		$objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit($pre.chr($asc).$no, $showrow,PHPExcel_Cell_DataType::TYPE_STRING);
                	}else{
                		$objPHPExcel->setActiveSheetIndex(0)->setCellValue($pre.chr($asc).$no, $showrow);
                	}
                }
                else
                {
                	$objPHPExcel->setActiveSheetIndex(0)->setCellValue($pre.chr($asc).$no, $showrow);
                }
                
                
                $asc++;
                $ii++;
            }
            $no++;
        }while($dataval);
		unset($datalist);
		unset($dataval);
		$objPHPExcel->getActiveSheet()->setTitle('sheet1');//设置sheet标签的名称
	    $objPHPExcel->setActiveSheetIndex(0);
	    $title =date("YmdHis");
	    ob_end_clean();  //清空缓存 
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
	    header("Content-Type:application/force-download");
	    header("Content-Type:application/vnd.ms-execl");
	    header("Content-Type:application/octet-stream");
	    header("Content-Type:application/download");
	    header('Content-Disposition:attachment;filename=excel_'.$title.'.xls');//设置文件的名称
	    header("Content-Transfer-Encoding:binary");
	    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	    $objWriter->save('php://output');
	    exit;
    }
    
}

?>