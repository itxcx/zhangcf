<?php

/**
 * 用于产品序列化的类
 * 说明：该类中所有涉及到数量的问题，统一用字段：num，所以在外部调用这个类传值时，要将数量这个字段重命名为：num
 */
class ProductSeries
{
    public $_productInfo=array();
    
    public $_remark;
    
    public $_user;
    
    public $_error;
    //整理传入的数据(貌似没用到过)
    //格式：$_productInfo = array(array('product_id'=>1, 'num' =>11,'template_id'=>11,'options_tag'=>'0-145'));
    public function combineData($productInfo)
    {
        foreach($productInfo as $data)
        {
            $tmp = array();

            $tmp['product_id'] = $data['product_id'];
            $tmp['num'] = (int)$data['num'];
            
            if(array_key_exists($data['product_id'], $this->_productInfo))
            {
                $this->_productInfo[$data['product_id']]['num'] += intval($data['num']);
            } else {
                $this->_productInfo[$data['product_id']] = $tmp;
            }
        }
    }
    

    /**
     * 
     * $productInfo = array('product_id'=>1, 'num' =>11,'template_id'=>10,'option_tag'=>'0-1')
     */
    public function addProductInfo($productInfo)
    {
        $this->_productInfo[] = $productInfo;
    }

    public function getProductInfo()
    {        
        return $this->_productInfo;
    }
    
    //获得某属性的汇总信息(例如pv，价格总和等...)
    public function getAttrSumInfo($attr)
    {
        $attrSumInfo = 0;
        foreach($this->_productInfo as $info)
        {
            $attrSumInfo += isset($info[$attr]) ? $info[$attr] : 0;
        }
        
        return $attrSumInfo;
    }
    
}

//ProductSeries::getFormPost()->filter(array('name'=>'xxx'))

?>
