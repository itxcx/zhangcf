<?php
class TagLibSql extends TagLib
{ 
	//标签定义 
	protected $tags   =  array( 
		// 标签定义： 
		//attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次 
		'query'=>array('attr'=>'model,view,field,limit,order,where,sql,key,mod','level'=>3), 
   );

    //定义查询数据库标签 
    public function _query($attr,$content) 
	{ 
        $tag    = $this->parseXmlAttr($attr,'query'); 
        $result = !empty($tag['result'])?$tag['result']:'vo'; //定义数据查询的结果存放变量 
        $key    = !empty($tag['key'])?$tag['key']:'i'; 
        $mod    = isset($tag['mod'])?$tag['mod']:'2'; 
        //echo $result.'<br/>';
		if ( isset($tag['model']) || isset($tag['view']) ) 
		{   
            //根据用户输入的值拼接查询条件
            if( isset($tag['model']) )
            {
                $sql  = "M('{$tag['model']}')->";
            } 
            else if( isset($tag['view']) )
            {
                $sql  = "D('{$tag['view']}')->";
            }
			$sql .= isset($tag['field'])?"field('{$tag['field']}')->":''; 
			$sql .= isset($tag['order'])?"order('{$tag['order']}')->":''; 

			if( isset($tag['where']) )
			{ //转换 gt egt elt lt 为 >  >=  <= <
				$_where_find	= array('gt','egt','lt','elt');
				$_where_replace = array('>','>=','<','<=');
				$_where = str_replace($_where_find,$_where_replace,$tag['where']);
				$sql .= "where(\"{$_where}\")->"; 
			}
			
			if(isset($tag['limit'])){
				$limitarray=explode(",",$tag['limit']);
				foreach($limitarray as $k=>$limits)
				{
					$tag['limit']='';
					if(count($limitarray)>1)
					{
					   $tag['limit']="limit(\"$limitarray[0],$limitarray[1]\")->";
					}
					else
					{
						$tag['limit']="limit({$limits})->";
					}
				}
			}else{
				$tag['limit']='';
			}
			$sql .= $tag['limit'];
			$sql .= "select()"; 
		}
		else
		{ 
			if (!$tag['sql']) return ''; //排除没有指定参数	
			$sql = "M()->query(\"{$tag['sql']}\")"; 	
		} 
	   //exit;
	   //下面拼接输出语句 
		$parsestr  = '<?php $_result='.$sql.'; if ($_result): $'.$key.'=0;'; 
		$parsestr .= 'foreach($_result as $key=>$'.$result.'):'; 
		$parsestr .= '++$'.$key.';$mod = ($'.$key.' % '.$mod.' );?>'; 
		$parsestr .= $content;//解析在article标签中的内容 
		$parsestr .= '<?php endforeach; endif;?>'; 
		return  $parsestr; 
	}

}

?>