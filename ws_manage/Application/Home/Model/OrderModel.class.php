<?php

namespace Home\Model;

Class OrderModel{
    /*
     *与订单相关的查询
     * */
    //获取个人销量
    public function getSelfOrderSales($user_id,$start_date = ''){
        $con = "";
        if ($start_date){
            $con = " and payment_time >= '{$start_date}'";
        }
        $sql = "SELECT SUM(o.money_paid) AS money FROM ws_goods_order o 
	      WHERE o.user_id = '{$user_id}' AND o.pay_status > 1 AND o.`status` = 1";

        $res = M()->query($sql);
        if (!$res['0']['money']){
            $res['0']['money'] = 0.00;
        }
        return $res['0']['money'];
    }

    //获取个人订单
    public function getUserOrderList($user_id,$status,$p){
        $start 	= ($p-1)*C('ORDER_PAGE');
        $end 	= C('ORDER_PAGE');
        if (!$status){
            $status = 0;
        }
        $con = "";
        if ($status > 0){
            $con = " and o.pay_status = $status ";
        }
        $sql = "SELECT o.order_id,o.user_id,o.phone,o.address,
            o.money_paid,o.receiver_name,o.payment_time,o.create_time,o.pay_status
            FROM ws_goods_order o           
            WHERE o.`status` <> 0 AND o.user_id = '{$user_id}'".$con." LIMIT $start,$end";

        $res = M()->query($sql);

        return $res;
    }

    //查询订单下的商品
    public function getOrderItem($order_id){
        $sql = "SELECT i.good_id,i.good_format,i.number,i.create_time,g.good_name,i.price,e.image_url 
            FROM ws_goods_order_item i
						JOIN ws_goods g ON i.good_id = g.good_id 
						LEFT JOIN ws_goods_image e ON e.good_id = g.good_id AND e.`status` = 1
            WHERE i.`status` = 1 AND i.order_id = '{$order_id}' GROUP BY g.good_id;";
        $res = M()->query($sql);
        foreach ($res as $k=>$v){
            $res[$k]['itemPrice'] = $v['number'] * $v['price'];
            $res[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }
        return $res;
    }

    //获取订单中的货物信息
    public function getGoodsInfoByOrder($order_id){
        $sql = "SELECT o.good_id,o.number,g.good_name,g.price,i.image_url 
            FROM ws_goods_order_item o
            JOIN ws_goods g ON g.good_id = o.good_id 
            LEFT JOIN ws_goods_image i ON g.good_id = i.good_id AND i.`status` = 1
            WHERE o.order_id = '{$order_id}' AND o.`status` <> 0 GROUP BY o.id";

        $res = M()->query($sql);

        return $res;
    }

    //求出个人奖金
    public function getSelfAward($money){
        $sql = "SELECT * FROM ws_commition_rule r 
	      WHERE r.`start` < '$money' AND r.`end` > '$money' 
	      AND r.`status` = 1 ORDER BY r.id DESC";

        $res = M()->query($sql);
        $award = $money * $res['0']['rate'] / 100;

        return $award;
    }

}