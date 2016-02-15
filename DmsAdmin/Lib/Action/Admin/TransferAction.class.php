<?php
// 本类由系统自动生成，仅供测试用途
defined('APP_NAME') || die('不要非法操作哦!');
class TransferAction extends CommonAction{
	//货币转账设置明细
	public function index()
	{
        $setButton=array(                 // 底部操作按钮显示定义
			'添加转账'    =>array("class"=>"add","href"=>"__URL__/add",'target'=>"dialog",'mask'=>"true",'title'=>"添加转账配置",'height'=>"550",'width'=>"520"),
			'修改转账'    =>array("class"=>"edit","href"=>"__URL__/edit/id/{tl_id}","target"=>"dialog",'title'=>'修改转账','mask'=>true,'height'=>"550",'width'=>"520"),
			'删除转账'    =>array("class"=>"delete","href"=>"__URL__/del/id/{tl_id}","target"=>"ajaxTodo","title"=>"确定要删除吗?"),
			'高级设置'    =>array("class"=>"addMore","href"=>"__URL__/givemoneyconfig",'target'=>"dialog",'mask'=>"true",'title'=>"转账总配置",'icon'=>'/Public/Images/ExtJSicons/cog.png'),
        );
        $list=new TableListAction("转账设置");
        $list->setButton = $setButton;// 定义按钮显示
        $list->order("time desc,id desc");
        $list->addshow("标题",array("row"=>"[title]"));
        //$list->addshow("转出货币",array("row"=>"[bank]","searchMode"=>"text",'searchGet'=>'bank',"excelMode"=>"text","searchPosition"=>"top"));   
		//$list->addshow("转入货币",array("row"=>"[tobank]","searchMode"=>"text",'searchGet'=>'tobank',"excelMode"=>"text","searchPosition"=>"top"));
		$list->addShow("转账货币",array("row"=>array(array($this,'transfer'),"[bank]","[tobank]")));
        $list->addShow("转账类型",array("row"=>array(array($this,'istome'),"[tome]","[toyou]")));
        $list->addshow("转账税",array("row"=>"[tax]","searchMode"=>"num",'searchRow'=>'tax'));
        $list->addshow("最大额",array("row"=>"[maxnum]","searchMode"=>"num",'searchRow'=>'maxnum'));
        $list->addshow("最小额",array("row"=>"[minnum]","searchMode"=>"text",'searchRow'=>'minnum'));
        $list->addshow("整数额",array("row"=>"[intnum]","searchMode"=>"text",'searchRow'=>'intnum'));
        $list->addshow("网络体系限定",array("row"=>array(array($this,'netview'),"[nets]")));
        $list->addShow("状态",array("row"=>array(array($this,'status'),"[status]")));
        $this->assign('list',$list->getHtml());
        $this->display();
	}
	//转账货币
	public function transfer($outbank,$tobank)
	{
		$outbyname = X('@'.$outbank)->byname;
		$tobyname  = X('@'.$tobank)->byname;
		return $outbyname."<span style='font-size:18px;color:#FF0000;'>→</span>".$tobyname;
	}
	//网络体系限定显示
	public function netview($nets)
	{
		foreach(X('net_rec,net_place') as $net){
			if($nets == $net->name)
			{
				return $net->byname;
			}
			if($nets == $net->name.'上级')
			{
				return $net->byname.'上级';
			}
			if($nets == $net->name.'下级')
			{
				return $net->byname.'下级';
			}
		}	
		return '无';
	}
	//是否转给自己
    public function istome($me,$you)
    {
    	if($me && $you)
    	{
    		return "转给自己和其他会员";
    	}else{
    		if($me)
			{
				return '转给自己';
			}
			if($you)
			{
				return '转给其他会员';
			}
    	}
    }
    //判断转账状态是否开启
    public function status($str){
       if($str)
       {
       		return '开启';
       }else{
       		return '关闭';
       }
    }
    //添加转账功能
    public function add()
    {
    	$banks = X('fun_bank');
    	$banknames = array();
   		foreach($banks as $key=>$bank)
   		{
   			$banknames[$bank->name] = $bank->byname;
   		}
   		$netset="";
		foreach(X('net_rec,net_place') as $net){
			$netset[$net->name] = $net->byname;
			$netset[$net->name.'上级']=$net->byname.'上级';
			$netset[$net->name.'下级']=$net->byname.'下级';
		}
        $this->assign('netsets',$netset);
   		$this->assign('banknames',$banknames);
    	$this->display();
    }
    //提交转账设置
    public function addsave()
    {
    	//验证转账类型是否选择
    	if(!I("post.toyou/b") && !I("post.tome/b"))
    	{
    		$this->error('请选择转账类型!');
    	}
    	//标题判断	
    	if(I("post.title/s")=="")
    	{
    		$this->error('前填写标题!');
    	}
    	//判断标题名字是否存在
    	$haves = M('转账设置')->where(array('title'=>I("post.title/s")))->find();
    	if($haves)
    	{
    		$this->error('标题名已存在,请重新命名!');
    	}
    	M()->startTrans();
    	//组合转账类型数组
    	$data =array();
    	$verdata = array();
    	//标题
    	$data['title'] = I("post.title/s");
    	$verdata['title'] = I("post.title/s");
    	//转出货币
    	$data['bank'] = I("post.outbank/s");
    	$verdata['bank'] = I("post.outbank/s");
    	//转入货币
    	$data['tobank'] = I("post.tobank/s");
    	$verdata['tobank'] = I("post.tobank/s");
    	//转账给自己
    	if(I("post.tome/b"))
    	{
    		$data['tome'] = 1;
    	}else{
    		$data['tome'] = 0;
    	}
    	//转账给其他人
    	if(I("post.toyou/b"))
    	{
    		$data['toyou'] = 1;
    		$toyoutype="";
            if(I("post.toyoutype/a"))
                $toyoutype=implode(',',I("post.toyoutype/a"));
            $data['toyoutype']=$toyoutype;
    	}else{
    		$data['toyou'] = 0;
    		$data['toyoutype']="";
    	}
    	//转账手续费来源 
    	$data['taxfrom'] = I("post.taxfrom/d");
    	//转账手续费
    	$data['tax'] = I("post.tax/f");
    	$verdata['tax'] = I("post.tax/f");
    	//转账手续费上限
    	$data['taxtop'] = I("post.taxtop/f");
    	$verdata['taxtop'] = I("post.taxtop/f");
    	//转账手续费下限
    	$data['taxlow'] = I("post.taxlow/f");
    	$verdata['taxlow'] = I("post.taxlow/f");
    	//转账转换比率
    	$data['sacl'] = I("post.sacl/f");
    	$verdata['sacl'] = I("post.sacl/f");
    	//转账最大金额
    	$data['maxnum'] = I("post.max/f");
    	$verdata['maxnum'] = I("post.max/f");
    	//转账最小金额
    	$data['minnum'] = I("post.min/f");
    	$verdata['minnum'] = I("post.min/f");
    	//转账最整数倍额
    	$data['intnum'] = I("post.intnum/f");
    	$verdata['intnum'] = I("post.intnum/f");
    	//转账的网体
    	$data['nets'] = I("post.nets/s");
    	$verdata['nets'] = I("post.nets/s");
    	//服务中心限定
    	$data['shop'] = I("post.shop/s");
    	$verdata['shop'] = I("post.shop/s");
    	//对提交数据验证是否已经存在转账类型
    	$num1 = M('转账设置')->where($data)->count();
		if($num1>0)
		{
			$this->error('转账类型已存在!');
		}
    	//验证转账是否存在
    	if(!I("post.tome/b") || !I("post.toyou/b"))
    	{
    		$verdata['tome'] = 0;
    		$verdata['toyou'] = 1;
    		$num2 = M('转账设置')->where($verdata)->count();
    		$verdata['tome'] = 1;
    		$verdata['toyou'] = 0;
    		$num3 = M('转账设置')->where($verdata)->count();
    		if($num2>0 || $num3>0)
    		{
    			$this->error('转账类型已存在!');
    		}
    	}
    	//验证转账是否存在
		$verdata['toyou'] = 1;
		$verdata['tome'] = 1;
		$num4 = M('转账设置')->where($verdata)->count();
		if($num4>0)
		{
			$this->error('转账类型已存在!');
		}
    	//状态
    	$data['status'] = I("post.status/d");
    	//更新时间
    	$data['time'] = systemTime();
    	//数据添加
   		$result = M('转账设置')->add($data);
   		//开启转账
        CONFIG('giveMoney',1);
   		if($result)
   		{
   			M()->commit();
   			$this->success('转账设置成功!');
   		}else{
   			$this->error('转账设置失败!');
   		}
    }
    //执行删除操作
    public function del()
    {
    	$model=M('转账设置');
    	$succNum = 0;
		$errNum = 0;
		if(I("get.id/s")!="")
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$where['id'] = $id;
			M()->startTrans();
			if($model->where($where)->delete()){
				$succNum++;
				M()->commit();
			}else{
				$errNum++;
				M()->rollback();
			}
		}
		if($errNum !=0){
			$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
		}else{
			$this->success("删除成功：".$succNum .'条记录；');
		}
    }
    //修改操作
    public function edit()
    {
    	if(strpos(I("get.id/s"),',')!== false){
			$this->error('参数错误!');
		}
		//查询要修改的数据
		$bankdata = M('转账设置')->where(array('id'=>I("get.id/d")))->find();
		$bankdata['bynets']="";
		if($bankdata)
		{
			$outbank=X('@'.$bankdata['bank']);
			$tobank =X('@'.$bankdata['tobank']);
			$bankdata['bankname']  =$outbank->byname;
			$bankdata['tobankname']=$tobank->byname;
			$netset="";
			foreach(X('net_rec,net_place') as $net){
				$netset[$net->name] = $net->byname;
				$netset[$net->name.'上级']=$net->byname.'上级';
				$netset[$net->name.'下级']=$net->byname.'下级';
				if($bankdata['nets'] == $net->name)
				{
					$bankdata['bynets'] =  $net->byname;
				}
				if($bankdata['nets'] == $net->name.'上级')
				{
					$bankdata['bynets'] =  $net->byname.'上级';
				}
				if($bankdata['nets'] == $net->name.'下级')
				{
					$bankdata['bynets'] = $net->byname.'下级';
				}
			}
	        $this->assign('netsets',$netset);
			$this->assign('bankdata',$bankdata);
			$toyoutype=isset($bankdata['toyoutype'])?explode(',',$bankdata['toyoutype']):array();
            $this->assign('toyoutype',$toyoutype);
			$this->display();
		}else{
			$this->error('信息有误!');	
		}
    }
     //提交转账设置
    public function editSave()
    {
    	if(!I("post.toyou/b") && !I("post.tome/b"))
    	{
    		$this->error('请选择转账类型!');
    	}
    	M()->startTrans();
    	//组合转账类型数组
    	$data =array();
    	$verdata = array();
    	//转账给自己
    	if(I("post.tome/b"))
    	{
    		$data['tome'] = 1;
    	}else{
    		$data['tome'] = 0;
    	}
    	//转账给其他人
    	if(I("post.toyou/b"))
    	{
    		$data['toyou'] = 1;
    		$toyoutype="";
            if(I("post.toyoutype/a"))
                $toyoutype=implode(',',I("post.toyoutype/a"));
            $data['toyoutype']=$toyoutype;
    	}else{
    		$data['toyou'] = 0;
    		$data['toyoutype']="";
    	}
    	//转账手续费来源
    	$data['taxfrom'] = I("post.taxfrom/d");
    	//转账手续费
    	$data['tax'] = I("post.tax/f");
    	//转账最小手续费
    	$data['taxlow'] = I("post.taxlow/f");
    	//转账最大手续费
    	$data['taxtop'] = I("post.taxtop/f");
    	//转账转换比率
    	$data['sacl'] = I("post.sacl/f");
    	//转账最大金额
    	$data['maxnum'] = I("post.max/f");
    	//转账最小金额
    	$data['minnum'] = I("post.min/f");
    	//转账最整数倍额
    	$data['intnum'] = I("post.intnum/f");
    	//转账的网体
    	$data['nets'] = I("post.nets/s");
    	//服务中心限定
    	$data['shop'] = I("post.shop/s");
    	//获取修改的数据
    	$olddata =  M('转账设置')->where(array('id'=>I("post.id/d")))->find();
    	$verdata = $data;
    	$verdata['title'] = $olddata['title'];
    	$verdata['bank'] = $olddata['bank'];
    	$verdata['tobank'] = $olddata['tobank'];
    	$verdata['id'] = array('neq',$olddata['id']);
    	//对提交数据验证是否已经存在转账类型
    	$num1 = M('转账设置')->where($verdata)->count();
		if($num1>0)
		{
			$this->error('转账类型已存在!');
		}
    	//验证转账是否存在
    	if(!I("post.tome/b") || !I("post.toyou/b"))
    	{
    		$verdata['tome'] = 0;
    		$verdata['toyou'] = 1;
    		$num2 = M('转账设置')->where($verdata)->count();
    		$verdata['tome'] = 1;
    		$verdata['toyou'] = 0;
    		$num3 = M('转账设置')->where($verdata)->count();
    		if($num2>0 || $num3>0)
    		{
    			$this->error('转账类型已存在!');
    		}
    	}
    	//验证转账是否存在
		$verdata['toyou'] = 1;
		$verdata['tome'] = 1;
		$num4 = M('转账设置')->where($verdata)->count();
		if($num4>0)
		{
			$this->error('转账类型已存在!');
		}
    	//状态
    	$data['status'] = I("post.status/d");
    	//更新时间
    	$data['time'] = systemTime();
    	//数据添加
   		$result = M('转账设置')->where(array('id'=>I("post.id/d")))->save($data);
   		if($result)
   		{
   			M()->commit();
   			$this->success('转账修改成功!');
   		}else{
   			$this->error('转账修改失败!');
   		}
    }
    public function givemoneyconfig(){
    	$this->assign('pwd3Switch',adminshow('pwd3Switch'));
		$this->assign('giveMoney',CONFIG('giveMoney'));
		$this->assign('sureGiveMoney',CONFIG('sureGiveMoney'));
		$this->assign('giveMoneyPass2',CONFIG('giveMoneyPass2'));
		$this->assign('giveMoneyPass3',CONFIG('giveMoneyPass3'));
		$this->assign('giveMoneySmsSwitch',CONFIG('giveMoneySmsSwitch'));
		$this->assign('giveMoneySmsContent',CONFIG('giveMoneySmsContent'));
		$this->display();
    }
    //系统设置更新
    public function gmconfigsave()
	{
		$data=array();
	
		$giveMoney  		   = I("post.giveMoney/f");
		$sureGiveMoney  	   = I("post.sureGiveMoney/d");
		$giveMoneyPass2        = I("post.giveMoneyPass2/d");
		$giveMoneyPass3        = I("post.giveMoneyPass3/d");
		$giveMoneySmsSwitch    = I("post.giveMoneySmsSwitch/d");
		$giveMoneySmsContent   = I("post.giveMoneySmsContent/s");;
		M()->startTrans();
		CONFIG('giveMoney',$giveMoney);
		CONFIG('sureGiveMoney',$sureGiveMoney);
		CONFIG('giveMoneyPass2',$giveMoneyPass2);
		CONFIG('giveMoneyPass3',$giveMoneyPass3);
		CONFIG('giveMoneySmsSwitch',$giveMoneySmsSwitch);
		CONFIG('giveMoneySmsContent',$giveMoneySmsContent);
		M()->commit();
		$this->saveAdminLog('',I("post."),"转账设置","转账参数设置");
        $this->success('修改完成',__URL__.'/givemoneyconfig');
	}
}
?>