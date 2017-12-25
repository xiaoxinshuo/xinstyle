<?php

namespace Admin\Model;

Class OrderModel{
    /*
     *与订单相关的查询
     * */
    //获取本月或本周订单的金额总数
    public function getOrderMoney($start_date){
        $sql = "SELECT SUM(o.money_paid) AS money FROM ws_goods_order o 
	      WHERE o.payment_time >= '{$start_date}' AND o.pay_status = 2 AND o.`status` = 1";
        return M()->query($sql);
    }

    //查询列表
    public function getOrderList($page,$order_id,$pay_status){
        $start 	= ($page-1)*C('AGENT_PAGE');
        $end 	= C('AGENT_PAGE');
        $con = "";
        if ($order_id){
            $con = " and o.order_id = {$order_id} ";
        }

        if ($pay_status > 0){
            if ($con){
                $con .= " and o.pay_status = {$pay_status} ";
            }else{
                $con = " and o.pay_status = {$pay_status} ";
            }
        }
        $sql = "SELECT o.order_id,o.user_id,o.phone,o.address,
            o.money_paid,o.receiver_name,o.payment_time,o.create_time,o.pay_status,
            u.name,u.user_no
            FROM ws_goods_order o  
            JOIN ws_user u ON u.user_id = o.user_id         
            WHERE o.`status` = 1 ".$con." ORDER BY o.update_time ASC LIMIT $start,$end";
        return M()->query($sql);
    }

    //查询订单下的商品
    public function getOrderItem($order_id){
        $sql = "SELECT i.good_id,i.good_format,i.number,i.create_time,g.good_name,i.price
            FROM ws_goods_order_item i
			JOIN ws_goods g ON i.good_id = g.good_id 
            WHERE i.`status` = 1 AND i.order_id = '{$order_id}'";
        $res = M()->query($sql);
        foreach ($res as $k=>$v){
            $res[$k]['itemPrice'] = $v['number'] * $v['price'];
        }
        return $res;
    }

    //获取商品数量
    public function getOrderCount($order_id,$pay_status){
        $con = "";
        if ($order_id){
            $con = " and o.order_id = {$order_id} ";
        }

        if ($pay_status > 0){
            if ($con){
                $con .= " and o.pay_status = {$pay_status} ";
            }else{
                $con = " and o.pay_status = {$pay_status} ";
            }
        }
        $sql = "SELECT COUNT(o.id) AS num FROM ws_goods_order o  WHERE o.`status` = 1 ".$con;
        return M()->query($sql);
    }

    //获取个人销量
    public function getSelfOrderSales($user_id,$start,$end){
        $sql = "SELECT SUM(o.money_paid) AS money FROM ws_goods_order o 
	      WHERE o.user_id = '{$user_id}' AND o.payment_time > '{$start}' AND o.payment_time < '{$end}' AND o.pay_status > 1 AND o.`status` = 1";

        $res = M()->query($sql);
        if (!$res['0']['money']){
            $res['0']['money'] = 0.00;
        }
        return $res['0']['money'];
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