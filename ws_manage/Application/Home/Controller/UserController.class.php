<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends PublicController {
    //我的页面
    public function index(){
        $user_id = $_SESSION['user_id'];
        $info = M('User')->where(array('user_id'=>$user_id))->find();
        $this->assign('name',$info['name']);
        $this->assign('user_id',$user_id);
        $this->display();
    }

    //下级代理页面
    public function lowerAgent(){
        $user_id = $_SESSION['user_id'];
        $arr = $this->getAllChildIds($user_id);
        $list = $arr['idsArr'];
        $this->assign('list',$list);
        $this->display();
    }

    //添加代理页面
    public function addAgent(){
        $recommender_id = $_GET['uid'];
        $erweima = 0;
        if ($recommender_id){
            $erweima = 1;
            $this->assign('recommender_id',$recommender_id);
        }
        //获取银行列表
        $where['status'] = 1;
        $where['bank_no'] = array('lt',2000);
        $bankList = M('bank_dict')->where($where)->select();
        $this->assign('bankList',$bankList);
        $this->assign('erweima',$erweima);
        $this->display();
    }

    public function addAgentOp(){
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $user_no = $_POST['user_no'];
        $password = trim($_POST['password']);
        //$bankName = $_POST['bankName'];
        //$bankNum = $_POST['bankNum'];
        $bankUserName = $_POST['bankUserName'];
        $alipayAccount = $_POST['alipayAccount'];
        $wxAccount = $_POST['wxAccount'];
        $email = $_POST['email'];
        if ($_POST['erweima']){
            $recommender_id = $_POST['recommender_id'];
        }else{
            $recommender_id = $_SESSION['user_id'];
        }


        //检查手机号是否已经注册
        $where['user_no'] = $user_no;
        $where['status'] = 1;
        $phoneres = M('User')->where($where)->find();
        if ($phoneres){
            $result['msg'] = '该手机号已存在';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        //开启事务
        M('User')->startTrans();
        //向用户表新增数据
        $user_id = $this->getId('user');
        $userData['user_id'] = $user_id;
        $userData['user_no'] = $user_no;
        $userData['name'] = $name;
        $userData['phone'] = $phone;
        $userData['password'] = md5($password);
        $userData['utype'] = 11;
        $userData['email'] = $email;
        $userData['alipayAccount'] = $alipayAccount;
        $userData['wxAccount'] = $wxAccount;
        $userData['recommender_id'] = $recommender_id;
		$userData['real_name'] = $bankUserName;
        $userData['create_time'] = date('Y-m-d H:i:s',time());
        $res = M('User')->add($userData);
        if ($res === false){
            M('User')->rollback();
            $result['msg'] = '添加代理信息失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        //向用户银行关联表添加
        /*$rel_id = $this->getId('userbankrel');
        $reldata['rel_id'] = $rel_id;
        $reldata['user_id'] = $user_id;
        $reldata['bank_no'] = $bankName;
        $reldata['card_no'] = $bankNum;
        $reldata['user_name'] = $bankUserName;
        $reldata['create_time'] = date('Y-m-d H:i:s',time());
        $reldata['status'] = 1;
        $res = M('user_bank_rel')->add($reldata);
        if ($res === false){
            M('User')->rollback();
            $result['msg'] = '添加银行信息失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }*/

        M('User')->commit();
        $result['msg'] = '添加成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);

    }

    //我的订单页面
    public function mimeOrder(){
        $p = 1;
        $user_id = $_SESSION['user_id'];
        $status = $_GET['status']?:$_POST['status'];
        $OrderModel = D('Order');
        $list = $OrderModel->getUserOrderList($user_id,$status,$p);
        foreach ($list as $k=>$v){
            if ($v['pay_status'] == 1){
                $list[$k]['pay_text'] = '未支付';
            }elseif($v['pay_status'] == 2){
                $list[$k]['pay_text'] = '已支付';
            }elseif($v['pay_status'] == 3){
                $list[$k]['pay_text'] = '已发货';
            }

            $itemlist = $OrderModel->getOrderItem($v['order_id']);
            $list[$k]['orderItem'] = $itemlist;
        }
        $this->assign('status_type',$status);
        $this->assign('list',$list);
        $this->display();
    }

    public function ajaxOrder(){
        $user_id = $_SESSION['user_id'];
        $status = $_POST['status'];
        $p = $_POST['page']?:1;
        $OrderModel = D('Order');
        $list = $OrderModel->getUserOrderList($user_id,$status,$p);
        foreach ($list as $k=>$v){
            if ($v['pay_status'] == 1){
                $list[$k]['pay_text'] = '未支付';
            }elseif($v['pay_status'] == 2){
                $list[$k]['pay_text'] = '已支付';
            }elseif($v['pay_status'] == 3){
                $list[$k]['pay_text'] = '已发货';
            }

            $itemlist = $OrderModel->getOrderItem($v['order_id']);
            $list[$k]['orderItem'] = $itemlist;
        }
        if ($list){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $result['list'] = $list;
        $this->ajaxReturn($result);
    }

    //收货地址管理页面
    public function addressList(){
        $user_id = $_SESSION['user_id'];
        $list = D('Address')->getAddressList($user_id);
        $this->assign('list',$list);
        $this->display();
    }
    //添加地址页面
    public function addAddress(){
        $this->display();
    }
    //添加地址操作
    public function addAddressOp(){
        $UserAddress = M("user_address");
        $data['address_id'] = $this->getId('useraddress');
        $data['user_id'] = $_SESSION['user_id'];
        $data['name'] = $_POST['name'];
        $data['phone'] = $_POST['phone'];
        $data['province_id'] = $_POST['province'];
        $data['city_id'] = $_POST['city'];
        $data['county_id'] = $_POST['county'];
        $data['address'] = $_POST['address'];
        $data['create_time'] = date('Y-m-d H:i:s',time());
        $data['remarks'] = $_POST['remarks'];
        $data['status'] = 1;
        $res = $UserAddress->add($data);
        if (!$res){
            $result['msg'] = '添加失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        $result['msg'] = '添加成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    public function delAddress(){
        $UserAddress = M("user_address");
        $where['address_id'] = $_POST['address_id'];
        $data['status'] = 0;
        $res = $UserAddress->where($where)->save($data);
        if (!$res){
            $result['msg'] = '删除失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        $result['msg'] = '删除成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    public function editAddress(){
        $address_id = $_GET['address_id'];
        $where['address_id'] = $address_id;
        $info = M('user_address')->where($where)->find();
        $this->assign('info',$info);
        $this->display();
    }

    public function editAddressOp(){
        $UserAddress = M("user_address");
        $where['address_id'] = $_POST['address_id'];
        $where['status'] = 1;

        $data['name'] = $_POST['name'];
        $data['phone'] = $_POST['phone'];
        $data['province_id'] = $_POST['province'];
        $data['city_id'] = $_POST['city'];
        $data['county_id'] = $_POST['county'];
        $data['address'] = $_POST['address'];
        $data['remarks'] = $_POST['remarks'];

        $res = $UserAddress->where($where)->save($data);
        if ($res === false){
            $result['msg'] = '编辑失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        $result['msg'] = '编辑成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //修改个人信息
    public function editUserInfo(){
        $user_id = $_SESSION['user_id'];
        //求出银行列表
        $where['status'] = 1;
        $banklist = M('bank_dict')->where($where)->select();
        $this->assign('banklist',$banklist);

        $userinfo = M('user')->where(array('user_id'=>$user_id))->find();
        $this->assign('userinfo',$userinfo);

        $wh['user_id'] = $user_id;
        $wh['status'] = 1;
        $ubinfo = M('user_bank_rel')->where($wh)->order('id desc')->find();
        $this->assign('ubinfo',$ubinfo);

        $this->display();
    }

    //修改个人信息操作
    public function editUserInfoOp(){
        $name = $_POST['name'];
        $bankName = $_POST['bankName'];
        $bankNum = $_POST['bankNum'];
        $bankUserName = $_POST['bankUserName'];
        $alipayAccount = $_POST['alipayAccount'];
        $wxAccount = $_POST['wxAccount'];
        $email = $_POST['email'];
        $rel_id = $_POST['rel_id'];

        //开启事务
        M('User')->startTrans();
        //向用户表修改数据
        $user_id = $_SESSION['user_id'];
        $userWhere['user_id'] = $user_id;

        $userData['name'] = $name;
        $userData['email'] = $email;
        $userData['alipayAccount'] = $alipayAccount;
        $userData['wxAccount'] = $wxAccount;
        $res = M('User')->where($userWhere)->save($userData);
        if ($res === false){
            M('User')->rollback();
            $result['msg'] = '编辑失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        //向用户银行关联表修改信息
        $relwhere['rel_id'] = $rel_id;
        $reldata['bank_no'] = $bankName;
        $reldata['card_no'] = $bankNum;
        $reldata['user_name'] = $bankUserName;
        $reldata['status'] = 1;
        $res = M('user_bank_rel')->where($relwhere)->save($reldata);
        if ($res === false){
            M('User')->rollback();
            $result['msg'] = '修改银行信息失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        M('User')->commit();
        $result['msg'] = '修改成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //修改密码
    public function editPwd(){
        $this->display();
    }

    //修改密码操作
    public function editPwdOp(){
        $user_id = $_SESSION['user_id'];
        $password = $_POST['password'];
        $new_password = trim($_POST['new_password']);

        //比较密码
        $where['user_id'] = $user_id;
        $info = M('User')->where($where)->find();
        if ($info['password'] !== md5($password)){
            $result['msg'] = '原密码错误';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        //向用户表修改数据

        $userWhere['user_id'] = $user_id;
        $userData['password'] = md5($new_password);
        $res = M('User')->where($userWhere)->save($userData);
        if ($res === false){
            $result['msg'] = '修改失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }
        $result['msg'] = '修改成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //历史奖金
    public function historicalBonus(){
        $this->display();
    }

    //审核代理
    public function checkAgent() {
        $this->display();
    }

    //查看物流
    public function wuliuDetail(){
        $order_id = $_GET['oid'];

        //找出快递单号
        $orderInfo = M('goods_order')->where(array('order_id'=>$order_id))->find();

        //获取快递公司
        $comres = $this->getKuaidiCom($orderInfo['kuaidi_no']);
        $comArr = json_decode($comres,true);
        $company = $comArr[0]['comCode'];
        //查找出信息
        /*$kuaidiInfo = M('kuaidi_info')->where(array('kuaidi_no'=>$orderInfo['kuaidi_no']));
        $res = json_decode(stripslashes($kuaidiInfo['text']),true);
        $list = $res['lastResult']['data'];
        $this->assign('list',$list);*/


        $post_data = array();
        $post_data["customer"] = '87D57EF370CDF55B6C8D16B6BD9712C7';
        $key= 'OxilEtrV4309' ;
        $post_data["param"] = '{"com":"'.$company.'","num":"'.$orderInfo['kuaidi_no'].'"}';

        $url='http://poll.kuaidi100.com/poll/query.do';
        $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
        $post_data["sign"] = strtoupper($post_data["sign"]);
        $o="";
        foreach ($post_data as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
        }
        $post_data=substr($o,0,-1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        $data = str_replace("\"",'"',$result );
        $data = json_decode($data,true);
	    $list = $data['data'];
        $this->assign('list',$list);

        //查询出哪一家快递公司
        //$key= 'OxilEtrV4309' ;
        //$company_url = 'http://www.kuaidi100.com/autonumber/auto?num='.$orderInfo['kuaidi_no'].'&key='.$key;
        /*$company_url = 'http://www.kuaidi100.com/autonumber/auto?num=469135630091&key='.$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$company_url);
        $result = curl_exec($ch);
        $data = str_replace("\"",'"',$result );
        $data = json_decode($data);
        $tmp = $data[0]['comCode'];
        var_dump($tmp);
        exit;*/


        $this->display();
    }

    public function getKuaidiCom($kuaidi_no){
        $key= 'OxilEtrV4309' ;
        $company_url = 'http://www.kuaidi100.com/autonumber/auto?num='.$kuaidi_no.'&key='.$key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$company_url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }



    public function provinceAjax(){
        if (IS_AJAX) {
            $map['area_deep'] = 1;
            $map['area_parent_id'] = 0;
            $cityModel = M('city');
            $data = $cityModel->field('id,area_name')->where($map)->select();
            $this->ajaxReturn($data);
        }
    }

    public function cityAjax(){
        if(IS_AJAX){
            $province = I('post.province');
            $zone = I('post.zone');
            if(!empty($province)){
                $map['area_deep'] = 2;
                $map['area_parent_id'] = $province;
                $cityModel = M('city');
                $data = $cityModel->field('id,area_name')->where($map)->select();
                $this->ajaxReturn($data);
            }elseif(!empty($zone)){
                $SchM = D('school');
                $data = $SchM->getAreaData($zone);
                $this->ajaxReturn($data);
            }
        }
    }

    public function countyAjax(){
        if(IS_AJAX){
            $city = I('post.city');
            $zone = I('post.zone');
            if(!empty($city)){
                $map['area_deep'] = 3;
                $map['area_parent_id'] = $city;
                $cityModel = M('city');
                $data = $cityModel->field('id,area_name')->where($map)->select();
                $this->ajaxReturn($data);
            }else if(!empty($zone)){
                $SchM = D('school');
                $data = $SchM->getAreaData($zone);
                $this->ajaxReturn($data);
            }

        }
    }

    public function getAllChildIds($categoryID){
        $OrderModel = D('Order');
        //初始化ID数组

        do
        {
            $ids = '';
            $where['recommender_id'] = array('in',$categoryID);
            $where['status'] = 1;
            $where['is_check'] = 1;
            $where['utype'] = 11;
            $cate = M('User')->where($where)->select();
            foreach ($cate as $k=>$v)
            {
                $array[] = $v;
                $ids .= ',' . $v['user_id'];
            }
            $ids = substr($ids, 1, strlen($ids));
            $categoryID = $ids;
        }
        while (!empty($cate));
        $ids = implode(',', $array);
        $res['ids'] = $ids;
        $res['idsArr'] = $array;
        return $res;    //  返回字符串
        //return $array //返回数组
    }

}