<?php
//产品分类模块---产品功能模块
class ProductCategoryAction extends CommonAction 
{
    /**
    +----------------------------------------------------------
    * 产品分类
    +----------------------------------------------------------
    */
    public function index($product)
    {
        $Category = M($product->name."_分类");
		$list = $Category->find();
		//根据product的class属性自动创建
		if(!$list && $product->class != '')
		{
			 $classs=explode(",",$product->class);
			 foreach($classs as $k=>$class)
			 {
				$data['名称'] = $class;
				$data['排序'] = $k;
				$data['创建时间'] = time();
			 	$Category->add($data);
			 }
		}
		$list = new TableListAction($product->name."_分类");
		$list ->setButton = array(
			"添加"=>array("class"=>"add"   ,"href"=>__APP__."/Admin/ProductCategory/add:__XPATH__","target"=>"dialog"  ,"mask"=>"true",'width'=>'500','height'=>'260'),
			"修改"=>array("class"=>"edit"  ,"href"=>__APP__."/Admin/ProductCategory/edit:__XPATH__/id/{tl_id}"  ,"target"=>"dialog"  ,"mask"=>"true",'width'=>'500','height'=>'260'),
			"删除"=>array("class"=>"delete","href"=>__APP__."/Admin/ProductCategory/delete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该分类吗？一旦删除，该分类下所有商品将一并删除"),	
		);
		
		$list->addshow('排序',array('row'=>'[排序]'));
		$list->addshow('名称',array('row'=>'[名称]'));
		$list->addshow('创建时间',array('row'=>'[创建时间]','format'=>'date'));
		$list->addshow('状态',array('row'=>'[状态]'));
		$this->assign('list',$list->getHtml());
		$this->display();
    }
    
    //分类添加
    public function add($product)
    {
        $Category = M($product->name."_分类");
		//获取栏目总数
        $categoryTotal  = $Category->count();
        $this->assign('categoryTotal',$categoryTotal+1);
		$this->display();
    }
    //分类添加保存
	public function addSave($product){
		$Category = M($product->name."_分类");
		if(trim(I("post.name/s")) == ''){
			$this->error('分类名称必填!');
		}else{//验证是否存在
			$have=$Category->where(array("名称"=>trim(I("post.name/s"))))->find();
			if($have){
				$this->error('此分类名称已存在!');
			}
		}
		$data['名称'] = trim(I("post.name/s"));
		$data['排序'] = I("post.sort/d");
		$data['状态'] = I("post.status/s");
		$data['创建时间'] = time();
        if( $Category->add($data) )
        {
            $this->success("添加成功!");
        }
        else
        {
            $this->error("添加失败!");
        }
	}

    //分类修改
    public function edit($product)
    {   
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$Category = M($product->name."_分类");
		$id     = I("get.id/d");
		$info   = $Category->find($id);
		if(!$info)
		{
		 	$this->error("分类不存在!");
		}
		$this->assign('info',$info);
		$this->display();
    }
    //分类修改保存--同步修改产品分类
	public function editSave($product){
		$Category = M($product->name."_分类");
		if(trim(I("post.name/s")) == ''){
			$this->error('分类名称必填!');
		}
		//验证是否存在
		$have=$Category->where(array("名称"=>trim(I("post.name/s")),"id"=>array('neq',I("post.id/d"))))->find();
		if($have){
			$this->error('此分类名称已存在!');
		}
		$data['id'] = I("post.id/d");
		//获取原分类名称
		$old=$Category->find($data['id']);
		$data['名称'] = trim(I("post.name/s"));
		$data['排序'] = I("post.sort/d");
		$data['状态'] = I("post.status/s");
		M()->startTrans();
        if($Category->save($data)){
        	//同步修改产品分类
        	M($product->name)->where(array("分类"=>$old['名称']))->save(array("分类"=>trim(I("post.name/s"))));
        	M()->commit();
            $this->success("修改成功!");
        }else{
        	M()->rollback();
			$this->success("没有修改任何数据!");
		}	
	}

    //删除分类--产品一并删除
    public function delete($product)
    {
		$succNum = 0;
		$errNum = 0; 
		foreach(explode(',',I("get.id/s")) as $id){
			if($id == '') continue;
			M()->startTrans();
			$Category   = M($product->name."_分类");
			$Product	= M($product->name);
			$cre = $Category->find($id);
			//分类下只要有产品不能删除，牵扯出入库联表和订单明细的产品id关联
			$have=$Product -> where(array("分类"=>$cre['名称']))->find();
			if($have) continue;
			if($Category->delete($id))
			{
				$succNum++;
				M()->commit();
			}
			else
			{
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
	
	/*
    +----------------------------------------------------------
    * 产品功能列表(系统维护菜单，针对不同的产品用于不同的用途（如注册，升级）)
    +----------------------------------------------------------
    */
    public function productfunction()
    {
    	$list=new TableListAction('产品_功能');
        $button=array(
          	"添加"=>array("class"=>"add","href"=>__APP__."/Admin/ProductCategory/productfunction_add","target"=>"dialog",'width'=>'600','height'=>'300','icon'=>'/Public/Images/ExtJSicons/application/application_form_add.png'),
			"修改"=>array("class"=>"edit","href"=>__APP__."/Admin/ProductCategory/productfunction_edit/id/{tl_id}","target"=>"dialog",'width'=>'600','height'=>'300','icon'=>'/Public/Images/ExtJSicons/application/application_link.png'),
			"删除"=>array("class"=>"delete","href"=>__APP__."/Admin/ProductCategory/productfunction_delete/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该数据吗？",'icon'=>'/Public/Images/ExtJSicons/application/application_form_delete.png')
        );
        $list->setButton = $button;
        $list->addshow("名称",array("row"=>'[名称]'));
        $list->addshow("创建时间",array("row"=>"[创建时间]","format"=>"time"));
        $list->addshow("所属功能",array("row"=>"[节点名称]"));
		$list->addshow("状态",array("row"=>"[状态]","searchMode"=>"text","searchSelect"=>array("使用"=>"使用","不使用"=>"不使用"),"searchPosition"=>"top",'searchRow'=>'状态'));
        $this->assign('list',$list->getHtml());
        $this->display();
    }
    
    //产品功能添加
    public function productfunction_add(){
    	//对各种sale的操作进行输出	
    	$sales = X('sale_*');
		//输出订单操作的菜单$sale->name
		$pros = array();
		foreach($sales as $key=>$v){
			if($v->productName){
			   $pros[$key] = $v->name;
			}
		}
		$this->assign('sales',$pros);
	    $this->display();
    }
    //功能保存
    public function productfunction_addSave(){
      	if(I("post.gongneng_name/s")==''){
      		$this->error("请填写功能名称");
      	}
		if(count(I("post.jiedian/a"))<=0){
			$this->error('请选择所属功能');
		}
		//保存数据
		$data = array(
			'名称'=>I("post.gongneng_name/s"),
			'创建时间'=>systemTime(),
			'节点名称'=>implode(',',I("post.jiedian/a")),
			'状态'=>I("post.status/s"),
		);
		$data_add = M('产品_功能')->data($data)->add();
		if($data_add){
			$this->success('添加成功');
		}else{
			$this->error('操作失败');
		}
    }
   //修改产品功能
   	public function productfunction_edit(){
		$id = I("get.id/d");
		$res = M('产品_功能')->where(array('id'=>$id))->find();
		$sales = X('sale_*');
		foreach($sales as $key=>$v){
			if($v->productName){
				$pros[$key] = $v->name;
			}
		}
		//输出订单操作的菜单$sale->name
		$this->assign('sales',$pros);
		$jiedian = explode(',',$res['节点名称']);
		$this->assign('jiedian',$jiedian);
		$this->assign('res',$res);
		$this->display();
   	}
   	//保存 产品功能
   	public function productfunction_editSave(){
		$id = I("post.id/d");
		if(I("post.gongneng_name/s")==''){
			$this->error("请填写功能名称");
		}
		if(count(I("post.jiedian/a"))<=0){
			$this->error('请选择所属功能');
		}
		//保存数据
		$data = array(
			'名称'=>I("post.gongneng_name/s"),
			'创建时间'=>systemTime(),
			'节点名称'=>implode(',',I("post.jiedian/a")),
			'状态'=>I("post.status/s"),
		);
		M()->startTrans();
		$data_add = M('产品_功能')->where(array('id'=>$id))->save($data);
		if($data_add){
			M()->commit();
			$this->success('操作成功');
		}else{
			M()->rollback();
			$this->error('操作失败');
		}
   	}
	//删除产品功能
	public function productfunction_delete(){
		//判断是否有产品存在
		foreach(X("product") as $product){
			$have=M($product->name)->where(array("_string"=>"find_in_set('".I("get.id/s")."',所属功能)"))->find();
			if($have)$this->error("此功能下已有".$product->byname."存在");
		}
		M()->startTrans();
		if(M('产品_功能')->where(array('id'=>I("get.id/d")))->delete()){
			M()->commit();
			$this->success('操作成功');
		}else{
			M()->rollback();
			$this->error('操作失败');
		}
	}
}
?>