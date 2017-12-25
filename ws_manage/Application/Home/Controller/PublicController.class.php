<?php
namespace Home\Controller;
use Think\Controller;


class PublicController extends Controller{

	function _initialize(){

        //$this->checkSessionStatus();
	}
	
	/**
	 * 判断session是否过期，过期的话重新登录
	 */
	public function checkSessionStatus(){
		//若当前session失效，跳到登录页面
		if(!isset($_SESSION['user_no'])){
				$this->redirect('Index/login');
		}
	}

	public function getId($type){
	    $id = md5(uniqid($type,true).uniqid('',true));
	    return $id;
    }

}
