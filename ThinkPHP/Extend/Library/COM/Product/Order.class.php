<?php

class Order extends Model
{
    public $id;
    
    public $sumMoney;
    
    public $saler;

    public $receiver;

    public $receiver_phone;
    
    public $status;
    
    public $create_time;


    public $statusMean = array('1'=>'待审核',
                                '2'=>'审核通过',
                                '3'=>'审核不通过',
                                '4'=>'退回',);


    public $orderProduct;
    
    public $orderModel;
    
    public $orderProductModel;
    
    public function __construct($name = '') {
        parent::__construct($name);
        $this->orderModel =  M('order');
        $this->orderProductModel = M('order_product');
    }
    
    public function creatOrder(ProductSeries $productSeries)
    {
        foreach($productSeries->getProductInfo() as $row)
        {
            $ret = $this->orderModel->add($row);
            if($ret > 0)
            {
                $orderProduct['order_id'] = $ret;
                $orderProduct['product_id'] = $row['product_id'];
                $orderProduct['sale_num'] = $row['num'];
                $orderProduct['warehouse_id'] = $row['warehouse_id'];
                $orderProduct['price'] = round($row['sum_money']/$row['num'], 2);
                $this->orderProductModel->add($orderProduct);
            } else {
                echo    "产品ID：{$row['prodcuct_id']}为的订单生成失败";
                continue;
            }
        }
        
        return true;
    }
    
    static public function getOrderByOrderID($orderID)
    {
        $orderModel = M('order');
        $orderInfo = $orderModel->field('`order`.*,product_name,op.product_id,op.warehouse_id,op.sale_num')
                                ->join('order_product op ON order.id=op.order_id')
                                ->join('product p ON p.id = op.product_id')
                                ->where(array('order.id'=>$orderID))
                                ->select();

        $orderObj = new Order();
        $orderProducts = array();
        foreach($orderInfo as $info)
        {
            $orderObj->id = $info['id'];
            $orderObj->sumMoney = $info['sum_money'];
            $orderObj->saler = $info['saler'];
            $orderObj->receiver = $info['receiver'];
            $orderObj->receiver_phone = $info['receiver_phone'];
            $orderObj->status = $info['status'];
            $orderObj->create_time = $info['create_time'];
            $orderProducts[$info['product_id']]['product_name'] = $info['product_name'];
            $orderProducts[$info['product_id']]['sale_num'] = $info['sale_num'];
            $orderProducts[$info['product_id']]['warehouse_id'] = $info['warehouse_id'];
        }
        $orderObj->orderProduct =$orderProducts;       

        return $orderObj;       
    }
    
}

?>
