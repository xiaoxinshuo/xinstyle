<?php
namespace Admin\Controller;
use Think\Controller;
class GoodsController extends PublicController {
    function _initialize(){
        $this->checkSessionStatus();
    }
    //商品列表
    public function index(){
        $goodsModel = D('Goods');
        $p = I('get.p')?I('get.p'):'1';
        $goodsList = $goodsModel->getGoodsList($p);
        foreach ($goodsList as $k=>$v){
            $goodsList[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }
        $this->assign('goodsList',$goodsList);

        //获取总数量
        $numres = $goodsModel->getGoodsCount();
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('pas',$p);
        $this->display();
    }
    //添加商品
    public function add(){
        $this->display();
    }

    public function addOp(){
        $name = urldecode($_GET['name']);
        $format = urldecode($_GET['format']);
        $price = urldecode($_GET['price']);
        //添加商品信息
        $good_id =md5(uniqid('goods',true).uniqid('',true));
        $data['good_id'] = $good_id;
        $data['good_name'] = $name;
        $data['good_format'] = $format;
        $data['price'] = $price;
        $data['status'] = 1;
        $res = M('goods')->add($data);
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '添加商品失败';
            $this->ajaxReturn ( $result );
        }

            $img_num = count($_FILES['file']['name']);
        //添加商品图片
        if ($img_num) {
            $destination = C('PUBLIC_RESOURCE_PATH').'Goods/'.$good_id.'/';
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

                //存入数据库
                $imageData['image_id'] = md5(uniqid('goodsImage',true).uniqid('',true));
                $imageData['good_id'] = $good_id;
                $imageData['image_url'] = 'Goods/'.$good_id.'/'.$image_result['name'];
                $imageData['image_size'] = $image_result['size'];
                $imageData['image_md5'] = $image_result['md5'];
                $imageData['upload_time'] = date('Y-m-d H:i:s',time());
                $res = M('goodsImage')->add($imageData);

                if ($res === false){
                    $result ['status'] = 0;
                    $result ['msg'] = '添加商品图片失败';
                    $this->ajaxReturn ( $result );
                }
            }

        }

        $result ['status'] = 1;
        $result ['msg'] = '添加商品成功';
        $this->ajaxReturn ( $result );
    }
    //删除商品
    public function delGood(){
        $good_id = $_POST['good_id'];

        $where['good_id'] = $good_id;
        $data['status'] = 0;
        $res = M('Goods')->where($where)->save($data);
        if ($res == 1){
            $result['msg'] = '删除商品成功';
            $result['status'] = 1;
        }else{
            $result['msg'] = '删除商品失败';
            $result['status'] = 0;
        }

        $this->ajaxReturn($result);
    }

    //编辑商品页面
    public function edit(){
        $good_id = $_GET['good_id'];
        $goodInfo = M('goods')->where(array('good_id'=>$good_id))->find();
        $imageList = M('goods_image')->where(array(
            'good_id'=>$good_id,
            'status'=>1
        ))->select();
        foreach ($imageList as $k=>$v){
            $imageList[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }
        $this->assign('goodInfo',$goodInfo);
        $this->assign('imageList',$imageList);
        $this->display();
    }

    public function editOp(){
        $name = urldecode($_GET['name']);
        $format = urldecode($_GET['format']);
        $price = urldecode($_GET['price']);
        $good_id = urldecode($_GET['good_id']);
        //编辑商品信息
        $where['good_id'] = $good_id;
        $data['good_name'] = $name;
        $data['good_format'] = $format;
        $data['price'] = $price;
        $res = M('goods')->where($where)->save($data);
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '编辑商品失败';
            $this->ajaxReturn ( $result );
        }

        $img_num = count($_FILES['file']['name']);
        //添加商品图片
        if ($img_num) {
            $destination = C('PUBLIC_RESOURCE_PATH').'Goods/'.$good_id.'/';
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

                //存入数据库
                $imageData['image_id'] = md5(uniqid('goodsImage',true).uniqid('',true));
                $imageData['good_id'] = $good_id;
                $imageData['image_url'] = 'Goods/'.$good_id.'/'.$image_result['name'];
                $imageData['image_size'] = $image_result['size'];
                $imageData['image_md5'] = $image_result['md5'];
                $imageData['upload_time'] = date('Y-m-d H:i:s',time());
                $res = M('goodsImage')->add($imageData);

                if ($res === false){
                    $result ['status'] = 0;
                    $result ['msg'] = '添加商品图片失败';
                    $this->ajaxReturn ( $result );
                }
            }
        }

        $result ['status'] = 1;
        $result ['msg'] = '编辑商品成功';
        $this->ajaxReturn ( $result );
    }

    // 单张图片删除ajax动作
    public function deleteImage(){

        $image_id=I('get.image_id','');
        $where['image_id'] = $image_id;
        $data['status'] = 0;
        $res = M('goods_image')->where($where)->save($data);
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '删除图片失败';
            $this->ajaxReturn ( $result );
        }
        $result ['status'] = 1;
        $result ['msg'] = '删除图片成功';
        $this->ajaxReturn ( $result );
    }

    public function wuliu(){
        $where['id'] = 1;
        $res = M('config')->where($where)->find();
        $this->assign('initial_postage',$res['initial_postage']);
        $this->assign('initial_good_num',$res['initial_good_num']);
        $this->assign('overstep_postage',$res['overstep_postage']);
        $this->assign('overstep_good_num',$res['overstep_good_num']);
        $this->display();
    }

    public function wuliuOp(){

        $where['id'] = 1;
        $data['initial_postage'] = $_POST['initial_postage'];
        $data['initial_good_num'] = $_POST['initial_good_num'];
        $data['overstep_postage'] = $_POST['overstep_postage'];
        $data['overstep_good_num'] = $_POST['overstep_good_num'];
        $res = M('config')->where($where)->save($data);
        if ($res === false){
            $result ['status'] = 0;
            $this->ajaxReturn ( $result );
        }
        $result ['status'] = 1;
        $this->ajaxReturn ( $result );
        /*$file = CONF_PATH.'/config.php';
        $fileArr = include($file);
        $config = array(
            'INITIAL_POSTAGE' => $_POST['initial_postage'],
            'INITIAL_GOOD_NUM' => $_POST['initial_good_num'],
            'OVERSTEP_POSTAGE' => $_POST['overstep_postage'],
            'OVERSTEP_GOOD_NUM' => $_POST['overstep_good_num'],
        );


        //合并数组，相同键名，后面的值会覆盖原来的值
        $res = array_merge($fileArr, $config);

        //数组循环，拼接成php文件
        $str = '<?php return array(';

        foreach ($res as $key => $value){
            // '\'' 单引号转义
            $str .= '\''.$key.'\''.'=>'.'\''.$value.'\''.','."\r\n";
        };
        $str .= '); ?>';
        if(file_put_contents($file, $str)){
            $result ['status'] = 1;
            $this->ajaxReturn ( $result );
        }else {
            $result ['status'] = 0;
            $this->ajaxReturn ( $result );
        }*/
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