<?php

class McAction extends  Action{
	public function index()
	{
        //exec();
		$prize = X('prize_*');
		$this->assign('prize',$prize);
		$this->display();
        
	}

	public function update()
	{
		$post = $_POST;

		$doc = new DOMDocument;
		$doc->load(ROOT_PATH."/DmsAdmin/config.xml");// 加载Xml文件    
		$XPath = new DOMXPath($doc);


		foreach($post as $k=>$v){
			$nodeList = $XPath->query("/con/user//*[@name='$k']"); // 获取文档下name属性等于xx的元素
			$node = $nodeList->item(0);
			$node->setAttribute('byname',$v);
			
		}

		// 设置编码 保存
		$doc->encoding = 'UTF-8';
		$doc->save(ROOT_PATH."/DmsAdmin/config.xml");

		// 读取文件内容
		$content = file_get_contents(ROOT_PATH."/DmsAdmin/config.xml");
		// 替换
		$content = str_replace(array('&gt;'),array('>'),$content);
		// 写入
		file_put_contents(ROOT_PATH."/DmsAdmin/config.xml",$content);
        if($_SERVER['SERVER_ADDR'] == '192.168.0.165')
        {
            exec("svn commit ".ROOT_PATH."/DmsAdmin/config.xml -m adminedit");
        }
		$this->success("设置完成");

	}
}
?>