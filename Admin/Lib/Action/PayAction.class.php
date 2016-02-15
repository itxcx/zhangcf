<?php
/*
* 名称：支付模块
* 版本：Ver 3.1.40
* 修档：2015/07/22
* 开发者：0025
* 验收人：冯露露
* 版权归属：临沂市新商网络技术有限公司
*/
class PayAction extends CommonAction{
	
	//接口相关数据数组
	public $interface_data=array(
								'app'=>'Admin',											//接口存放于那个项目目录下
								'subpath'=>'/Lib/Pay',									//处于app目录下的子路径(注意路径大小写！)
								'path'=>'',												//接口文件目录完整路径,ROOT_PATH.$this->interface_data['app'].$this->interface_data['subpath']
								'names'=>'',											//所有接口名称[数组]
								'data'=>'',												//处理后的所有接口信息[数组]
								'new_names'=>false,										//当扫描到新接口时，新接口名字[数组]
								'new_static'=>false,									//出现新数组是增加还是减少，增加是TRUE，减少是FALSE
								'number'=>'',											//接口总数量
								'discharge'=>array('.','..','.svn','Pay.class.php'),	//排出目录类非接口程序文件
								'update_read'=>false,									//当发现有新接口时，是否从新读取所有接口程序（用于支付接口更名等操作）
								'rest_read'=>true										//是否每次都从新读取所有接口程序（用于支付接口更名等操作)
							);
	//接口错误信息
	public $interface_error=array(
								'1'=>'路径错误，无法找到接口程序文件夹'
							);
	//构造方法
	public function __construct(){
		parent::__construct();
		$this->interface_data['path']=ROOT_PATH.$this->interface_data['app'].$this->interface_data['subpath'];
		$this->rest();
		if(MD5(json_encode(F('installedPayment')))!=CONFIG('INPAYMD5')){
			M()->startTrans();
			//生成缓存数据
			import("Admin.Pay.Pay");
			Pay::iud();
			M()->commit();
		}
	}
	
	//析构方法
	public function __destruct(){
		unset($this->interface_data);
	}
	
	//模块自动收集接口信息
	protected function rest(){
		$this->interface_list();
		if(F('interface_names') && F('interface_num')){										//接口列表缓存和接口数量缓存文件存在
			if($this->interface_data['number']==F('interface_num')){						//扫描数量与缓存数量一致
				$this->interface_data['names']=F('interface_names');						//直接读取缓存接口列表（节省循环处理新接口列表）
				$this->read_info($this->interface_data['rest_read']);
			}elseif($this->interface_data['number']>F('interface_num')){					//扫描数量大于缓存数量
				$this->interface_names();
				$this->interface_data['new_names']=array_values(array_diff($this->interface_data['names'],F('interface_names')));	//获取加入的所有接口
				$this->optimize1();
				$this->interface_data['new_static']=true;
				$this->read_info(false);
			}else{																			//扫描数量小于缓存数量
				$this->interface_names();
				$this->interface_data['new_names']=array_values(array_diff(F('interface_names'),$this->interface_data['names']));	//获取去掉的所有接口
				$this->optimize1();
				$this->read_info(false);
			}
		}else{
			$this->interface_names();
			$this->optimize1();
			$this->read_info(true);															//初始化时读取所有接口
		}
	}
	
	//获取支付目录列表和数量
	protected function interface_list(){
		if(is_dir($this->interface_data['path'])){
			$this->interface_data['names']=scandir($this->interface_data['path']);
			$this->interface_data['names']=array_values(array_diff($this->interface_data['names'],$this->interface_data['discharge']));
			$this->interface_data['number']=count($this->interface_data['names']);
		}else{
			$this->error_msg(1);
		}
	}
	
	//获取支付接口名称
	protected function interface_names(){
		foreach($this->interface_data['names'] as &$value){
			$a=explode('.',$value);
			$value=$a[0];
		}
	}
	
	//错误信息
	protected function error_msg($num){
		echo $this->interface_error[$num];
		return false;
	}
	
	//精简语句方法1
	protected function optimize1(){
		F('interface_num',$this->interface_data['number']);	
		F('interface_names',$this->interface_data['names']);
	}
	
	//精简语句方法2
	protected function optimize2($name){
		foreach($this->interface_data[$name] as $pay){
			$this->interface_data['data'][$pay]= Pay::getPayInfo($pay);
		}
	}
	
	//获取接口相关信息（访问接口静态类）
	protected function read_info($sta){
		import($this->interface_data['app'].".Pay.Pay");
		if($sta){
			$this->optimize2('names');
			F('interface_data',$this->interface_data['data']);
		}else{
			if(empty($this->interface_data['new_names'])){
				$this->interface_data['data']=F('interface_data');
			}else{
				if($this->interface_data['new_static']){			//数据增加操作
					if($this->interface_data['update_read']){
						$this->optimize2('names');
					}else{
						$this->optimize2('new_names');
						$this->interface_data['new_static']=F('interface_data');
						$this->interface_data['data']=array_merge($this->interface_data['new_static'],$this->interface_data['data']);
					}
					F('interface_data',$this->interface_data['data']);
				}else{												//先读缓存，在删除数据
					if($this->interface_data['update_read']){
						$this->optimize2('names');
					}else{
						$this->interface_data['data']=F('interface_data');
						foreach($this->interface_data['new_names'] as $value){
							unset($this->interface_data['data'][$value]);
						}
					}
					F('interface_data',$this->interface_data['data']);
				}
			}
		}
	}
	
	//已安装支付接口列表
	public function index(){
		$list=new TableListAction('pay_onlineaccount');
        $list->excel=false;
        $list->autoLoad = false; 
        $list->order("id asc");
		$button=array(
			"安装支付"=>array("class"=>"add"   ,"href"=>__URL__."/install","target"=>"dialog","mask"=>"true",'width'=>'600','height'=>'500'),
            "编辑支付"=>array("class"=>"edit"  ,"href"=>__URL__."/pay_edit/id/{tl_id}","target"=>"dialog",'width'=>'600','height'=>'500',"mask"=>"true"),
			"删除支付"=>array("class"=>"delete","id"=>"deljk","href"=>__URL__."/pay_delete/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？")
        );
        $list->setButton = $button;
		$list->addshow("ID",array("row"=>'[id]'));
		$list->addshow("支付名称",array("row"=>'[name]'));
		$list->addshow("英文名称",array("row"=>'[pay_type]'));
		$list->addshow("支付账号",array("row"=>'[account]'));
		$list->addshow("累计收款",array("row"=>'[pay_amount]'));
		$list->addshow("生效状态",array("row"=>array(array(&$this,"tpprtin"),'[state]','正常','暂停')));
		$list->addshow("是否直连",array("row"=>'[credit]'));
		$this->assign('list',$list->getHtml());
		$this->display();
	}
	
	//已安装支付接口列表附属函数，是否支持直连
	public function tpprtin($state,$yes,$no){
		if($state==1){
			return $yes;
		}else{
			return $no;
		}
	}
	
	//AJAX调用接口配置
	public function config(){
		if(I("post.interface/s")!="" && !empty($this->interface_data['data'][I("post.interface/s")])){
			$interface=$this->_post("interface","htmlspecialchars");
			import($this->interface_data['app'].'.Pay.'.$interface);
			$result=$interface::getConfigInfo();
			$credit=$interface::getBankList();
			$this->assign('payInfo',$result);
			if(empty($credit)){
				$credit='No';
			}
			$this->assign('credit',$credit);
			$this->display();
		}
	}
	
	// 安装支付接口页面
	public function install(){
		$this->optimize2('names');
        $this->assign('data',$this->interface_data['data']);
		$this->display();
	}
	
	//配置接口，添加支付账号
	public function do_add(){
		//??
		if(I("post.payment/s")!="" && I("post.attr/a") && I("post.name/a")){
			$type=$this->_post("payment","htmlspecialchars");
		    $attr=$this->_post("attr","htmlspecialchars");
		    $name=$this->_post("name","htmlspecialchars");
		    $state=$this->_post("state","htmlspecialchars");
		    //去空格处理
		    foreach($attr as &$att)
		    {
		    	$att=trim($att);
		    }
		    $add_data = array(
		      'pay_type'=>$type,
		      'pay_attr'=>serialize($attr),
		      'pay_name'=>serialize($name),
		      'pay_amount'=>0,
		      'name'=>$attr[$type.'_name'],
		      'account'=>$attr[$type.'_account'],
		      'state'=>$state,
		      'credit'=>(isset($attr[$type.'_credit']) && $attr[$type.'_credit'])?$attr[$type.'_credit']:'No',
		      'time'=>systemTime()
			);
            M()->startTrans();
			$res=M('pay_onlineaccount',' ')->data($add_data)->add();
			if($res){
				//不管有没有直联都要记录到banklist 防止支付在返回处理时出错
				 $banklist = F('banklist');
                 if(I("post.secondSelect/a")){
                 	 //记录直联银行
                     $banklist[$type] = $this -> _post("secondSelect", "htmlspecialchars");
                 }else{
                 	 //记录空数组
                     if($banklist=="")
                         $banklist=array();
                    $banklist[$type]=array();
                 }
                 //写入缓存
                 F('banklist',$banklist);
				//生成缓存数据
				import("Admin.Pay.Pay");
				Pay::iud();
				M()->commit();
				$this->success("安装成功!");
			}else{
				M()->rollback();
				$this->error("安装失败!");
			}
			unset($type,$attr,$name,$state,$add_data,$res,$banklist);
		}
	}
	
	//删除支付账号
	public function pay_delete(){
	   	if(I("get.id/d")){
		   	$id=$this->_get("id","htmlspecialchars");
		   	M()->startTrans();
			$res = M('pay_onlineaccount',' ')->where(array('id'=>$id))->delete();
			if($res){
				//生成缓存数据
				import("Admin.Pay.Pay");
				Pay::iud();
				M()->commit();
				$this->success('删除成功');
			}else{
				M('pay_onlineaccount',' ')->rollback();
				$this->error('操作失败');
			}
			unset($id,$res);
		}
	}
	
	//修改支付账号页面
	public function pay_edit(){
		if(I("get.id/d")){
			$id=$this->_get("id","htmlspecialchars");
			$res=M('pay_onlineaccount',' ')->where(array('id'=>$id))->find();
			$res['pay_name'] = unserialize($res['pay_name']);
			$res['pay_attr'] = unserialize($res['pay_attr']);
			import($this->interface_data['app'].'.Pay.'.$res['pay_type']);
			$payInfo = $res['pay_type']::getConfigInfo($res['pay_type']);
			$credit=$res['pay_type']::getBankList();
			if(empty($credit)){
				$credit='No';
			}
			$this->assign('credit',$credit);			
			$credit1=F('banklist');$credit2=array();
			if(isset($credit1[$res['pay_type']])){
				$credit2=array_fill_keys($credit1[$res['pay_type']],1);
			}
			foreach($payInfo as $key=>$v){
				$name = $v['config_name'];
			    $payInfo[$key]['config_value'] = $res['pay_attr'][$name];
			}
			$this->assign('payment',$res['pay_type']);
			$this->assign('payInfo',$payInfo);
			$this->assign('state',$res['state']);
			$this->assign('ids',$id);
			$this->assign('credit',$credit);
			$this->assign('bank',$credit);
			$this->assign('select',$credit2);
			$this->display();
			unset($id,$res,$payInfo,$credit,$credit1,$credit2);
	    }
	}

	//更新数据库支付账号配置
	public function do_pay_edit(){
		if(I("post.ids/d")>0 && I("post.name/a") && I("post.attr/a") && I("post.state/d")){
			//数据组合过滤
			$ids=$this->_post("ids","htmlspecialchars");
			$payment=$this->_post("payment","htmlspecialchars");
		    $name=$this->_post("name","htmlspecialchars");
		    $attr=$this->_post("attr","htmlspecialchars");
		    //去空格处理
		    foreach($attr as &$att)
		    {
		    	$att=trim($att);
		    }
		    //修改的数据
		    $res['state']=$this->_post("state","htmlspecialchars");
			$res['pay_name'] = serialize($name);
			$res['pay_attr'] = serialize($attr);
			$res['credit'] = isset($attr[$payment.'_credit'])?$attr[$payment.'_credit']:'No';
			$res['account'] = $attr[$payment.'_account'];
			M()->startTrans();
			//保存到数据库数据
			$upd_res = M('pay_onlineaccount',' ')->where(array('id'=>$ids))->save($res);
			//获取安装的数据 读取文件
			$banklist=F('banklist')?F('banklist'):array();
			if(!is_array($banklist)){
				$banklist=array();
			}
			//组合本次的安装数据
			$banklist[$payment]=$this->_post("secondSelect","htmlspecialchars")?$this->_post("secondSelect","htmlspecialchars"):array();
			//写入文件
			F('banklist',$banklist);
			//生成缓存数据
			import("Admin.Pay.Pay");
			Pay::iud();
			M()->commit();
			$this->success('修改成功');
		}
	}
	
}
?>