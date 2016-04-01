<?php
//产品模块(产品列表---产品出入库)
class ProducttaosetAction extends CommonAction {
    /**
    +----------------------------------------------------------
    * 产品列表
    +----------------------------------------------------------
    */
	public function index($product){
		$Category = M($product->name."_分类");
		$categoryArr = $Category->field('名称')->order('排序 asc')->getField("名称 as name,名称");//->where(array('状态'=>'使用'))
		$list = new TableListAction($product->name."套餐");
        $list->table('dms_'.$product->name.'套餐 a inner join dms_'.$product->name.' b on a.产品id=b.id');
        $list->field('a.*,b.名称 as taocan');
		$setButton=array(
			"修改"=>array("class"=>"edit"  ,"href"=>__APP__."/Admin/Producttaoset/edit:__XPATH__/id/{tl_id}"  ,"target"=>"navTab"  ,"mask"=>"true",'width'=>'600','height'=>'550'),
			"删除"=>array("class"=>"delete","href"=>__APP__."/Admin/Producttaoset/delete:__XPATH__/id/{tl_id}","target"=>"ajaxTodo","mask"=>"true","title"=>"确定要删除该套餐里面的此产品吗？"),	
            );
		$list ->setButton = $setButton;
		$list->addshow('ID',array('row'=>'[id]',"searchMode"=>"text",'searchPosition'=>'top','searchRow'=>'[a.id]'));
		$list->addshow('所属套餐',array('row'=>'[taocan]',"searchMode"=>"text",'searchPosition'=>'top','searchRow'=>'[b.名称]'));
		$list->addshow('分类',array('row'=>'[分类]',"searchMode"=>"text",'searchPosition'=>'top','searchSelect'=>$categoryArr,'searchRow'=>'[a.分类]'));
		$list->addshow('名称',array('row'=>'[名称]',"searchMode"=>"text",'searchPosition'=>'top','searchRow'=>'[a.名称]'));
		$list->addshow('图片',array('row'=>array(array($this,'getimg'),'[图片]')));
		$list->addshow('价格',array('row'=>'[价格]',"searchMode"=>"num","order"=>"[价格]",'searchRow'=>'[a.价格]'));
		$list->addshow('数量',array('row'=>'[数量]',"searchMode"=>"text",'searchRow'=>'[a.数量]'));
		$list->addshow('规格',array('row'=>'[规格]',"searchMode"=>"text",'searchRow'=>'[a.规格]'));
		$list->addshow('添加时间',array('row'=>'[添加时间]','format'=>'time',"searchMode"=>"date","order"=>"[添加时间]",'searchRow'=>'[a.添加时间]'));
		$list->addshow('修改时间',array('row'=>'[修改时间]','format'=>'time',"searchMode"=>"date","order"=>"[修改时间]",'searchRow'=>'[a.修改时间]'));
	    $list->addshow("状态",array("row"=>array(array($this,'getstatus'),'[状态]'),"searchMode"=>"text","searchSelect"=>array("上架"=>"使用","下架"=>"不使用"),"searchPosition"=>"top",'searchRow'=>'a.状态'));
		$this->assign('list',$list->getHtml());
		$this->display();
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
	//状态
	public function getstatus($status){
		$ary=array("使用"=>'上架',"不使用"=>"下架");
		return $ary[$status];
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
    
    //修改产品套餐
	public function edit($product){
		if(strpos(I("get.id/s"),',') !== false){
			$this->error('参数错误!');
		}
		$id = I("get.id/d");
		$productInfo	= M($product->name.'套餐')->find($id);
		if(!$productInfo){
			$this->error('该产品不存在!');
		}
        //查询产品的信息
        $taocans = M($product->name)->where(array('id'=>$productInfo['产品id']))->find();
		//查询出所有的产品功能
		$Category = M($product->name."_分类");
		$CategoryList = $Category->field('id,名称')->order('排序 asc')->select();//->where(array('状态'=>'使用'))
		$this->assign('CategoryList',$CategoryList);
		$this->assign('productInfo',$productInfo);
       	$this->assign('taocans',$taocans);
		$this->display();
	}
    //保存套餐产品
    function editSave($product){
       	$model  = M($product->name.'套餐');
        $data = $this -> gettaocanData($product,'edit');
		$data['修改时间'] = systemTime();
        $where['id'] = I("post.id/s");
		M()->startTrans();
		if($model->where($where)->save($data)){
			M()->commit();
			$this->success('修改成功!');
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

	//产品套餐删除
	public function delete($product){
		$model  = M($product->name.'套餐');
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
	
}
?>