<?php

namespace Home\Model;

Class AddressModel{
    /*
     *与地址相关的查询
     * */
    //获取个人地址列表
    public function getAddressList($user_id){

        $sql = "SELECT a.*,c1.area_name AS province_name,c2.area_name AS city_name,c3.area_name AS county_name 
            FROM ws_user_address a 
            LEFT JOIN ws_city c1 ON c1.id = a.province_id
            LEFT JOIN ws_city c2 ON c2.id = a.city_id
            LEFT JOIN ws_city c3 ON c3.id = a.county_id
            WHERE a.`status` = 1 AND a.user_id = '{$user_id}' 
            ORDER BY a.is_default DESC,a.create_time DESC";

        $res = M()->query($sql);
        return $res;
    }


}