<?php
namespace Home\Controller;
use Think\Controller;
require_once VENDOR_PATH.'wxpay/lib/WxPay.Api.php';
require_once VENDOR_PATH."wxpay/example/WxPay.JsApiPay.php";
require_once VENDOR_PATH."wxpay/lib/WxPay.Data.php";
require_once VENDOR_PATH.'wxpay/example/log.php';
class OrderController extends PublicController {
    public function goodsList(){
        $p = 1;
        $list = D('Goods')->getGoodsList($p);
        foreach ($list as $k=>$v){
            $list[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }

        $this->assign('list',$list);
        $this->display();
    }

    //异步加载商品
    public function ajaxGoodsList(){
        $page = $_POST['page'];
        $list = D('Goods')->getGoodsList($page);
        foreach ($list as $k=>$v){
            $list[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }
        if ($list){
            $result['status'] = 1;
        }else{
            $result['status'] = 0;
        }
        $result['list'] = $list;
        $this->ajaxReturn($result);
    }

    //添加预订单
    public function addPreOrder(){

        $dish = $_POST['dish'];
        $dishArr = explode(',',$dish);
        $money = 0.00;
        $order_id = $this->getId('order');
        $date = date('Y-m-d H:i:s',time());
        $goods_num = 0;
        M('goods_order')->startTrans();
        for ($i=0;$i<count($dishArr);$i++){
            if (empty($dishArr[$i])){
                continue;
            }
            $tmpArr = explode('-',$dishArr[$i]);
            $goods_num = $goods_num + $tmpArr['1'];
            //计算价格
            $goodWhere['good_id'] = $tmpArr['0'];
            $goodInfo = M('goods')->where($goodWhere)->find();
            $money = $goodInfo['price'] * $tmpArr['1'] + $money;
            //item表添加数据
            $itemData = array();
            $itemData['order_id'] = $order_id;
            $itemData['good_id'] = $tmpArr['0'] ;
            $itemData['good_format'] = $goodInfo['good_format'];
            $itemData['number'] = $tmpArr['1'];
            $itemData['price'] = $goodInfo['price'];
            $itemData['create_time'] = $date;
            $itemData['status'] = 1;
            $res = M('goods_order_item')->add($itemData);
            if ($res === false){
                M('goods_order')->rollback();
                $result['msg'] = '添加订单信息失败';
                $result['status'] = 0;
                $this->ajaxReturn($result);
            }
        }

        //计算运费
        $yfInfo = M('config')->where(array('id'=>1))->find();
        if ($goods_num > $yfInfo['initial_good_num']){
            $over_num = $goods_num-$yfInfo['initial_good_num'];
            $postage = ceil($over_num/$yfInfo['overstep_good_num'])*$yfInfo['overstep_postage'] + $yfInfo['initial_postage'];
        }else{
            $postage = $yfInfo['initial_postage'];
        }


        //添加order数据
        $orderData['order_id'] = $order_id;
        $orderData['user_id'] = $_SESSION['user_id'];
        $orderData['money_paid'] = $money;
        $orderData['postage'] = $postage;
        $orderData['create_time'] = $date;
        $orderData['update_time'] = $date;
        $res = M('goods_order')->add($orderData);
        if ($res === false){
            M('goods_order')->rollback();
            $result['msg'] = '添加信息失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        //向ws_goods_order_pay添加数据
        $pay_id = $this->getId('pay');
        $payData['pay_id'] = $pay_id;
        $payData['order_id'] = $order_id;
        $payData['user_id'] = $_SESSION['user_id'];
        $payData['payment_way'] = 1;
        $res = M('goods_order_pay')->add($payData);
        if ($res === false){
            M('goods_order')->rollback();
            $result['msg'] = '添加信息失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        M('goods_order')->commit();
        session('out_trade_no',$pay_id);
        $result['order_id'] = $order_id;
        $result['pay_id'] = $pay_id;
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //下单页面
    public function newOrder(){
        //$order_id = $_GET['order'];
        $pay_id = $_SESSION['out_trade_no'];
	$out_trade_no = $_SESSION['out_trade_no'];
        $payInfo = M('goods_order_pay')->where(array('pay_id'=>$pay_id))->find();
        $order_id = $payInfo['order_id'];
        $info = M('goods_order')->where(array('order_id'=>$order_id))->find();

        $goodlist = D('Order')->getGoodsInfoByOrder($order_id);
        foreach ($goodlist as $k=>$v){
            $goodlist[$k]['total'] = $v['number'] * $v['price'];
        }
        $this->assign('goodlist',$goodlist);
        $this->assign('order_id',$order_id);
        //支付金额 = 商品金额+运费
        $total_money = $info['money_paid'] + $info['postage'];
        $this->assign('money',$total_money);
        $this->assign('postage',$info['postage']);
		
		//微信金额
		$weixin_money = $total_money * 100;

        //获取微信支付相关

        //初始化日志
        $logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);

        //①、获取用户openid
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("test");
        $input->SetAttach("test");
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($weixin_money);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url("http://fzg.shandianbao.top/index.php/Order/wxpayNotify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);
        
        $jsApiParameters = $tools->GetJsApiParameters($order);
		$jsApiParamArr = json_decode($jsApiParameters,true);
		//var_dump($jsApiParamArr);exit;
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
        $editAddressArr = json_decode($editAddress,true);
        $this->assign('jsApiParameters',$jsApiParameters);
        $this->assign('editAddress',$editAddress);
		$this->assign('jsApiParamArr',$jsApiParamArr);
        $this->assign('editAddressArr',$editAddressArr);

        $this->display();
    }

    public function wxpayNotify(){
        $xml=$GLOBALS['HTTP_RAW_POST_DATA'];
        $weixinObject=new \WxPayDataBase;
        $notify=$weixinObject->FromXml($xml);
        error_log("收到了".date('Y-m-d H:i:s',time()).'---'.json_encode($notify)."\r\n", 3, "wxpayNotify.log");
        // 验证是否返回数据结果
        if ('SUCCESS'==$notify['return_code']){
            $result=
                [
                    'out_trade_no'=>$notify['out_trade_no'],
                    'money'=>$notify['cash_fee'], // /100
                    'payment_way'=>'weixinPay'
                ];
            $this->handleNotify($result);
        }

    }

    private function handleNotify($notify)
    {
        header('Content-Type: text/xml');

        // 验证数据是否存在
        $payDetail = M('goods_order_pay')->where(array('pay_id'=>$notify['out_trade_no']))->find();
        if (!$payDetail){
            exit;
        }
        // 验证数据是否已经处理 [成功交易过或者被处理为失败]
        if ($payDetail['pay_status']==2){
            echo "<xml>
                <return_code><![CDATA[SUCCESS]]></return_code>
                <return_msg><![CDATA[OK]]></return_msg>
            </xml>";

            exit;
        }
        M('goods_order')->startTrans();
        //更改状态
        $nowdate = date('Y-m-d H:i:s',time());
        $orderWhere['order_id'] = $payDetail['order_id'];
        $orderData['pay_status'] = 2;
        $orderData['payment_way'] = 1;
        $orderData['update_time'] = $nowdate;
        $orderData['payment_time'] = $nowdate;
        $res = M('goods_order')->where($orderWhere)->save($orderData);
        if ($res === false){
            M('goods_order')->rollback();
            exit;
        }

        $payWhere['pay_id'] = $notify['out_trade_no'];
        $payData['pay_status'] = 2;
        $payData['money_paid'] = $notify['money'];
        $payData['payment_time'] = $nowdate;
        $res = M('goods_order_pay')->where($payWhere)->save($payData);
        if ($res === false){
            M('goods_order')->rollback();
            exit;
        }
        M('goods_order')->commit();
        echo "<xml>
			  	<return_code><![CDATA[SUCCESS]]></return_code>
			  	<return_msg><![CDATA[OK]]></return_msg>
			</xml>";
    }

    public function editOrderAddress(){
        $old_address_id = $_POST['old_address_id'];
        $order_id = $_POST['order_id'];
        //如果选了已存在的地址，就直接使用已存在的地址
        if ($old_address_id){
            $old_address_name = $_POST['old_address_name'];
            $old_address_phone = $_POST['old_address_phone'];
            $old_address_text = $_POST['old_address_text'];
            $res = M('goods_order')->where(array(
                'order_id'=>$order_id
            ))->save(array(
                'address_id'=>$old_address_id,
                'receiver_name'=>$old_address_name,
                'phone'=>$old_address_phone,
                'address'=>$old_address_text
            ));
            if ($res === false){
                $result['msg'] = '添加地址失败';
                $result['status'] = 0;
                $this->ajaxReturn($result);
            }
            $result['msg'] = '添加地址成功';
            $result['status'] = 1;
            $this->ajaxReturn($result);
        }else{
            //新地址的话，先往地址表添加数据，再更改订单表
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
            $res = M("user_address")->add($data);
            if (!$res){
                $result['msg'] = '添加地址失败';
                $result['status'] = 0;
                $this->ajaxReturn($result);
            }

            $res = M('goods_order')->where(array(
                'order_id'=>$order_id
            ))->save(array(
                'address_id'=>$data['address_id'],
                'receiver_name'=>$_POST['name'],
                'phone'=>$_POST['phone'],
                'address'=>$_POST['address']
            ));
            if ($res === false){
                $result['msg'] = '添加地址失败';
                $result['status'] = 0;
                $this->ajaxReturn($result);
            }
            $result['msg'] = '添加地址成功';
            $result['status'] = 1;
            $this->ajaxReturn($result);
        }
    }

    //常用收货地址
    public function offenAddressList(){
        $user_id = $_SESSION['user_id'];
        $list = D('Address')->getAddressList($user_id);
        $this->assign('list',$list);
        
        $this->display();
    }
}