<?php

namespace Admin\Model;

Class UserModel{

    //获取用户信息
    public function getUserInfo($user_no){

        $sql = "SELECT * FROM ws_user u 
          WHERE u.user_no = '{$user_no}' AND u.`status` = 1";
        return M()->query($sql);
    }
    //获取代理数量
    public function getUserCount($start_date=null){
        $con = '';
        if ($start_date) {
            $con = " AND u.check_time >= '{$start_date}'";
        }
        $sql = "SELECT COUNT(u.id) AS num FROM ws_user u WHERE u.utype = 11 AND u.`status` = 1 "."$con";
        return M()->query($sql);
    }

    //获取审核通过代理列表
    public function getPassAgentList($page,$user_id){
        $start 	= ($page-1)*C('AGENT_PAGE');
        $end 	= C('AGENT_PAGE');
        $con = "";
        if ($user_id){
            $con = " AND u.recommender_id = '{$user_id}' ";
        }

        $sql = "SELECT u.*,r.`name` AS r_name,r.user_no AS r_user_no FROM ws_user u 
            LEFT JOIN ws_user r ON u.recommender_id = r.user_id
            WHERE u.`status` = 1 AND u.utype = 11 AND u.is_check = 1 ".$con." ORDER BY u.check_time ASC 
            LIMIT $start, $end";
        return M()->query($sql);
    }

    //获取审核通过代理数量
    public function getPassAgentNum($user_id){
        $con = "";
        if ($user_id){
            $con = " AND u.recommender_id = '{$user_id}' ";
        }
        $sql = "SELECT COUNT(u.id) AS num FROM ws_user u 
	      WHERE u.`status` = 1 AND u.utype = 11 AND u.is_check = 1".$con;
        return M()->query($sql);
    }

    //获取审核未通过代理列表
    public function getFailedAgentList($page,$status = 1){
        $start 	= ($page-1)*C('AGENT_PAGE');
        $end 	= C('AGENT_PAGE');

        $sql = "SELECT u.*,r.`name` AS r_name,r.user_no AS r_user_no FROM ws_user u 
            LEFT JOIN ws_user r ON u.recommender_id = r.user_id
            WHERE u.`status` = {$status} AND u.utype = 11 AND u.is_check = 0
            LIMIT $start, $end";
        return M()->query($sql);
    }

    //获取审核未通过代理数量
    public function getFailedAgentNum($status = 1){
        $sql = "SELECT COUNT(u.id) AS num FROM ws_user u 
	      WHERE u.`status` = {$status} AND u.utype = 11 AND u.is_check = 0";
        return M()->query($sql);
    }

    //结算统计---查询用户列表
    public function getAgentList($page,$user_no){
        $start 	= ($page-1)*C('AGENT_PAGE');
        $end 	= C('AGENT_PAGE');
        $con = "";
        if ($user_no){
            $con = " AND u.user_no = '{$user_no}' ";
        }

        $sql = "SELECT u.* FROM ws_user u 
            WHERE u.`status` = 1 AND u.utype = 11 AND u.is_check = 1 ".$con." ORDER BY u.check_time ASC 
            LIMIT $start, $end";
        return M()->query($sql);
    }

    //获取审核通过代理数量
    public function getAgentNum($user_no){
        $con = "";
        if ($user_no){
            $con = " AND u.user_no = '{$user_no}' ";
        }
        $sql = "SELECT COUNT(u.id) AS num FROM ws_user u 
	      WHERE u.`status` = 1 AND u.utype = 11 AND u.is_check = 1".$con;
        return M()->query($sql);
    }

}