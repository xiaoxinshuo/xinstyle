<?php

namespace Admin\Model;

Class GoodsModel{


    //获取商品数量
    public function getGoodsCount(){

        $sql = "SELECT COUNT(g.id) AS num FROM ws_goods g WHERE g.`status` = 1";
        return M()->query($sql);
    }

    //获取商品列表
    public function getGoodsList($page){
        $start 	= ($page-1)*C('AGENT_PAGE');
        $end 	= C('AGENT_PAGE');
            $sql = "SELECT DISTINCT(g.good_id),g.good_name,g.good_format,g.price,i.image_url 
                FROM ws_goods g 
                LEFT JOIN ws_goods_image i ON i.good_id = g.good_id AND i.`status`
                WHERE g.`status` = 1 GROUP BY g.good_id LIMIT $start,$end";
        return M()->query($sql);
    }

}