<?php

/**
 *
 * 仓库信息管理类 
 */
import('COM.ProductManage.ProductSeries');
import('COM.ProductManage.Product');

class Warehouse extends Model
{
    public $id;
    
    public $name;
    
    public $code;
    
    public $createTime;
    
    public $isFreeze;
    
    private $warehouseModel;
    
    public function __construct($name = '') {
        parent::__construct($name);
        $this->warehouseModel = D('Warehouse');
    }
    
    
    
    /**
     * 建仓
     * $warehouse= array('数据库字段' =>'值')
     */
    public function createWarehouse($warehouse)
    {
        if(!$this->warehouseModel->create())
        {
            return $this->getError();
        }
        if($this->warehouseModel->add($warehouse))
        {
            return true;
        }
        return false;
    }
    
    /**
     * 修改仓
     * int $warehouseID
     * $data = array('字段' =>'值')
     * 
     */
    public function editWarehouse($warehouseID, $data)
    {
        if($this->warehouseModel->where(array('id'=>$warehouseID))->save($data))
        {
            return true;
        }
        
        return false;
    }
    
    //删除仓库
    public function deleteWarehouse()
    {
        if($this->warehouseModel->delete($this->id))
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * 冻结仓
     * int $warehouseID
     */
    public function changeStatus($status)
    {
        if($this->warehouseModel->where(array('id'=> $this->id))->setField('status', $status))
        {
            return true;
        }
        
        return false;
    }
    
    //仓库列表
    public function warehouseList($whereArr = array())
    {
        if(empty($whereArr))
            return $this->warehouseModel->select();
        
        return $this->warehouseModel->where($whereArr)->select();
    }

        /**
     * 检查仓库是否存在
     * int $warehouseID
     * 返回true or false
     */
    public function checkWarehouseIsExist($warehouseID)
    {
        $warehouseModel = M("warehouse");
        $ret = $warehouseModel->where("id={$warehouseID}")->find();

        if(is_null($ret) || $ret === false)
        {
            return false;
        }
        
        return true;
    }


    /**
     * 通过仓库ID获得仓库对象
     * int $warehouseID
     * 返回仓库对象
     */
    public static function getWarehouseByID($warehouseID)
    {
        $model = M('warehouse');
        $warehouse = $model->where("id={$warehouseID}")->find();
        
        $warehouseObj = new Warehouse();
        $warehouseObj->id = $warehouseID;
        $warehouseObj->code = $warehouse['code'];
        $warehouseObj->name = $warehouse['warehouse_name'];
        $warehouseObj->createTime = $warehouse['create_time'];
        $warehouseObj->isFreeze = $warehouse['is_freeze'];
        $warehouseObj->warehouseModel = $model;
        
        return  $warehouseObj;
    }

    
    
//    /**
//     *  获得所有产品在该仓库的库存,不存在于该库的产品的库存用零补充
//     *  int $warehouseID
//     */
//    public function getAllProductStockByWarehouseID($warehouseID, $cateID)
//    {
//        //存在于该库的商品库存信息
//        $productsInfo = $this->getProductStockSum(intval($warehouseID), 0)->getProductInfo();
//
//        $thisWarehouseStock = array();
//        foreach($productsInfo as $info)
//        {
//            $thisWarehouseStock[$info['product_id']] = $info['num'];
//        }
//
//        $productModel = M('product');
//
//        $products = $productModel->where(array('category_id'=>$cateID))->select();
//        
//        $allProductStockInOneWarehouse = array();
//        //遍历所有产品，整理每个产品的库存数量,不存在于该库的产品的库存用0填充
//        foreach($products as $product_id=>$pro)
//        {
//            if(array_key_exists($pro['id'], $thisWarehouseStock))
//            {
//                $allProductStockInOneWarehouse[$product_id] = $pro;
//                $allProductStockInOneWarehouse[$product_id]['enable_num'] = $thisWarehouseStock[$pro['id']];
//            } else {
//                $allProductStockInOneWarehouse[$product_id] = $pro;
//                $allProductStockInOneWarehouse[$product_id]['enable_num'] = 0;
//            }
//        }
//
//        return $allProductStockInOneWarehouse;
//    }
    
    
    public function getProductStockSum($warehouseID, $productID = 0,$template_id=0,$options_tag='')
    {
        $model = M("product_stock_map");
        
        //获得某个仓库某个产品的库存值
        if(!empty($warehouseID)&&!empty($productID) && !empty($options_tag))
        {
            $productStock = $model->field("product_id,template_id,options_tag,product_name,enable_num num")
                                    ->where(array('warehouse_id'=>$warehouseID,'product_id'=>product_id,'options_tag'=>$options_tag))
                                    ->find();
            $productSeries = new ProductSeries();
            $productSeries->addProductInfo($productStock);
            return $productSeries;
        }
        
        //获得某个仓库下所有产品的库存值
        if(!empty($warehouseID) && empty($productID) && empty($template_id) && empty($options_tag))
        {
            $productStock = $model->field("product_id,template_id,options_tag,enable_num num")
                                    ->where(array('warehouse_id'=>$warehouseID))
                                    ->select();
            
            $productSeries = new ProductSeries();
            foreach ($productStock as $info)
            {
                $productSeries->addProductInfo($info);
            }
            return $productSeries;
        }
    }

    
    /**
     * 产品进仓
     */
    public function productInWarehouse(ProductSeries $productSeries)
    {
        $mapModel = M('product_stock_map');
        
        //返回一个关于库存信息的二维数组array(array('product_id'=>1, 'num' =>11,'template_id'=>11,'options_tag'=>'0-145'));
        $productsInfo = $this->getProductStockSum(intval($this->id))->getProductInfo();
        
        $productIDNumMap = array();
        foreach($productsInfo as $info)
        {
            $productIDNumMap[$info['product_id']][$info['options_tag']] = $info['num'];
        }

        
        //操作库存：已存在于该库的产品执行更新数量操作，不存在的插入
        foreach($productSeries->getProductInfo() as $info)
        {
            //商城中需要product_id及options_tag两个字段来确定一个产品
            if(array_key_exists($info['product_id'], $productIDNumMap))
            {
                if(array_key_exists($info['options_tag'],$productIDNumMap[$info['product_id']]))
                {
                    $newNum = $info['num'] + $productIDNumMap[$info['product_id']][$info['options_tag']];
                
                    //商品存在于库中，更新库存数
                    $ret = $mapModel->where("options_tag='{$info['options_tag']}' AND product_id={$info['product_id']} AND warehouse_id={$this->id}")
                                    ->setField(array('enable_num'=>$newNum,'update_time'=>time()));

                    //记录库存变更日志
                    if($ret !== false)
                        $this->insertStockLog($info,'I',$productSeries);
                }
            } else {
                $info['warehouse_id'] = $this->id;
                $info['enable_num'] = $info['num'];
                $info['update_time'] = time();
                
                //插入库存
                $ret = $mapModel->add($info);
                
                //记录库存变更日志
                    if($ret !== false)
                        $this->insertStockLog($info,'I', $productSeries);
            }
        }
        return true;
    }
    
    
    
    //产品出仓
    public function productOutWarehouse(ProductSeries $productSeries)
    {
        $productInfo = $this->getProductStockSum(intval($this->id))->getProductInfo();
        
        $productIDNumMap = array();
        foreach($productInfo as $info)
        {
            $productIDNumMap[$info['product_id']][$info['options_tag']] = $info['num'];
        }
        
        //检查出库数量是否大于可用数量
        foreach($productSeries->getProductInfo() as $data)
        {
            if($data['num'] > $productIDNumMap[$data['product_id']][$data['options_tag']])
            {
                return 1;
            }
        }

        $mapModel = M('product_stock_map');

        //只有有库存的商品才能出库，所以只需执行更新操作
        foreach($productSeries->getProductInfo() as $data)
        {
            //更新产品数量
            $newNum = $productIDNumMap[$data['product_id']][$data['options_tag']] - $data['num'];
            $ret = $mapModel->where("warehouse_id={$this->id} AND product_id={$data['product_id']} AND options_tag={$data['options_tag']}")
                            ->setField(array('enable_num'=>$newNum,'change_time'=>time()));

            //插入库存变更日志
            if($ret !== false){
                $this->insertStockLog($data, 'O',$productSeries);
            }
        }
        
        return 2;
    }
    
    public function insertStockLog($info, $type, ProductSeries $productSeries)
    {
        $stockLogModel = M('product_stock_log');
                
        //整理用于插入库存变更日志的数据
        $log['warehouse_id'] = $this->id;
        $log['product_id'] = $info['product_id'];
        $log['template_id'] = isset($info['template_id']) ? intval($info['template_id']) : 0;
        $log['options_tag'] = $info['options_tag'];
        $log['product_info'] = $info['product_name'].'  '.$info['product_desc'] ;
        $log['change_num'] = $info['num'];
        $log['type'] = $type;
        $log['operater'] = empty($productSeries->_user) ? '' : $productSeries->_user;
        $log['remark'] = empty($productSeries->_remark) ? '' : $productSeries->_remark;
        $log['create_time'] = time();
           
        $stockLogModel->add($log);    
    }
}

?>
