<?php
defined('APP_NAME') || die('不要非法操作哦');
class TableListAction extends Model{
    
    public $setShow = array();                  // 列表显示定义
       
    public $pagenum=15;                         // 每页显示行数
    
    public $showSearch = true;                 // 是否显示开启搜索
    
    public $setSearch = array();              // 搜索字段定义
    
    public $myoptions;

	public $pageCon = "p";

	public $edit = false;

    protected $comparison      = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE');
    
    public function getData(){
		if($this->edit){
			$this->resetShow();
		}
        return $this->initialize();
    }
    //在模版调用
    public function getDataTpl(){
		if($this->edit){
			$this->resetShow();
		}
        return $this->tableList();
    }
    public function addShow($string,$array){
        $this->setShow[$string]=$array;
    }
    public function initialize() {
        $this->myoptions =  $this->options;
        return $this->tableList();
    }
    //  判断数据库中是否有标题数据，如果有则按照数据库中的设置显示，没有则保存到数据库
    protected function resetShow(){
        $m=M("xtable_set");
        //$url=$_SERVER["PHP_SELF"];
		$url = __ACTION__;
        $result=$m->table("xtable_set")->where("地址='$url'")->find();
        if(!$result){
            $this->setSearch=$this->setShow;
		
            $data=array("地址"=>$url,"标题"=>"","显示"=>"","排序"=>"");
            $i=0;
            foreach($this->setShow as $showkey=>$showval){
					
                $data["标题"].=",".$showkey;
                if($showval["hide"]){
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
            $m->table("xtable_set")->add($data);
            
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
    //  列表
    public function tableList(){
        if($this->pagenum > 0){  
            $data  = $this->page();
            
        }else{
            $where = array();
            foreach($this->setSearch as $key=>$value){
                if($_REQUEST[$key] != ""){
                    if(preg_match("/^\d{2,4}-\d{1,2}-\d{1,2}$/",trim($_REQUEST[$key]))){
                        $_REQUEST[$key] = strtotime($_REQUEST[$key]);
                    }
                    if(is_array($value)){
                        $value["exp"] = isset($value["exp"]) ? $value["exp"] : 'eq';
                        $where[$value["row"]] = array($value["exp"],$_REQUEST[$key]);
                    }else{
                        $where[$value] = $_REQUEST[$key];
                    }
                }
            }
            if(is_array($this->myoptions["where"])){
                $this->myoptions["where"] = array_merge($this->myoptions["where"],$where);
            }else if(count($where>0)){
                if(isset($this->myoptions["where"]) && $this->myoptions["where"]!=''){
                    $this->myoptions["where"] = $this->myoptions["where"] .' AND '.$this->parseWhere($where);
                }else{
                    $this->myoptions["where"] = $this->parseWhere($where);
                }
            }
            $this->options = $this->myoptions;
            
            $list = $this->select();
            $data = array('list'=>$list);
            //dump($list);
        }
        return $data;
    }
    
    // 分页
    public function page() {
        $where = array();
        $parameter = "" ;                //  页面跳转时带的参数
        foreach($_REQUEST as $key=>$val) {  
            if($val!="" && $key!="__hash__"&& $key!="_URL_" && $key !="obj"){
                 $parameter   .=   "$key=".urlencode($val)."&"; 
            }
        }
        foreach($this->setSearch as $key=>$value){
            if($_REQUEST[$key]){
                if(preg_match("/^\d{2,4}-\d{1,2}-\d{1,2}$/",$_REQUEST[$key])){
                    $_REQUEST[$key] = strtotime($_REQUEST[$key]);
                }
                if(is_array($value)){
                    $value["exp"] = isset($value["exp"]) ? $value["exp"] : 'eq';
                    $where[$value["row"]] = array($value["exp"],$_REQUEST[$key]);
                }else{
                    $where[$value] = $_REQUEST[$key];
                }
            }
        }
        if(count($where)>0){
            if(is_array($this->myoptions["where"])){
                $this->myoptions["where"] = array_merge($this->myoptions["where"],$where);
            }else{
                if(isset($this->myoptions["where"]) && $this->myoptions["where"]!=''){
                    $this->myoptions["where"] = $this->myoptions["where"] .' AND '.$this->parseWhere($where);
                }else{
                    $this->myoptions["where"] = $this->parseWhere($where);
                }
            }
            
        }
        $this->options = $this->myoptions;
        $listRows = $this->pagenum;
        $count = $this->count();
        $p = $this->pageCon;                                                       //  参数  
        $totalPages = intval(ceil($count/$listRows));                   //  总页数
        $totalPages = ($totalPages==0) ? 1 : $totalPages ;
        $nowPage  = !empty($_GET[$p]) ? intval($_GET[$p]) : 1;          //  当前页码
        if(!empty($totalPages) && $nowPage > $totalPages) {
            $nowPage = $totalPages;
        }        
        $firstRow = $listRows * ($nowPage-1);                   //  起始行数
        $this->options = $this->myoptions;
        //dump($this->options);
        $list = $this->limit($firstRow.','.$listRows)->select();
        $url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'&':"?").$parameter;
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        
        //上下页
        $upRow   = $nowPage-1;
        $downRow = $nowPage+1;
        $rollPage =array();
        for($i=10;$i>0;$i--){
            if(($nowPage-$i) >0)
            $rollPage[-$i] = $url."&".$p."=" . ($nowPage-$i);
        }
        for($i=0;$i<10;$i++){
            if(($nowPage+$i) <= $totalPages)
            $rollPage[$i] = $url."&".$p."=" . ($nowPage+$i);
        }
        $field = array();$i=0;
        if($this->setShow !=""){
            foreach($this->setShow as $k=>$v){
                if($v){
                    $field[$i] = $k;
                }
                $i++;
            }
        }
        $list1 = $this->getList($list);
        if(isset($_SESSION["loginUserName"]) && $_SESSION["loginUserName"] !="" && $this->edit){
            $pageStr = array(
                "count" => $count,                           //  总记录条数
                "url" => $url."&".$p."=",                   //  不带页码的url
                "nowPage" => $nowPage,                      //  当前页码
                "firstRow" => $url."&".$p."=1",             //  首页url地址
                "upRow" =>$url."&".$p."=".$upRow,           //  上一页url地址
                "downRow" => $url."&".$p."=".$downRow,      //  下一页url地址
                "theEndRow" => $url."&".$p."=".$totalPages, //  尾页url地址
                "totalPages" => $totalPages,                //  总页数
                "rollPage" =>$rollPage,
                "list" => $list1,                            //  列表数据数组
                "pagenum" => $this->pagenum,
                "field" => $field,
                
                "edit" => __APP__."/TableList/editList/edit/".base64_encode(__ACTION__."/obj/".$_REQUEST["obj"]),
            );
        }else{
           $pageStr = array(
           
                "count" => $count,                           //  总记录条数
                "url" => $url."&".$p."=",                   //  不带页码的url
                "nowPage" => $nowPage,                      //  当前页码
                "firstRow" => $url."&".$p."=1",             //  首页url地址
                "upRow" =>$url."&".$p."=".$upRow,           //  上一页url地址
                "downRow" => $url."&".$p."=".$downRow,      //  下一页url地址
                "theEndRow" => $url."&".$p."=".$totalPages, //  尾页url地址
                "totalPages" => $totalPages,                //  总页数
                "rollPage" =>$rollPage,
                "list" => $list1,                            //  列表数据数组
                "pagenum" => $this->pagenum,
                "field" => $field,
                
            ); 
        }
        return $pageStr;
    }
    // 获取转换后的显示列表数组
    function getList($datasource){
        if(isset($this->setShow) && count($this->setShow)>0){
            $list = array();
            if($datasource)
            foreach($datasource as $k=>$dataval){
                foreach($this->setShow as $showkey=>$showval) {
                    if(!$showval){continue;}
                    if(is_array($showval["row"])){
                        $value=array();$i=0;
                        foreach($showval["row"] as $val){
                            if($i>0){
                                if(is_string($val))preg_match_all('/\[(.*)\]/U',$val,$matchs);
                                for($j=0;$j<count($matchs[0]);$j++){
                                    $str1=$matchs[0][$j];
                                    $str2=$matchs[1][$j];
									if(!is_numeric($str2))
									{
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
                        
                    }else {
                        $showrow=$showval["row"];
                        preg_match_all('/\[(.*)\]/U',$showrow,$matchs);
                        for($i=0;$i<count($matchs[0]);$i++){
                            $str1=$matchs[0][$i];
                            $str2=$matchs[1][$i];
							if(!is_numeric($str2))
							{
	                            $showrow=str_replace($str1,$dataval[$str2],$showrow);
                            }
                        }
                    }
                    if(isset($showval["format"]) && $showval["format"]=="bool"){
                        if((bool)$showrow){
                            $showrow='<img src="/Public/Admin/images/ok.gif" border="0">';
                        }else{
                            $showrow='<img src="/Public/Admin/images/cross.gif" border="0"/>';
                        }
                    }else if(isset($showval["format"]) && $showval["format"]=="time"){
                    	if($showrow!=0)
                        	$showrow=date('Y-m-d H:i:s',$showrow);
                        else
                        	$showrow='';
                    }else if(isset($showval["format"]) && $showval["format"]=="date"){
                    	if($showrow!=0)
                    		$showrow=date('Y-m-d',$showrow);
                    	else
                    		$showrow='';
                    }else if(isset($showval["format"]) && $showval["format"]=="num"){
                    	if($showrow==""){$showrow="0";}
                    }
                    if(isset($showval["url"])){
                        preg_match_all('/\[(.*)\]/U',$showval["url"],$matchs);
                        $showurl=$showval["url"];
                        for($i=0;$i<count($matchs[0]);$i++){
                            $str1=$matchs[0][$i];
                            $str2=$matchs[1][$i];
                            $showurl=str_replace($str1,$dataval[$str2],$showurl);
                        }
                       // if($page){
                        //    $showurl=$showurl.'/page/'.$p;
                       // }
                        $list= '<a href="'.$showurl.'" target="'.$showval["target"].'">'.$showrow.'</a>';
                    }else{
                        if($showrow==""){$showrow="&nbsp;";}
                        $list[$k][$showkey]=$showrow;
                    }
                }
            }


			$i=0;
			foreach($this->setShow as $value){
				if(isset($value["sum"])){
					$i++;
				}
			}
			if($i>0 && $list){
				$lineSum=array();
				
				$i=0;
				foreach($this->setShow as $value){
					if($value){
						if(isset($value["sum"])){
							$sum=$this->getSum($value["sum"],$this->myoptions);
							//dump($options1);
							if($sum == ""){
								$lineSum[$i] = '&nbsp;';
							}else{
								$lineSum[$i] = $sum;
							}
						}else{
							$lineSum[$i]= '&nbsp;';
						}
						$i++;
					}
				}
				foreach($lineSum as $sk=>$sv){
					if($sv=="&nbsp;" && $sk+1<$i && $lineSum[$sk+1] != "&nbsp;" ){
						$lineSum[$sk] = "汇总:";
						break;
					}
				}
				$list[] = $lineSum;
			}
            return  $list;
        }else{
            return $datasource;
        }
    }

	//  获得字段总和
    protected function getSum($str,$options){
        $this->options = $options;
        if(preg_match('/\[(.*)\]/',$str)){
            preg_match_all('/\[(.*)\]/U',$str,$matchs);
            for($i=0;$i<count($matchs[0]);$i++){
                $str1=$matchs[0][$i];
                $str2=$matchs[1][$i];
                $str2=$this->sum($str2);
                $result=str_replace($str1,$str2,$str);
            }
        }else{
            $result=$this->sum($str);
        }
        return $result;
    }
      
    //  where条件 数组转换字符串
    protected function parseWhere($where) {
        $whereStr = '';
        if(is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        }else{ // 使用数组或者对象条件表达式
            if(isset($where['_logic'])) {
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate    =   ' '.strtoupper($where['_logic']).' ';
                unset($where['_logic']);
            }else{
                // 默认进行 AND 运算
                $operate    =   ' AND ';
            }
            foreach ($where as $key=>$val){
                $whereStr .= '( ';
                

                // 多条件支持
                $multi = is_array($val) &&  isset($val['_multi']);
                $key = trim($key);
                if(strpos($key,'|')) { // 支持 name|title|nickname 方式定义查询字段
                        $array   =  explode('|',$key);
                        $str   = array();
                        foreach ($array as $m=>$k){
                            $v =  $multi?$val[$m]:$val;
                            $str[]   = '('.$this->parseWhereItem($this->parseKey($k),$v).')';
                        }
                        $whereStr .= implode(' OR ',$str);
                }elseif(strpos($key,'&')){
                        $array   =  explode('&',$key);
                        $str   = array();
                        foreach ($array as $m=>$k){
                            $v =  $multi?$val[$m]:$val;
                            $str[]   = '('.$this->parseWhereItem($this->parseKey($k),$v).')';
                        }
                        $whereStr .= implode(' AND ',$str);
                }else{
                        $whereStr   .= $this->parseWhereItem($this->parseKey($key),$val);
                }
                
                $whereStr .= ' )'.$operate;
            }
            $whereStr = substr($whereStr,0,-strlen($operate));
        }
        return empty($whereStr)?'1': $whereStr;
    }

    // where子单元分析
    protected function parseWhereItem($key,$val) {
        $whereStr = '';
        if(is_array($val)) {
            if(is_string($val[0])) {
                if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i',$val[0])) { // 比较运算
                    $whereStr .= $key.' '.$this->comparison[strtolower($val[0])].' '.$this->parseValue($val[1]);
                }elseif('exp'==strtolower($val[0])){ // 使用表达式
                    $whereStr .= ' ('.$key.' '.$val[1].') ';
                }elseif(preg_match('/IN/i',$val[0])){ // IN 运算
                    if(isset($val[2]) && 'exp'==$val[2]) {
                        $whereStr .= $key.' '.strtoupper($val[0]).' '.$val[1];
                    }else{
                        if(is_string($val[1])) {
                             $val[1] =  explode(',',$val[1]);
                        }
                        $zone   =   implode(',',$this->parseValue($val[1]));
                        $whereStr .= $key.' '.strtoupper($val[0]).' ('.$zone.')';
                    }
                }elseif(preg_match('/BETWEEN/i',$val[0])){ // BETWEEN运算
                    $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                    $whereStr .=  ' ('.$key.' '.strtoupper($val[0]).' '.$this->parseValue($data[0]).' AND '.$this->parseValue($data[1]).' )';
                }else{
                    throw_exception(L('_EXPRESS_ERROR_').':'.$val[0]);
                }
            }else {
                $count = count($val);
                if(in_array(strtoupper(trim($val[$count-1])),array('AND','OR','XOR'))) {
                    $rule = strtoupper(trim($val[$count-1]));
                    $count   =  $count -1;
                }else{
                    $rule = 'AND';
                }
                for($i=0;$i<$count;$i++) {
                    $data = is_array($val[$i])?$val[$i][1]:$val[$i];
                    if('exp'==strtolower($val[$i][0])) {
                        $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                    }else{
                        $op = is_array($val[$i])?$this->comparison[strtolower($val[$i][0])]:'=';
                        $whereStr .= '('.$key.' '.$op.' '.$this->parseValue($data).') '.$rule.' ';
                    }
                }
                $whereStr = substr($whereStr,0,-4);
            }
        }else {
            //对字符串类型字段采用模糊匹配
            if(C('DB_LIKE_FIELDS') && preg_match('/('.C('DB_LIKE_FIELDS').')/i',$key)) {
                $val  =  '%'.$val.'%';
                $whereStr .= $key.' LIKE '.$this->parseValue($val);
            }else {
                $whereStr .= $key.' = '.$this->parseValue($val);
            }
        }
        return $whereStr;
    }
     protected function parseValue($value) {
        if(is_string($value)) {
            $value = '\''.$this->escapeString($value).'\'';
        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value   =  $this->escapeString($value[1]);
        }elseif(is_array($value)) {
            $value   =  array_map(array($this, 'parseValue'),$value);
        }elseif(is_null($value)){
            $value   =  'null';
        }
        return $value;
    }
    protected function parseKey(&$key) {
        return $key;
    }
    public function escapeString($str) {
        return addslashes($str);
    }
    // 编辑列表显示项
    public function editList(){
        if(!isset($_SESSION["loginUserName"]) || $_SESSION["loginUserName"] =="" ) exit("没有权限");
        $url=base64_decode($_REQUEST["edit"]);
        $m=M("xtable_set")->table("xtable_set");
        $result=$m->where("地址='$url'")->find();
      //  dump($result);
        $titleList=explode(',',$result["标题"]);
        $showList =explode(',',$result["显示"]);
        $sortList =explode(',',$result["排序"]);
        $titleList=$this->sortByArray($titleList,$sortList);
        $showList=$this->sortByArray($showList,$sortList);
       //dump($titleList);
        echo '<HTML><head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <script src="'.__ROOT__.'/Public/Js/jquery-1.8.3.min.js"></script>
        <script src="'.__ROOT__.'/Public/Js/jquery-ui.js"></script>
        <script src="'.__ROOT__.'/Public/User/js/xstable.js"></script>
        <script>
        $(function(){
            $("#sortable").sortable();    
        });
        </script>
        </head>
        <body bgcolor="#FFFFFF">';

        echo '<div style="width:300px">';
        echo '<div id="editHead" style="border-bottom:1px solid #eee;padding-bottom:5px">'."\r\n";
		echo '<span id="inner_header" style="font-size:12px;"><b>编辑列表</b></span><span style="font-size:12px;"> (拖动列表进行排序,双击更改标题名称)</span>'."\r\n";
        echo '</div><div style="margin:5px">'."\r\n";
        echo '<ul style="border:0;list-style-type:none;padding:0;margin:0"><li style="border:0;"><div style="width:200px;float:left;font-size:12px;font-weight:bold">标题</div><div style="width:50px;float:left;font-size:12px;font-weight:bold">状态</div><div style="float:left;font-size:12px;font-weight:bold">操作</div>&nbsp;</li></ul>'."\r\n";
        echo '<ul id="sortable" style="list-style-type:none;padding:5 0;margin:0;">'."\r\n";
        
	    // for($i=0;$i<count($titleList);$i++){
	    foreach($titleList as $titleListkey=>$titleListval){
            if($showList["$titleListkey"]=="1"){$sta="显示";$do="隐藏";$color="green";}else{$sta="隐藏";$do="显示";$color="red";}
            echo '<li id="titleList_'.$titleListkey.'" style="height:25px"><div style="width:200px;float:left;font-size:12px" ondblclick="setTitleName(this,\''.$titleListkey.'\')"><input type="text" value="'.$titleList["$titleListkey"].'" style="display:none" id="title_'.$titleListkey.'" onblur="outTitleName(\''.$titleListkey.'\')"><span id="span_'.$titleListkey.'">'.$titleList["$titleListkey"].'</span></div><div style="width:50px;float:left;font-size:12px;color:'.$color.'" id="titleStatus_'.$titleListkey.'">'.$sta.'</div><div style="float:left;font-size:12px"><a href="javascript:;" onclick="setListStatus(this,\''.$titleListkey.'\')">'.$do.'</a></div>&nbsp;</li>'."\r\n";
        }
        echo '</ul>'."\r\n";
        echo '<div style="padding-top:5px;padding-left:60px"><div style="float:left;width:60px;"><input type="button" value="确定" onclick="conformEditList(\''.$url.'\')"></div><div style="float:left;width:60px"><input type="button" value="取消" onclick="closeEdit()"></div><div style="float:left;width:60px"><input type="button" value="重置" onclick="resetTitle(\''.$url.'\',this)"></div></div></div>'."\r\n";
        echo '</div>';
        echo '</body></html>';
       
    }  
    // 重置列表显示项
    public function resetTitle(){
        $url=$_POST["url"];
        $m=M("xtable_set")->table("xtable_set");
        M()->startTrans();
        $result=$m->where("地址='$url'")->delete();
        if(!$result){
        	M()->rollback();
            echo "重置失败";
        }
        M()->commit();
    }
    
    // 保存用户更改的列表显示
    public function conformEditList(){
        $url=$_POST["url"];$data=array();
        $showList=trim($_POST["showList"],',');
        $sortList=trim($_POST["sortList"],',');
        $titleList=trim($_POST["titleList"],',');
        $m=M("xtable_set")->table("xtable_set");
        $data["显示"]=$showList;
        $data["排序"]=$sortList;
        $data["标题"]=$titleList;
        M()->startTrans();
        $result=$m->where("地址='$url'")->save($data);
        M()->commit();
    }
}
?>