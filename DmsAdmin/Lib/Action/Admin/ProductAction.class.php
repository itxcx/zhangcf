<?php
//产品模块(产品列表---产品出入库)
class ProductAction extends CommonAction {
    /**
    +----------------------------------------------------------
    * 产品列表
    +----------------------------------------------------------
    */
	public function index($product){
		$Category = M($product->name."_分类");
		$categoryArr = $Category->field('名称')->order('排序 asc')->getField("名称 as name,名称");//->where(array('状态'=>'使用'))
		$list = new TableListAction($product->name);
		$setButton=array(
			"添加"=>array("class"=>"add"   ,"href"=>__APP__."/Admin/Product/add:__XPATH__","target"=>"navTab"  ,"mask"=>"true",'width'=>'600','height'=>'550'),
			"修改"=>array("class"=>"edit"  ,"href"=>__APP__."/Admin/Product/edit:__XPATH__/id/{tl_id}"  ,"target"=>"navTab"  ,"mask"=>"true",'width'=>'600','height'=>'550'),
			"删除"=>array("class"=>"delete","href"=>__APP__."/Admin/Product/delete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该产品吗？如果此产品已经被订购过，将不能使用此功能。请直接修改产品为‘下架’状态"),	
            );
		if(adminshow('pro_tc')){
        	$setButton["套餐添加产品"]=array("class"=>"add"   ,"href"=>__APP__."/Admin/Product/setpros:__XPATH__/id/{tl_id}","target"=>"navTab"  ,"mask"=>"true");
    	}
		if(adminshow('prostock')){
			$setButton["入库"]=array("class"=>"add"   ,"href"=>__APP__."/Admin/Product/add_pronum:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true",'width'=>'600','height'=>'350');
		}
		$list ->setButton = $setButton;
		$list->addshow('ID',array('row'=>'[id]',"searchMode"=>"text",'searchPosition'=>'top'));
		//$list->addshow('产品编码',array('row'=>'[产品编码]',"searchMode"=>"text",'searchPosition'=>'top'));
		$list->addshow('分类',array('row'=>'[分类]',"searchMode"=>"text",'searchPosition'=>'top','searchSelect'=>$categoryArr));
		$list->addshow('名称',array('row'=>'[名称]',"searchMode"=>"text",'searchPosition'=>'top'));
		
		if($product->image)
		{
			$list->addshow('图片',array('row'=>array(array($this,'getimg'),'[图片]'),"searchMode"=>"text"));
		}
		if($product->cost)
		{
			$list->addshow('成本价',array('row'=>'[成本价]',"searchMode"=>"num","order"=>"[成本价]"));
		}
		$list->addshow('价格',array('row'=>'[价格]',"searchMode"=>"num","order"=>"[价格]"));
		if($product->productPV){
			$list->addshow('PV',array('row'=>'[PV]',"searchMode"=>"num","order"=>"[PV]"));
		}
		
		$cons = $product->getfieldCon();
		foreach($cons as $feild){
			$list->addshow($feild['name'],array('row'=>'['.$feild['name'].']',"searchMode"=>"num"));
		}
		//显示数量
		if($product->stock){
			if(adminshow('prostock')){
				$list->addshow('未发货库存',array('row'=>'[数量]',"searchMode"=>"text"));
			}
			$list->addshow('可订购库存',array('row'=>'[可订购数量]',"searchMode"=>"text"));
		}
		//开启计算物流费		
		foreach(X('sale_*') as $sale)
		{
			if($sale->logistic && $sale->productName==$product->name){
				$list->addshow('重量',array('row'=>'[重量]',"searchMode"=>"num"));
				break;
			}
		}
		if($product->guige){
			$list->addshow('规格',array('row'=>'[规格]',"searchMode"=>"text"));
		}
		$list->addshow('添加时间',array('row'=>'[添加时间]','format'=>'time',"searchMode"=>"num","order"=>"[添加时间]"));
		//$list->addshow('修改时间',array('row'=>'[修改时间]','format'=>'time',"searchMode"=>"num","order"=>"[修改时间]"));
		$gn=M('产品_功能')->count();
		if($gn){
			$list->addshow('所属功能',array('row'=>array(array($this,'pro_gongneng'),'[所属功能]')));
		}
		$list->addshow("状态",array("row"=>array(array($this,'getstatus'),'[状态]'),"searchMode"=>"text","searchSelect"=>array("上架"=>"使用","下架"=>"不使用"),"searchPosition"=>"top",'searchRow'=>'状态'));
		$this->assign('list',$list->getHtml());
		$this->display();
	}

	//获得图片
	public function getimg($imgstr){
		if($imgstr == ''){
			return '无';
		}else{
			return '<img src='.$imgstr.' width="120" />';
		}
	}
	//所属功能
	public function pro_gongneng($ids){
		if(!empty($ids)){
	   		$chanpin = M('产品_功能')->where(array('id'=>array("in",$ids)))->getField('id,名称');
	   		return implode(',',$chanpin);
	   	}
	}
	//状态
	public function getstatus($status){
		$ary=array("使用"=>'上架',"不使用"=>"下架");
		return $ary[$status];
	}
	
	//产品添加
	public function add($product){
		$Category = M($product->name."_分类");
		$CategoryList = $Category->field('id,名称')->where(array('状态'=>'使用'))->order('排序 asc')->select();
		//查询产品功能
		$pro_gong = M('产品_功能')->where(array('状态'=>'使用'))->select();
		$pro_count = M('产品_功能')->where(array('状态'=>'使用'))->count();
		$cons = $product->getfieldCon();
		$this->assign('fields',$cons);
		$this->assign('CategoryList',$CategoryList);
		$this->assign('productPV',$product->productPV);
		$this->assign('image',$product->image);
		$this->assign('stock',$product->stock);
		$this->assign('numCheck',$product->productnumCheck);
		$this->assign('cost',$product->cost);
		$this->assign('pro_gong',$pro_gong);
		$this->assign('pro_count',$pro_count);
		$this->display();
	}
    //上传产品图片
    public function UploadPhoto()
    {
		$this->assign('id',I("get.id/d"));
        $this->display();
    }
    //上传产品图片保存
	public function UploadPhotoSave(){
		$upload = A('Admin://Public');
		$upload ->upload();
	}
	//产品添加保存
	public function addSave($product)
	{
		$model  = M($product->name);
		$data = $this -> getData($product);
		$data['添加时间'] = time(); 
		//数量不能小于0
		if(isset($data['数量'])){
			$data['可订购数量']=$data['数量'];
		}
		$pid=$model->add($data);
		if($pid){
			//入库
			if(isset($data['数量']) && $data['数量']>0 && adminshow('prostock')){
			   $indata=array(
			      '产品id'=>$pid,
			      '数量'=>$data['数量'],
			      '产品节点'=>$product->name,
			      '操作人'=>$_SESSION["loginAdminAccount"],
			      '操作时间'=>time(),
			      '备注'=>"添加产品"
			   ); 
			   M('产品库存')->add($indata);
			}
			if(I("post.submitnext/s")!="")
			{
				$this->success('添加成功!','',array('next'=>true));
			}
			else
			{
				$this->success('添加成功!','',array('next'=>false));
			}
		}else{
			$this->error('添加失败!');
		}
	}
	
	// 表单数据
	private function getData($product,$action='add'){
		$data = array();		
		if(I("post.pro_gong/a")){
	    	$data['所属功能'] = implode(',',I("post.pro_gong/a"));
		}
		$data['产品编码'] = I("post.itemid/s");
		$data['分类'] = I("post.category/s");
		$data['名称'] = trim(I("post.name/s"));
		$data['图片'] = I("post.image/s");
		$data['数量'] = abs(I("post.number/d"));
		$data['可订购数量'] = abs(I("post.number2/d"));//修改
		$data['成本价'] = abs(I("post.costprice/f"));
		$data['价格'] = abs(I("post.price/f"));
		if($product->productPV){
			$data['PV'] = abs(I("post.pv/f"));
		}
		//获取自定义价格
		$cons = $product->getfieldCon();
		foreach($cons as $feild){
			$data[$feild['name']] = abs(I("post.".$feild['name']."/f"));
		}
		$data['重量'] = I("post.wight/f");
		$data['规格'] = I("post.guige/s");
		$data['描述'] = get_magic_quotes_gpc() ? stripslashes(I("post.description/s")) : I("post.description/s");
		$data['状态'] = I("post.status/s","使用");//修改页面
		
		if($data['分类'] ===''){
			$this->error('所属分类必选!');
		}
		$pro_count = M('产品_功能')->where(array('状态'=>'使用'))->count();
		if($pro_count>0 && (!isset($data['所属功能']) || $data['所属功能']=="")){
		  	$this->error('所属功能必选');
		}
		if($data['名称'] ===''){
			$this->error('名称必填!');
		}else{
			$where=array();
			$where['名称']=$data['名称'];
			if($action=='edit') 
				$where['id']=array("neq",I("post.id/d"));
			$have=M($product->name)->where($where)->find();
			if($have){
				$this->error("同名的产品已经存在");
			}
		}
		if($action=='add' && ($product->stock || $product->productnumCheck || adminshow('prostock')) && empty($data['数量'])){
			$this->error('数量必填!');
		}
		if($data['价格'] ===''){
			$this->error('价格必填!');
		}
		return $data;
	}
    //添加产品套餐
    function setpros($product){
      	if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$id = I("get.id/d");
		$productInfo	= M($product->name)->find($id);
		if(!$productInfo){
			$this->error('该产品不存在!');
		}
        
		//查询出所有的产品功能
		$Category = M($product->name."_分类");
		$CategoryList = $Category->field('id,名称')->order('排序 asc')->select();//->where(array('状态'=>'使用'))
		$this->assign('CategoryList',$CategoryList);
		$this->assign('productInfo',$productInfo);
		$this->display();
    
    }
    function setprosSave($product){
       	$model  = M($product->name.'套餐');
        $data = $this -> gettaocanData($product,'edit');
		$data['添加时间'] = systemTime();
		M()->startTrans();
		if($model->data($data)->add()){
			M()->commit();
			$this->success('添加成功!');
		}else{
			M()->rollback();
			$this->error('操作失败!');
		}
    }
    
    // 表单数据
	private function gettaocanData($product,$action='add'){
		$data = array();
		$data['产品id'] = I("post.proid/s");
		$data['分类'] = I("post.category/s");
		$data['名称'] = trim(I("post.name/s"));
		$data['图片'] = I("post.image/s");
		$data['数量'] = abs(I("post.number/d"));
		$data['价格'] = abs(I("post.price/f"));
		$data['规格'] = I("post.guige/s");
		$data['描述'] = get_magic_quotes_gpc() ? stripslashes(I("post.description/s")) : I("post.description/s");
		$data['状态'] = I("post.status/s","使用");
		return $data;
	}

	//产品修改
	public function edit($product){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$id = I("get.id/d");
		$productInfo	= M($product->name)->find($id);
		if(!$productInfo){
			$this->error('该产品不存在!');
		}
		//将所属功能 写成一个数组
		$pro_gong = explode(',',$productInfo['所属功能']);
		$this->assign('voss',$pro_gong);
		//查询出所有的产品功能
		$Category = M($product->name."_分类");
		$CategoryList = $Category->field('id,名称')->order('排序 asc')->select();//->where(array('状态'=>'使用'))
		//查询产品功能
		$pro_gong = M('产品_功能')->where(array('状态'=>'使用'))->select();
		$pro_count = M('产品_功能')->where(array('状态'=>'使用'))->count();
		$this->assign('pro_gong',$pro_gong);
		$this->assign('pro_count',$pro_count);
		$cons = $product->getfieldCon();
		$this->assign('fields',$cons);
		$this->assign('productPV',$product->productPV);
		$this->assign('numCheck',$product->productnumCheck);
		$this->assign('CategoryList',$CategoryList);
		$this->assign('productInfo',$productInfo);
		$this->assign('image',$product->image);
		$this->assign('cost',$product->cost);
		$this->display();
	}
	// 产品修改保存
	public function editSave($product)
	{
		$model  = M($product->name);
		$data = $this -> getData($product,'edit');
		$data['修改时间'] = time();
		$where['id'] = I("post.id/s");
		M()->startTrans();
		if($model->where($where)->save($data)){
			M()->commit();
			$this->success('修改成功!');
		}else{
			M()->rollback();
			$this->error('修改失败!');
		}
	}
	//产品删除
	public function delete($product){
		$model  = M($product->name);
		$succNum = 0;
		$errNum = 0;
		if(I("get.id/s")!="")
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			$where['id'] = $id;
			M()->startTrans();
			//已经订购的不能删除，牵扯出入库联表
			$have=M("产品订单")->where(array("产品id"=>$id,"产品节点"=>$product->name))->find();
			if($have) continue;
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
	
	/**
    +----------------------------------------------------------
    * 产品出入库
    +----------------------------------------------------------
    */
    //产品入库	
	 function add_pronum($product){
	 	if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$id = I("get.id/d");
		$productInfo	= M($product->name)->find($id);
		if(!$productInfo){
			$this->error('该产品不存在!');
		}
		 $this->assign('productInfo',$productInfo);
	     $this->display();
	 }
	 //保存入库
	 function addSavepronum($product){
		$id=I("post.pid/d");
		$chanpin=M($product->name)->where(array('id'=>$id))->find();
		if(!$chanpin){
			$this->error("产品不存在");
		}
		$num=I("post.pro_num_in/d");
		if($num==0){
			$this->error("请输入入库数量");
		}
		//判断入库的数量
		if($num<0){
			$jianshu=-$num;
			if($jianshu>$chanpin['数量']){
				$this->error('出库数量不能高于产品实际库存'.$chanpin['数量']);
			}
		}
		//入库
		$indata=array(
			'产品id'=>$id,
			'数量'=>$num,
			'产品节点'=>$product->name,
			'操作人'=>$_SESSION["loginAdminAccount"],
			'操作时间'=>time(),
			'备注'=>I("post.beizhu/s")
		); 
		M()->startTrans();
		$res = M('产品库存')->add($indata);
		if($res){
			//产品增加相应库存
			$data=array();
			$data['id']=$id;
			$data['数量']=$chanpin['数量']+$num;
			$data['可订购数量']=$chanpin['可订购数量']+$num;
			if($data['可订购数量']<0) $data['可订购数量']=0;
			$save=M($product->name)->save($data);
			if($save){
				M()->commit();
				$this->success('操作成功');
			}else{
				$this->error('操作失败');
			}
		}else{
			$this->error('操作失败');
		}
	}
	 
	//入库列表
	public function addproNum($product){
		$Category = M($product->name."_分类");
		$categoryArr = $Category->field('名称')->order('排序 asc')->getField("名称 as name,名称");
		$list = new TableListAction($product->name);
		$list->table('dms_'.$product->name.' a');
		$list->join('right join dms_产品库存 as b on a.id=b.产品id');
		$list->where(array("b.数量"=>array("gt",0),"b.产品节点"=>$product->name));
		$list->field('a.*,b.数量 as nums,b.操作人,b.操作时间,b.备注');
		$list ->setButton = array(
			"入库"=>array("class"=>"add","href"=>__APP__."/Admin/Product/add_pronum:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true",'width'=>'600','height'=>'350'),
		);
		$list->addshow('产品ID',array('row'=>'[id]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"id","order"=>"id"));
		$list->addshow('名称',array('row'=>'[名称]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"名称"));
		$list->addshow('分类',array('row'=>'[分类]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"分类",'searchSelect'=>$categoryArr));
		if($product->image)
		{
			$list->addshow('图片',array('row'=>array(array($this,'getimg'),'[图片]'),"searchMode"=>"text","searchRow"=>"图片"));
		}
		$list->addshow('价格',array('row'=>'[价格]',"searchMode"=>"num","order"=>"[价格]","searchRow"=>"价格"));
		$list->addshow('入库数量',array('row'=>'[nums]',"searchMode"=>"nums","searchRow"=>"b.数量","order"=>"b.数量"));
		$list->addshow('入库时间',array('row'=>'[操作时间]','format'=>'time','searchPosition'=>'top',"searchMode"=>"date","searchRow"=>"b.操作时间","order"=>"b.操作时间"));
		$list->addshow('操作人',array('row'=>'[操作人]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"操作人"));
		$list->addshow('备注',array('row'=>'[备注]',"searchRow"=>"备注"));
		$this->assign('list',$list->getHtml());
		$this->display ();
	}
	//出库列表	
	function proOut($product)
	{
	    $Category = M($product->name."_分类");
		$categoryArr = $Category->field('名称')->order('排序 asc')->getField("名称 as name,名称");
		$list = new TableListAction($product->name);
		$list->table('dms_'.$product->name.' a');
		$list->join('right join dms_产品库存 as b on a.id=b.产品id');
		$list->where(array("b.数量"=>array("lt",0),"b.产品节点"=>$product->name));
		$list->field('a.*,b.数量 as nums,b.操作人,b.操作时间,b.备注,b.报单id');
		$list ->setButton = array(
			"入库"=>array("class"=>"add","href"=>__APP__."/Admin/Product/add_pronum:__XPATH__/id/{tl_id}","target"=>"dialog","mask"=>"true",'width'=>'600','height'=>'350'),
		);
		$list->addshow('产品ID',array('row'=>'[id]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"id","order"=>"id"));
		$list->addshow('订单ID',array('row'=>'[报单id]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"报单id","order"=>"报单id"));
		$list->addshow('名称',array('row'=>'[名称]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"名称"));
		$list->addshow('分类',array('row'=>'[分类]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"分类",'searchSelect'=>$categoryArr));
		if($product->image)
		{
			$list->addshow('图片',array('row'=>array(array($this,'getimg'),'[图片]'),"searchMode"=>"text","searchRow"=>"图片"));
		}
		$list->addshow('价格',array('row'=>'[价格]',"searchMode"=>"num","order"=>"[价格]","searchRow"=>"价格"));
		$list->addshow('出库数量',array('row'=>'[nums]',"searchMode"=>"nums","searchRow"=>"b.数量","order"=>"b.数量"));
		$list->addshow('出库时间',array('row'=>'[操作时间]','format'=>'time','searchPosition'=>'top',"searchMode"=>"date","searchRow"=>"b.操作时间","order"=>"b.操作时间"));
		$list->addshow('操作人',array('row'=>'[操作人]',"searchMode"=>"text",'searchPosition'=>'top',"searchRow"=>"操作人"));
		$list->addshow('备注',array('row'=>'[备注]',"searchRow"=>"备注"));
		$this->assign('list',$list->getHtml());
		$this->display ();
	}
}
?>