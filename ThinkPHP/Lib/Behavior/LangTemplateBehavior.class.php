<?php
/**
 +------------------------------------------------------------------------------
  用于替换模板中的多语言选择代码
 +------------------------------------------------------------------------------
 */
class LangTemplateBehavior extends Behavior {

    // 行为扩展的执行入口必须是run
    public function run(&$content){
		if(strpos($content,'__LANGLINK__') !== false)
		{
			
			//如果没开启语言包，则不做任何输出
			if(!C('LANG.USE'))
			{
				$content = str_replace('__LANGLINK__','',$content);
				return ;
			}
			if(!defined('LANG_SET'))
			{
					throw_exception('使用语言标签替换，但是未发现CheckLang模块定义的LANG_SET常量');
			}
			$link="<ul class='langlink_ul'>";
			foreach(C('LANG.SET') as $key=>$val)
			{
				if(LANG_SET == $key)
				{
					$tmp = "<li><a href=" .__ACTION__. "/l/" .$key. " class='langselect'>".$val."</a></li>";
				}
				else
				{
					$tmp = "<li><a href=" .__ACTION__. "/l/" .$key. ">".$val."</a></li>";
				}
				$link.=$tmp;
			}
			$link .= '</ul>';
			
			$content = str_replace('__LANGLINK__',$link,$content);
			
		}
    }
}
?>