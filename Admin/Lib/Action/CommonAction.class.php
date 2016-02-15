<?php
// 后台公共Action
class CommonAction extends Action
{
    public $sortBy  = '';
    public $asc     = false;
	private $pageArgs	= array();  //分页参数附加
	/*
	* 权限验证方法,该方法要保证在每个Action里面执行,可以将该方法放到公共头文件里面
	*/
	function _initialize() 
    {
    	diffTime();
		//设置字符编码
		Header("Content-Type:text/html;charset=utf-8");
		//对cli模式命令进行过滤
		//cli不需要进行权限验证
		if(adminshow('cliSwitch') && MODULE_NAME == 'Backup' && ACTION_NAME == 'getstateajax')
		{
			return true;
		}
        if(IS_CLI)
		{
			return true;
		}
		
		//验证管理员密码修改
		if (!isset($_SESSION [C( 'RBAC_ADMIN_AUTH_KEY' )]))
		{
			//对自动结算进行过滤
			if(I("get.calpass/s")!="" && I("get.calpass/s") == F('calpass') && MODULE_NAME == 'Cal' && ACTION_NAME == 'settlementExecute'){
				return true;
			}
			if($this->isAjax() ){
				//弹出登录页面
				$this->ajaxReturn(0,'','301');
			}else{
				//跳转到认证网关
				redirect ( "?s=" . C ( 'RBAC_ADMIN_AUTH_GATEWAY' ) );
			}
		}
		
		// 用户权限检查
		if (C ( 'RBAC_ADMIN_AUTH_ON' ) && !in_array(MODULE_NAME,explode(',',C('RBAC_NOT_AUTH_MODULE'))) && MODULE_NAME!='UpdateUser') 
		{
			import ( 'ORG.Util.RBAC' );
			if (! RBAC::AccessDecision()) //检查权限
			{
				//如果是ajax请求
				if ( $this->isAjax() )
				{
					// 提示错误信息
					$this->error('无权限操作');
				}
				//不是ajax
				else
				{
					if(!(MODULE_NAME =='Index' && ACTION_NAME == 'index')){
						// 提示错误信息
						$this->assign('waitSecond',20);
						$this->assign('closeWin',true);
						$this->error('无权限操作, <a href="?s=/Public/logout" style="color:red">退出登录</a>!');
					}
				}
			}
			//防XSS跨站攻击登入
			if(!in_array(get_client_ip(),$_SESSION['loginIp']))
			{
				//跳转到登录页面  要求登录
				$this->ajaxReturn(0,'您的ip有变化存在安全隐患,请重新登录','301');
				die();
			}
		}
		/*判断登录管理账号的权限*/
		$admin_status=M("admin")->where(array("id"=>$_SESSION[C('RBAC_ADMIN_AUTH_KEY')]))->getField('admin_status');
		if($admin_status==1) 
		{
        	$_SESSION[C('RBAC_SUPER_ADMIN_KEY')] = true;
        }
        else
        {
        	unset($_SESSION[C('RBAC_SUPER_ADMIN_KEY')]);
        }
		$this->userobj = X('user');
		$relodargs=strstr($_SERVER['REQUEST_URI'],'&');
        $this->assign("relodargs",$relodargs);
        $this->assign("time",time());
	}
    /*
    * 模块列表方法
    */
	public function index() 
    {
		$this->assign('_order',I("request._order/s"));
		$this->assign('_sort',I("request._sort/s"));
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$name   = $this->getActionName();
        $sortBy = $this->sortBy;
        $asc    = $this->asc;
		$model  = D($name);
		$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
          if(!$admins['admin_status']){
    	     $map['id'] =  $_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ];  
    	}
		if (! empty ( $model )) {
			$this->_list ( $model, $map, $sortBy, $asc);
		}
		$this->display ();
		return;
	}
	/**
     +----------------------------------------------------------
	 * 取得操作成功后要返回的URL地址
	 * 默认返回当前模块的默认操作
	 * 可以在action控制器中重载
     +----------------------------------------------------------
	 * @access public
     +----------------------------------------------------------
	 * @return string
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	function getReturnUrl() 
    {
		return __URL__ . '/' . C ( 'DEFAULT_ACTION' );
	}

	/**
     +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
     +----------------------------------------------------------
	 * @access protected
     +----------------------------------------------------------
	 * @param string $name 数据对象名称
     +----------------------------------------------------------
	 * @return HashMap
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	protected function _search($name = '') 
    {
		//生成查询条件
		if (empty ( $name )) {
			$name = $this->getActionName();
		}
		$model = D ( $name );
		$map = array ();
		foreach ( $model->getDbFields () as $key => $val ) {
			if (I("request.".$val."/s") != '') {
				$map [$val] = I("request.".$val."/s");
			}
		}
		return $map;
	}
	/**
     +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
     +----------------------------------------------------------
	 * @access protected
     +----------------------------------------------------------
	 * @param Model $model 数据对象
	 * @param HashMap $map 过滤条件
	 * @param string $sortBy 排序
	 * @param boolean $asc 是否正序
     +----------------------------------------------------------
	 * @return void
     +----------------------------------------------------------
	 * @throws ThinkExecption
     +----------------------------------------------------------
	 */
	protected function _list($model, $map, $sortBy = '', $asc = false) 
    {
		//排序字段 默认为主键名
		if (I("request._order/s")!="") {
			$order = I("request._order/s");
		} else if($sortBy!=""){
			$order = $sortBy;
		}else {
			$order = $model->getPk ();
		}
		//排序方式默认按照倒序排列
		//接受 sost参数 0 表示倒序 非0都 表示正序
		if (I("request._sort/s")!="") {
			$sort = I("request._sort/s")!=0 ? 'asc' : 'desc';
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}
		//取得满足条件的记录数
		$count = $model->where ( $map )->count ( $model->getPk () );
		if ($count > 0) {
			import ( "ORG.Util.Page" );
			//创建分页对象
			if (I("request.listRows/s")!="") {
				$listRows = I("request.listRows/s");
			} else {
				$listRows = '';
			}
			$p = new Page ( $count, $listRows );
			//分页查询数据
			$voList = $model->where($map)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select();
            $url = __URL__.'/index';
            if( count($map) > 0 )
            {
                //分页跳转的时候保证查询条件
                foreach ( $map as $key => $val ) 
                {
                    if (! is_array ( $val )) {
                        $url .= "/{$key}/" . urlencode ( $val );
                    }
                    else if( is_array ( $val ) )
                    {
                        $search  = array('%', 'like ', '\'');
                        $replace = array('', '', '');
                        $_val = str_replace($search,$replace,$val[1]);
                        $url .= "/{$key}/" . urlencode ( $_val );
                    }
                }
            }
			//额外附加分页参数
			if( count($this->pageArgs) > 0 )
			{
				foreach ( $this->pageArgs as $key => $val ) 
                {
					$url .= "/{$key}/" . urlencode ( $val );
				}
			}
			//分页显示
			$page = $p->show ($url);
			//列表排序显示
			$sortImg = $sort; //排序图标
			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
			$sort = $sort == 'desc' ? 1 : 0; //排序方式
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'sort', $sort );
			$this->assign ( 'order', $order );
			$this->assign ( 'sortImg', $sortImg );
			$this->assign ( 'sortType', $sortAlt );
			$this->assign ( 'map', $map );
			$this->assign ( "page", $page );
			$this->assign ( "currentPage", $p->nowPage );	//当前页数
			$this->assign ( "numPerPage", $p->listRows );  //每页显示几条
			
			$this->assign ( "totalCount", $count );  //总记录数
		}
		cookie( '_currentUrl_', __SELF__ );
		return;
	}


	/**
    +----------------------------------------------------------
	* 默认保存添加之后返回添加页面
    +----------------------------------------------------------
	*/
    public function _before_insert() {
        cookie( '_currentUrl_', __URL__ . '/add' );
    }

	/**
    +----------------------------------------------------------
	* 默认保存添加操作
    +----------------------------------------------------------
	*/
	public function insert() 
    {
		$name   = $this->getActionName();
		$model  = D($name);
		if (false === $model->create ()) {
			$this->error( $model->getError () );
		}
		//添加之前检查有无定义过滤器方法
		if (method_exists ( $this, '_filter_insert_before' )) {
			$this->_filter_insert_before ( $model );
		}
		//保存当前数据对象
		
		$result		= $model->add ();
		if ($result!==false)  //保存成功
        {
			//添加之后检查有无定义过滤器方法
			if (method_exists ( $this, '_filter_insert_after' )) {
				$this->_filter_insert_after ( $model,$result );
			}
			$this->success ('新增成功!');
		} 
        else 
        {
			Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
			//失败提示
			$this->error ('新增失败!');
		}
	}

	/**
    +----------------------------------------------------------
	* 默认添加操作
    +----------------------------------------------------------
	*/
	public function add() 
    {
    	$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
		//判断此会员是否是超管
		if($admins['admin_status']!=1){
		  $this->error('无权限操作');
		}
		if (method_exists ( $this, '_filter_add_before' )) {
			$this->_filter_add_before ();
		}
		//判断此会员是否是超管
		$this->assign('admins',$admins);
		$this->display();
	}


	/**
    +----------------------------------------------------------
	* 默认修改操作
    +----------------------------------------------------------
	*/
	public function edit() 
    {
		if (method_exists ( $this, '_filter_edit_before' )) {
			$this->_filter_edit_before();
		}
		$name   = $this->getActionName();
		$model  = M( $name );
		$id     = I("request.".($model->getPk ())."/d");
		$vo     = $model->getById ( $id );
		$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
		$this->assign('admins',$admins);
		$this->assign ( 'vo', $vo );
		$this->display ();
	}

	/**
    +----------------------------------------------------------
	* 默认查看操作
    +----------------------------------------------------------
	*/
	public function view() 
    {
		$name   = $this->getActionName();
		$model  = M( $name );
		$id     = I("request.".($model->getPk ())."/d");
		$vo     = $model->getById ( $id );
		$this->assign ( 'vo', $vo );
		$this->display ();
	}

    /*
    * 默认保存修改之后返回修改页面
    */
    public function _before_update() {
    	$name			= $this->getActionName();
    	$model			= D( $name );
        $pk				= $model->getPk ();
    	if(I("request.".$pk."/d")>0)
        	cookie('_currentUrl_',__URL__.'/edit/id/'.I("request.".$pk."/d"));
    }


	/**
    +----------------------------------------------------------
	* 默认保存修改操作
    +----------------------------------------------------------
	*/
	public function update() 
    {
		$name			= $this->getActionName();
		$model			= D( $name );
        $pk				= $model->getPk ();
		$id				= I("request.".$pk."/d");
		// 查出修改之前的数据
		$oldData		= $model->find($id);
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		//修改之前检查有无定义过滤器方法
		if (method_exists ( $this, '_filter_update_before' )) {
			$this->_filter_update_before ( $model );
		}
		M()->startTrans();
		// 更新数据
		$result			= $model->save ();
		if (false !== $result) 
        {
        	M()->commit();
			//修改之后检查有无定义过滤器方法
			if (method_exists ( $this, '_filter_update_after' )) {
				$this->_filter_update_after ( $model,$id );
			}
			//查出修改之后的数据
			$newData	= $model->find($id);
			//成功提示
			$this->success ('编辑成功!');
		} 
        else 
        {
			Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
			//错误提示
			$this->error ('编辑失败!');
		}
	}

	/**
    +----------------------------------------------------------
	* 默认删除操作
    +----------------------------------------------------------
	*/
	public function delete() 
    {
    	$admins = M('admin')->where(array('id'=>$_SESSION[ C('RBAC_ADMIN_AUTH_KEY') ]))->find();
		//判断此会员是否是超管
		if($admins['admin_status']!=1){
		  $this->error('无权限操作');
		}
		//删除指定记录
		$name   = $this->getActionName();
		$model  = D($name);
		if (! empty ( $model )) 
        {
			$pk = $model->getPk();
			$succNum = 0;
			$errNum = 0;
			foreach(explode(',',I("get.".$pk."/s")) as $id){
				if($id == '') continue;
				//删除之前检查有无定义过滤器方法
				if (method_exists ( $this, '_filter_delete_before' )) {
					$this->_filter_delete_before ( $model , $id);
				}
				M()->startTrans();
				$condition  = array ($pk => array ('in', explode ( ',', $id ) ) );
				$list       = $model->where ( $condition )->delete();
				if ( $list !== false ) 
                {
                	M()->commit();
					//成功删除之后检查有无定义过滤器方法
					if (method_exists ( $this, '_filter_delete_after' )) 
					{
						$this->_filter_delete_after ( $model , $id);
					}
					$succNum++;
				} 
                else 
                {
					Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
					$errNum++;
				}
			}
			if($errNum !=0){
				$this->error("删除成功：".$succNum .'条记录；删除失败：'.$errNum .'条记录；');
			}else{
				$this->success("删除成功：".$succNum .'条记录；');
			}
		}
	}
	/**
    +----------------------------------------------------------
	* 保存后台用户操作日志
    +----------------------------------------------------------
	*/
    public function saveAdminLog($oldData=null,$newData=null,$content=null,$memo=null)
    {
		$Model  = D('Admin://Log');
        $Model->saveAdminLog($oldData,$newData,$content,$memo);
    }
	/**
    +----------------------------------------------------------
	* 默认审核不通过操作
    +----------------------------------------------------------
	*/
	/*public function forbid() 
    {
		$name   = $this->getActionName();
		$model  = D($name);
		$pk     = $model->getPk();
		$id     = $_REQUEST[$pk];
		$condition = array ($pk => array ('in', $id ) );
		$list   = $model->forbid( $condition );
		if ( $list!==false ) 
        {
			$this->success( "ID: $id 审核不通过状态成功" );
		} 
        else 
        {
			Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
			$this->error(  'ID: $id 审核不通过状态失败' );
		}
	}*/
	/**
    +----------------------------------------------------------
	* 默认审核通过操作
    +----------------------------------------------------------
	*/
	/*public function checkPass() 
    {
		$name   = $this->getActionName();
		$model  = D($name);
		$pk     = $model->getPk();
		$id     = $_REQUEST[$pk];
		$condition = array ($pk => array ('in', $id ) );
		if ( false !== $model->checkPass( $condition ) ) 
        {
			$this->success( "ID: $id 审核通过状态成功！" );
		} 
        else 
        {
			Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
			$this->error(  "ID: $id ".$model->getError() );
		}
	}*/
	/**
    +----------------------------------------------------------
	* 默认还原待审操作
    +----------------------------------------------------------
	*/
	/*public function recycle() 
    {
		$name   = $this->getActionName();
		$model  = D($name);
		$pk     = $model->getPk();
		$id     = I("request.".$pk."/s");
		$condition = array ($pk => array ('in', $id ) );
		if ( false !== $model->recycle ( $condition ) ) 
        {
			$this->success( "ID: $id 还原待审状态成功！" );
		} 
        else 
        {
			Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
			$this->error(  "ID: $id 还原待审状态失败！" );
		}
	}*/
	/**
    +----------------------------------------------------------
	* 默认恢复操作
    +----------------------------------------------------------
	*/
	/*public function resume() 
    {
		//恢复指定记录
		$name   = $this->getActionName();
		$model  = D($name);
		$pk     = $model->getPk();
		$id     = I("request.".$pk."/s");
		$condition = array($pk => array ('in', $id ) );
		if ( false !== $model->resume( $condition ) ) 
        {
			$this->success( '状态恢复成功！' );
		} 
        else 
        {
			Log::write('dubugSql:'.$model->getLastSql(), Log::SQL);
			$this->error( '状态恢复失败！' );
		}
	}*/
    /**
     +----------------------------------------------------------
     * 默认排序操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    /*public function sort()
    {
        $name = $this->getActionName();
		$node   = M($name);
        if(I('get.sortId/s')!="") {
            $map = array();
            $map['id']   = array('in',I('get.sortId/s'));
            $sortList   =   $node->where($map)->order('sort asc')->select();
        }
        $this->assign("sortList",$sortList);
        $this->display();
        return ;
    }*/
    /**
     +----------------------------------------------------------
     * 默认保存排序操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    /*public function saveSort() 
    {
        $seqNoList = I("post.seqNoList/s");
        if (! empty ( $seqNoList )) 
        {
            //更新数据对象
            $name = $this->getActionName();
            $model = D ($name);
            $col = explode ( ',', $seqNoList );
            foreach ( $col as $val ) 
            {
                $val            = explode ( ':', $val );
                $data['id']     = $val [0];
                $data['sort']   = $val [1];
                $result = $model->save($data);
            }
            if ($result!==false) {
                //采用普通方式跳转刷新页面
                $this->ajaxReturn(0, '更新成功' ,1);
            } else {
                $this->ajaxReturn(0, $model->getError () ,0);
            }
        }
        $this->ajaxReturn(0, '更新失败' ,0);
    }*/
}
?>