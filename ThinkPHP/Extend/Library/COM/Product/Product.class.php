<?php

class Product extends Model
{
    public $marketPrice; //市场价
    
    public $price; //商城价
    
    public $supplierPrice; //供货价
    
    public $number; //库存数量
    
    public $weight; //重量

    public $score; //积分价格
    
    public $rewardScore; //购物返积分
    /**
     *
     * @param int $productID
     * @param int $templateID
     * @param string $options_tag '0-0' '1-0'
     */
    public function getProductInfo($productID, $templateID, $options_tag)
    {
        $templateAttributeModel = M('product_template_attribute');
        $templateAttributes = $templateAttributeModel->where(array('template_id'=>$templateID))->select();
        
        //获取产品模板下的属性值信息
        $templateAttributeInfo = array();
        foreach($templateAttributes as $key=>$attr)
        {
            //将可选项分割成数组
            $optionContentArr = explode("\r\n", $attr['option_content']);
            
            $templateAttributeInfo[$key] = $attr;
            $templateAttributeInfo[$key]['option_content_arr'] = $optionContentArr;
        }
        //print_r($templateAttributeInfo);
        
        //属性模板有几个种类 应该与 产品选项以 '-' 分割后数组的个数是一致的
        $optionsArr = explode('-', $options_tag);
        $productInfo = array();
        foreach ($optionsArr as $key=>$option)
        {
            $productInfo['attrValue'][$templateAttributeInfo[$key]['name']] = $templateAttributeInfo[$key]['option_content_arr'][$option];
        }
        //print_r($productInfo);die;
        
        //获取产品的基本信息（库存数啦，销售价啦）
        $productAttrModel = M('product_attribute');
        //这条sql暂时没有问题因为没有字段发生歧义
        $productBaseInfo = $productAttrModel->join('shop_product p ON p.id = product_id')->
                                            where(array('product_id'=>$productID,'template_id'=>$templateID,'options_tag'=>$options_tag))->
                                            find();
        
        $productInfo['baseInfo'] = $productBaseInfo;

        return $productInfo;
        
        //找出产品的模板属性值,魔法方法赋给产品(这个方法挺好的，回头试试)
    }

}
?>
