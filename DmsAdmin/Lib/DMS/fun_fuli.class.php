<?php
	class fun_fuli extends stru
	{
		public function event_calover($tle,$caltime,$type)
		{
			$user=X('user');
			$cons=$this->getcon('con',array('name'=>'','where'=>''));
			foreach($cons as $con)
			{ 
				$where=delsign($con['where']);
				$users= M('会员','dms_')->where($where)->select();
				if($users)
				{
					foreach($users as $userinfo)
					{
						if(!$this->existfuli($userinfo['编号'],$con['name']))
						{
						   $data=array();
						   $data['name']=$con['name'];
						   $data['编号']=$userinfo['编号'];
						   $data['获得时间']=$caltime;
						   $data['state']=0;
						   $this->adduser($data);
						}
					}
				}
			}
            
		}

		public function adduser($data)
		{

			M($this->name,'dms_')->add($data);
		}
		public function existfuli($userid,$name)
		{
              $where=array();
			  $where['name']=$name;
			  $where['编号']=$userid;
			  $rs=M($this->name,'dms_')->where($where)->find();
			  if($rs){
			     return true;
			  }else{
				  return false;
			  }
		}
		public function event_sysclear()
		{
            $model=M();
			$model->execute('truncate table `dms_'.$this->name.'`');
		}
		public function event_modifyId($oldbh,$newbh)
		{ 
			M()->execute("update dms_" . $this->name . " set 编号='{$newbh}' where 编号='{$oldbh}'");
		}
	}
?>