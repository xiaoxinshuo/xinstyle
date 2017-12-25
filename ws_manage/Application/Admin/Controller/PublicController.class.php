<?php
namespace Admin\Controller;
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

    /**
     * 文件上传
     */
    function fileUpload($name,$saveUrl,$size){
        //上传错误码
        if($_FILES[$name]['error'] > 0){
            return 1;
        }
        //图片过大
        if($_FILES[$name]['size'] >= $size){
            return 2;
        }
        //文件上传
        if($_FILES[$name]['type'] != 'image/gif' && $_FILES[$name]['type'] != 'image/jpeg' && $_FILES[$name]['type'] != 'image/png'){
            return 3;
        }

        $tmpArr = pathinfo($_FILES[$name]['name']);
        //获取唯一名称
        $uniqid = uniqid();
        $filename 	= $uniqid . '.' . $tmpArr['extension'];
        //判断是否有重名文件
        if(file_exists($filename))
            $filename 	= $uniqid . "_1" . $tmpArr['extension'];
        //return $saveUrl.$filename;
        $result 	= move_uploaded_file($_FILES[$name]["tmp_name"], $saveUrl.$filename);
        if($result){
            $res['name'] = $filename;
            $res['size'] = $_FILES[$name]['size'];
            $res['md5']  = md5_file($saveUrl.$filename);
            return $res;
        }
        return false;
    }

}
