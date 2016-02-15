<?php
defined('APP_NAME') || die('不要非法操作哦!');
class SnAction extends CommonAction {
    public function index()
    {
        $setButton=array(
			'导入'=>array("class"=>"add","href"=>__URL__."/upload","target"=>"dialog","mask"=>"true","width"=>"520","height"=>"240"),
            '删除'=>array("class"=>"delete","href"=>"__URL__/delbanks"."/id/{tl_id}","target"=>"ajaxTodo" ,"title"=>"确定要删除吗?"),
        );
        $setShow = array(
            'Sn号'=>array('row'=>'[sn]'),
            '添加时间'=>array('row'=>'[时间]','format'=>'date'),
			'状态'=>array('row'=>'[状态]'),
        );
        
        $list=new TableListAction("sn",null);
        $list->setShow = $setShow;         // 定义列表显示
        $list->setButton = $setButton;     // 定义按钮显示
        $list->title="密码器序列号";        // 列表标题
        //$this->assign('list',); 
        echo $list->getHtml();
    }
    //文件上传
    public function upload()
    {
    	$this->display();
    }
    public function uploadSave()
    {
    	import("ORG.Util.UploadFile");
        $upload						= new UploadFile();                         // 实例化上传类
        $upload->maxSize			= 838860;                                   // 默认允许上传的附件大小(800K)
        $upload->allowExts			= array('smd');
        $upload->savePath           = TEMP_PATH;
		
        if(!$upload->upload()) 
        { 
            // 上传错误提示错误信息
			echo json_encode(array('error' => 1, 'message' => $upload->getErrorMsg()));
			exit;
        }
		else
		{
			
			$m_sn=M('sn',null);
			$info		= $upload->getUploadFileInfo();
			$file_url = str_replace( ROOT_PATH , "/" , $info[0]['file'] );
	        $content  =file_get_contents($file_url);
	        $sndatas=explode("\r\n",$content);
	        foreach($sndatas as $sndata)
	        {
	        	if($sndata!='')
	        	{
	        		$data=explode(',',$sndata);
	        		if($m_sn->where(array('sn'=>$data[0]))->count()==0)
	        		{$m_sn->add(array('sn'=>$data[0],'sninfo'=>$data[1]));}
	        		else
	        		{$m_sn->where(array('sn'=>$data[0]))->save(array('sn'=>$data[0],'sninfo'=>$data[1]));}
	        	}
	        }
			echo json_encode(array('error' => 0, 'message' => '','statusCode'=>0));
			exit;
		}
    }
}
?>