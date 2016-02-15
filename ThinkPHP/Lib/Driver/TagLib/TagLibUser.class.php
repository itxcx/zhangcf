<?php
class TagLibUser extends TagLib
{ 
	//标签定义 
	protected $tags   =  array( 
		// 标签定义： 
		//attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次 
		'rename'=>array('attr'=>'id,name,value,style,class,onclick','close'=>0,'level'=>3), 
   );

    //定义查询数据库标签 
    public function _rename($attr) 
	{ 
        $tag    = $this->parseXmlAttr($attr,'rename');
        $name       = isset($tag['name'])?$tag['name']:'';                //名称
        $value      = isset($tag['value'])?$tag['value']:'';                 //文字
        $id         = isset($tag['id'])?$tag['id']:'';                //ID
        $style      = isset($tag['style'])?$tag['style']:'';                //样式
        $class      = isset($tag['class'])?$tag['class']:'';                //class
        $onclick=isset($tag['onclick'])?$tag['onclick']:"showEditRename(this)";
        $onmouseout=isset($tag['onmouseout'])?$tag['onmouseout']:"";
        $parseStr   = '<?php if(F(THEME_NAME."_'.$name.'")){ $value = F(THEME_NAME."_'.$name.'");}else{ $value="'.$value.'";}';
        $parseStr   .= 'if(isset($_SESSION["loginUserName"]) && $_SESSION["loginUserName"] !=""){ $onclick="'.$onclick.'";if($value=="[无内容]")$opacity="opacity:0.5;";else $opacity="";}else{ $onclick="";if($value=="[无内容]") $value=" ";$opacity="";}';
        $parseStr   .= 'echo \'<span class="'.$class.'" style="\'.$opacity.\''.$style.'" name="'.$name.'" onclick="\'.$onclick.\'" onmouseout="\'.$mouseout.\'">\'.$value.\'</span><div style="font-size:12px;font-color:#FFF;background-color:#aaa;vertical-align:top;display:none;padding:2px 0px;width:71px;position:absolute"><span style="padding:2px 4px;cursor:pointer" onmouseover="this.style.background=\\\'#555\\\'" onmouseout="this.style.background=\\\'#aaa\\\'" onclick="saveEditRename(this,\\\''.$name.'\\\')">保存</span>|<span style="padding:2px 4px;cursor:pointer" onmouseover="this.style.background=\\\'#555\\\'" onmouseout="this.style.background=\\\'#aaa\\\'" onclick="cancelEditRename(this,\\\''.$name.'\\\')">取消</span><span id="'.$name.'_v" style="display:none">\'.$value.\'</span></div>\';';
        $parseStr   .= '?>';

        return $parseStr;
        
    }
}

?>