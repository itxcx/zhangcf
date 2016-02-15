<?php
//会员的模型
class duserModel extends model
{
	//设置时间的处理环节
	//对各类级别，业绩做回退生成临时性业绩表
    // 数据表名（不包含表前缀）
    protected $tableName = '会员';
    // 实际数据表名（包含表前缀）
    protected $trueTableName ='dms_会员';
    private static $time;
    //设置日期
	public function settime($time)
	{
		self::$time=$time;
		//对自动升级的级别。判断当日之后是否有升级记录，如果存在则报异常。
	}
	//各种LEFT JOIN 
	
	//对各种字段各种条件各种改。
	public function select($options = array())
	{
		$this->table('dms_会员 u');
		//重构表结构
		if(!isset($this->options['field']))
		{
			throw_exception("在使用duser模块时，必须要使用field选择需要的字段");
		}
		//重构field
		$newfield = '';
		foreach(explode(',',$this->options['field']) as $field)
		{
			$fieldset = $this->fieldset($field);
			if($fieldset)
			{
				$newfield.=','.$fieldset['select'];
				if(!isset($this->options['join']) || !in_array($fieldset['join'],$this->options['join']))
				{
					$this->join($fieldset['join']);
				}
			}
			else
			{
				$newfield.=',u.'.$field;
			}
		}
		$this->options['field']=ltrim($newfield,',');
		//重构where
		//$newwhere = '';
		//如果存在where
		if(isset($this->options['where']) && is_string($this->options['where']))
		{
			preg_match_all('/\[(.*)\]/Uis',$this->options['where'],$trform,PREG_SET_ORDER);
			if(!empty($trform))
			foreach($trform as $val)
			{
				$fieldset = $this->fieldset($val[1]);
				if($fieldset)
				{
					//$newfield.=','.$fieldset['select'];
					$this->options['where'] = str_replace($val[0],$fieldset['where'],$this->options['where']);
					if(!isset($this->options['join']) || !in_array($fieldset['join'],$this->options['join']))
					{
						$this->join($fieldset['join']);
					}
				}
				else
				{
					$this->options['where'] = str_replace($val[0],"u.".$val[1],$this->options['where']);
				}
			}
		}
		$this->options['field']=ltrim($newfield,',');
		
		//dump($this->options);
		return parent::select($options);
	}
	//定义可用的会员表字段
	function fieldset($field)
	{
		/*
		rowset作为二维数组存在
		内部数组结构为
		row=array(..)用于查询的字段
		selrow  实际查询出来的字段
		whererow实际查询出来的字段
		join    连表代码
		*/
		$rowset=array();
		foreach(X('levels') as $key=>$levels)
		{
			$rowset[]=array(
				'row'     =>array(
					$levels->name=>array(
										'select'=>"ifnull(level".$key.".olv,u.".$levels->name.") ".$levels->name,
										'where' =>"ifnull(level".$key.".olv,u.".$levels->name.")",
										),
				),
				/*  此连表无法考虑到在本结算日之后有升级记录
					但同时本日奖金产生了新的升级记录的情况
					但系统约定自动升级的级别不能够做手动升级处理
					所以如果回退奖金，那么可以自动升级的级别，
					后续必然不可能有手动升级记录导致自动升级后无法正常读出升级后的级别
				*/
				'join'    =>"(select * from (select userid,olv from dms_lvlog where lvname='".$levels->name."' and time >=".(self::$time + 86400)." order by time asc,id asc) lvtmp{$key}  group by userid) level{$key} on level{$key}.userid=u.id",
				);
		}
		//推荐人数
		foreach(X('net_rec') as $net)
		{
			$tjrow=
			$rowset[]=array(
				'row'     =>array(
					$net->name.'_推荐人数'=>array(
										'select'=>$net->name."_推荐人数-ifnull(netrec".$key.".num,0) ".$net->name."_推荐人数",
										'where' =>$net->name."_推荐人数-ifnull(netrec".$key.".num,0) ",
										),
				),
				'join'    =>"(select ".$net->name."_上级编号,count(*) num from dms_会员 where 审核日期 >=".(self::$time + 86400)." and ".$net->name."_被推荐数>0  group by ".$net->name."_上级编号) netrec{$key} on netrec{$key}.".$net->name."_上级编号=u.编号",
				);
		}
		foreach(X('net_rec') as $net)
		{
			$tjrow=
			$rowset[]=array(
				'row'     =>array(
					$net->name.'_推荐人数'=>array(
										'select'=>$net->name."_推荐人数-ifnull(netrec".$key.".num,0) ".$net->name."_推荐人数",
										'where' =>$net->name."_推荐人数-ifnull(netrec".$key.".num,0) ",
										),
				),
				'join'    =>"(select ".$net->name."_上级编号,count(*) num from dms_会员 where 审核日期 >=".(self::$time + 86400)." and ".$net->name."_被推荐数>0  group by ".$net->name."_上级编号) netrec{$key} on netrec{$key}.".$net->name."_上级编号=u.编号",
				);
		}
		//返回查到的数据
		foreach($rowset as $set)
		{
			if(isset($set['row'][$field]))
			{
				return array(
				'select'=>$set['row'][$field]['select'],
				'where' =>$set['row'][$field]['where'],
				'join'  =>$set['join']
				);
			}
		}
		return null;
	}
}
?>