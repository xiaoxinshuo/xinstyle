<?php
namespace Admin\Controller;
use Think\Controller;
class AdvertController extends PublicController {
    function _initialize(){
        $this->checkSessionStatus();
    }

    public function index(){
        $p=I('get.p')?:1;

        $where['status'] = 1;
        $list = M('advert')->where($where)->order('id desc')->select();
        foreach ($list as $k=>$v){
            $list[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }
        $this->assign('list',$list);
        $this->display();
    }

    public function add(){
        $this->display();
    }

    public function addOp(){
        $name = urldecode($_GET['name']);
        $address = urldecode($_GET['address']);
        $date = date('Y-m-d',time());
        //添加商品信息
        $data['name'] = $name;
        $data['address'] = $address;
        $data['status'] = 1;

        $img_num = count($_FILES['file']['name']);
        //添加商品图片
        if ($img_num) {
            $destination = C('PUBLIC_RESOURCE_PATH').'Advert/'.$date.'/';
            if (! is_dir ( $destination )) {
                mkdir ( $destination, 0777, true );
            }
            for ($i=0;$i<$img_num;$i++){
                $image_result = $this->ImageUpload( 'file', $destination, 1024*1024*2,$i);
                switch ($image_result) {
                    case false :
                        // 上传失败
                        $result ['status'] = 0;
                        $result ['msg'] = '上传失败';
                        $this->ajaxReturn ( $result );
                        break;
                    case 1 :
                        // 表单错误+
                        $result ['status'] = 0;
                        $result ['msg'] = '表单错误';
                        $this->ajaxReturn ( $result );
                        break;
                    case 2 :
                        // 图片太大
                        $result ['status'] = 0;
                        $result ['msg'] = '图片太大';
                        $this->ajaxReturn ( $result );
                        break;
                    case 3 :
                        // 图片类型错误
                        $result ['status'] = 0;
                        $result ['msg'] = '图片类型错误';
                        $this->ajaxReturn ( $result );
                        break;
                }

                $data['image_url'] = 'Advert/'.$date.'/'.$image_result['name'];
            }

        }
        $res = M('advert')->add($data);
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '添加失败';
            $this->ajaxReturn ( $result );
        }

        $result ['status'] = 1;
        $result ['msg'] = '添加成功';
        $this->ajaxReturn ( $result );
    }

    public function delOp(){
        $id = $_POST['aid'];
        $res = M('advert')->where(array('id'=>$id))->save(array('status'=>0));
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '删除失败';
            $this->ajaxReturn ( $result );
        }

        $result ['status'] = 1;
        $result ['msg'] = '删除成功';
        $this->ajaxReturn ( $result );
    }

    public function ImageUpload($name,$saveUrl,$size,$index){
        //上传错误码
        if($_FILES[$name]['error'][$index] > 0){
            return 1;
        }
        //图片过大
        if($_FILES[$name]['size'][$index] >= $size){
            return 2;
        }
        //文件上传
        if($_FILES[$name]['type'][$index] != 'image/gif' && $_FILES[$name]['type'][$index] != 'image/jpeg' && $_FILES[$name]['type'][$index] != 'image/png'){
            return 3;
        }

        $tmpArr = pathinfo($_FILES[$name]['name'][$index]);
        //获取唯一名称
        $uniqid = uniqid();
        $filename 	= $uniqid . '.' . $tmpArr['extension'];
        //判断是否有重名文件
        if(file_exists($filename))
            $filename 	= $uniqid . "_1" . $tmpArr['extension'];
        //return $saveUrl.$filename;
        $result 	= move_uploaded_file($_FILES[$name]["tmp_name"][$index], $saveUrl.$filename);
        if($result){
            $res['name'] = $filename;
            $res['size'] = $_FILES[$name]['size'][$index];
            $res['md5']  = md5_file($saveUrl.$filename);
            return $res;
        }
        return false;
    }

}