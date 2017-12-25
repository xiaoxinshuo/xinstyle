<?php
namespace Admin\Controller;
use Think\Controller;
class OrderController extends PublicController {
    function _initialize(){
        $this->checkSessionStatus();
    }
    //订单列表
    public function index(){
        $p=I('get.p')?:1;
        $this->assign('pas',$p);
        $this->display();
    }

    public function search() {
        $orderModel = D('Order');
        $p = I('get.p')?I('get.p'):'1';
        $order_id = $_GET['order_id'];
        $pay_status = $_GET['pay_status'];
        $orderList = $orderModel->getOrderList($p,$order_id,$pay_status);
        foreach ($orderList as $k=>$v){
            $orderList[$k]['pay_date'] = substr($v['payment_time'],0,10);
            $orderList[$k]['create_date'] = substr($v['create_time'],0,10);
            $itemlist = $orderModel->getOrderItem($v['order_id']);
            $orderList[$k]['orderItem'] = $itemlist;
        }
        $this->assign('orderList',$orderList);

        //获取总数量
        $numres = $orderModel->getOrderCount($order_id,$pay_status);
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('pas',$p);
        $this->assign('div','orderIndex');
        echo $this->fetch('ajax');
    }

    //代发货
    public function daifahuo(){
        $p=I('get.p')?:1;
        $orderModel = D('Order');
        $order_id = '';
        $pay_status = 2;
        $orderList = $orderModel->getOrderList($p,$order_id,$pay_status);
        foreach ($orderList as $k=>$v){
            $orderList[$k]['pay_date'] = substr($v['payment_time'],0,10);
            $orderList[$k]['create_date'] = substr($v['create_time'],0,10);
            $itemlist = $orderModel->getOrderItem($v['order_id']);
            $orderList[$k]['orderItem'] = $itemlist;
        }
        $this->assign('orderList',$orderList);

        //获取总数量
        $numres = $orderModel->getOrderCount($order_id,$pay_status);
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('page',$show);

        $this->assign('pas',$p);
        $this->display();
    }

    //已完成
    public function yiwancheng(){
        $p=I('get.p')?:1;
        $orderModel = D('Order');
        $order_id = '';
        $pay_status = 3;
        $orderList = $orderModel->getOrderList($p,$order_id,$pay_status);
        foreach ($orderList as $k=>$v){
            $orderList[$k]['pay_date'] = substr($v['payment_time'],0,10);
            $orderList[$k]['create_date'] = substr($v['create_time'],0,10);
            $itemlist = $orderModel->getOrderItem($v['order_id']);
            $orderList[$k]['orderItem'] = $itemlist;
        }
        $this->assign('orderList',$orderList);

        //获取总数量
        $numres = $orderModel->getOrderCount($order_id,$pay_status);
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('page',$show);

        $this->assign('pas',$p);
        $this->display();
    }

    public function fahuoOp(){
        $order_id = $_POST['order_id'];
        $kuaidi_no = $_POST['kuaidi_no'];

        //获取快递公司
        $comres = $this->getKuaidiCom($kuaidi_no);
        $comArr = json_decode($comres,true);
        $company = $comArr[0]['comCode'];
        error_log(date('Y-m-d H:i:s',time()).'快递公司:'.$comres."\r\n",3,"wuliures.log");
        $key= 'OxilEtrV4309' ;
        $post_data = array();
        $post_data["schema"] = 'json' ;
        $json_data=array(
            'company'=>'tiantian',
            'number'=>$kuaidi_no,
            'key'=>$key,
            'parameters'=>array(
                'callbackurl'=>'http://fzg.shandianbao.top/admin.php/Order/wuliu_callback'
            )
        );

        $post_data["param"] = json_encode($json_data);
        $url='http://www.kuaidi100.com/poll';

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
        $kresult = curl_exec($ch);       //返回提交结果，格式与指定的格式一致（result=true代表成功）
        error_log(date('Y-m-d H:i:s',time()).':'.$kresult."\r\n",3,"wuliures.log");
        $resArr = json_decode($kresult,true);
        if ($resArr['result']){
            //插入快递信息表
            $kdata['order_id'] = $order_id;
            $kdata['kuaidi_no'] = $kuaidi_no;
            $kdata['update_time'] = date('Y-m-d H:i:s',time());
            $res = M('kuaidi_info')->add($kdata);
            if ($res === false){
                $result ['status'] = 0;
                $result ['msg'] = '操作失败';
                $this->ajaxReturn ( $result );
            }

            $where['order_id'] = $order_id;
            $data['kuaidi_no'] = $kuaidi_no;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $data['pay_status'] = 3;
            $res = M('goods_order')->where($where)->save($data);
            if ($res === false){
                $result ['status'] = 0;
                $result ['msg'] = '操作失败';
                $this->ajaxReturn ( $result );
            }

            $result ['status'] = 1;
            $result ['msg'] = '操作成功';
            $this->ajaxReturn ( $result );
        }else{
            $result ['status'] = 0;
            $result ['msg'] = $resArr['message'];
            $this->ajaxReturn ( $result );
        }

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

    public function wuliu_callback(){
        header("Content-Type:text/html;charset=utf-8");
        $param=isset($_POST['param']) && !empty($_POST['param'])?$_POST['param']:'';
        error_log(date('Y-m-d H:i:s',time()).':'.json_encode($_POST)."\r\n",3,"wuliu.log");
        if($param!=''){
            $res=json_decode(stripslashes($param),true);
            $kuaidi_no = $res['lastResult']['nu'];
            $where['kuaidi_no'] = $kuaidi_no;
            $data['text'] = $param;
            $data['update_time'] = date('Y-m-d H:i:s',time());
            $res = M('kuaidi_info')->where($where)->save($data);
            if ($res === false){
                echo  '{"result":"false","returnCode":"500","message":"失败"}';
            }

            echo  '{"result":"true","returnCode":"200","message":"成功"}';
        }

    }

    public function getOrderItem(){
        $orderModel = D('Order');
        $order_id = $_POST['order_id'];
        $itemlist = $orderModel->getOrderItem($order_id);
        $result ['status'] = 1;
        $result ['list'] = $itemlist;
        $this->ajaxReturn ( $result );
    }

    public function export(){
        $from=I("get.from");
        $to=I("get.to");
        $pay_status=I("get.pay_status");

        $con="";
        if ($pay_status > 0){
                $con = " and o.pay_status = {$pay_status} ";
        }

        if($from!=''){
            $from1 =$from.' 00:00:00';
            $con.=" AND o.update_time >= '$from1'";
        }
        if($to!=''){
            $to1 = $to.' 23:59:59';
            $con.=" AND o.update_time <= '$to1'";
        }

        $sql="SELECT o.order_id,o.user_id,o.phone,o.address,o.postage,
            o.money_paid,o.receiver_name,o.payment_time,o.create_time,o.pay_status,
            u.name,u.user_no
            FROM ws_goods_order o  
            JOIN ws_user u ON u.user_id = o.user_id         
            WHERE o.`status` = 1 ".$con." ORDER BY o.update_time ASC";

        $orderList = M()->query($sql);
        $orderModel = D('Order');
        $n=0;
        foreach($orderList as $k=>$v){
            $itemlist = $orderModel->getOrderItem($v['order_id']);
            $orderList[$k]['itemlist'] = $itemlist;

        }
        $order['0']['title']='订单记录';
        $dir='./Public/download/order/';
        $outputFileName = $dir."order data".'.xlsx';
        @unlink ($outputFileName);
        require_once VENDOR_PATH.'phpExcel/PHPExcel.php';
        require_once VENDOR_PATH.'phpExcel/PHPExcel/Writer/Excel2007.php';
        require_once VENDOR_PATH.'phpExcel/PHPExcel/Cell/DataType.php';

        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objExcel);
        $objProps = $objExcel->getProperties ();
        $objProps->setCreator ( 'order');
        $objProps->setLastModifiedBy("order");
        $objProps->setDescription("订单列表");
        $objProps->setTitle ($order['0']['title']);
        $objProps->setSubject($order['0']['title']);
        $objProps->setKeywords ($order['0']['title']);
        $objProps->setCategory ("订单列表");

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(1);
        $objActSheet = $objExcel->getActiveSheet ();
        //表头
        $objActSheet->setCellValue ( 'A1', '订单编号');

        $objActSheet->setCellValue ( "B1", '会员名称');
        $objActSheet->setCellValue ( "C1", '总金额');
        $objActSheet->setCellValue ( "D1", '运费');
        $objActSheet->setCellValue ( "E1", '收件人');
        $objActSheet->setCellValue ( "F1", '收件人地址');
        $objActSheet->setCellValue ( "G1", '联系电话');
        $objActSheet->setCellValue ( "H1", '状态');
        $n = 1;
        foreach ($orderList as $k=>$v){
            if ($v['pay_status'] == 1){
                $pay_text = '未支付';
            }elseif ($v['pay_status'] == 2){
                $pay_text = '已付款';
            }elseif ($v['pay_status'] == 3){
                $pay_text = '已发货';
            }
            $n = $n + 1;
            $objActSheet->setCellValue ( 'A'.$n, $v['order_id']);
            $objActSheet->setCellValue ( 'B'.$n, $v['name'].'('.$v['user_no'].')');
            $objActSheet->setCellValue ( 'C'.$n, $v['money_paid']);
            $objActSheet->setCellValue ( 'D'.$n, $v['postage']);
            $objActSheet->setCellValue ( 'E'.$n, $v['receiver_name']);
            $objActSheet->setCellValue ( 'F'.$n, $v['address']);
            $objActSheet->setCellValue ( 'G'.$n, $v['phone']);
            $objActSheet->setCellValue ( 'H'.$n, $pay_text);

            foreach ($v['itemlist'] as $a=>$b){
                $n = $n + 1;
                $objActSheet->setCellValue ( 'B'.$n, '商品名称：'.$b['good_name']);
                $objActSheet->setCellValue ( 'C'.$n, '商品规格：'.$b['good_format']);
                $objActSheet->setCellValue ( 'D'.$n, '商品数量：'.$b['number']);

            }
        }


        $objWriter->save($outputFileName);
        $downloadObject=new \Think\Download;
        $config=
            [
                'file'=>$outputFileName,
                'name'=>'订单记录'.'.xlsx',
            ];

        $downloadObject->initialize($config)->start();
    }


}