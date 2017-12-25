<?php
namespace Admin\Controller;
use Think\Controller;
class CommissionController extends PublicController {
    function _initialize(){
        $this->checkSessionStatus();
    }

    public function rules(){
        $list = M('commition_rule')->where(array('status'=>1))->select();
        $this->assign('list',$list);
        $this->display();
    }

    public function jiesuan(){
        $p=I('get.p')?:1;
        $this->assign('pas',$p);
        $this->display();
    }

    public function ajaxGet(){
        $UserModel = D('User');
        $OrderModel = D('Order');
        $from = $_GET['from'];
        $user_no = $_GET['user_no'];
        $p = I('get.p')?I('get.p'):'1';

        $firstday = date('Y-m-01 00:00:00', strtotime($from));
        $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));

        $userlist = $UserModel -> getAgentList($p,$user_no);
        foreach($userlist as $k=>$v){
            //个人当月销量
            $selfMonthSales = $OrderModel->getSelfOrderSales($v['user_id'],$firstday,$lastday);
            //个人奖金
            $selfAward = $OrderModel->getSelfAward($selfMonthSales);
            $ures = $this->getTeamAward($v['user_id'],$selfMonthSales,$firstday,$lastday);
            $userlist[$k]['selfMonthSales'] = $selfMonthSales;
            $userlist[$k]['selfAward'] = $selfAward;
            $userlist[$k]['teamMonthSale'] = $ures['teamMonthSale'];
            $userlist[$k]['teamMonthAward'] = $ures['teamMonthAward'];
            $userlist[$k]['allAward'] = $ures['teamMonthAward'] + $selfAward;
            $userlist[$k]['jiesuanDate'] = $from;

        }

        $this->assign('userlist',$userlist);
        //获取总数量
        $numres = $UserModel->getAgentNum($user_no);
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('pas',$p);
        echo $this->fetch('ajax');

    }

    public function addOp(){
        $start = $_POST['start'];
        $end = $_POST['end'];
        $rate = $_POST['rate'];
        $data['start'] = $start;
        $data['end'] = $end;
        $data['rate'] = $rate;
        $res = M('commition_rule')->add($data);
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '添加失败';
            $this->ajaxReturn ( $result );
        }

        $result ['status'] = 1;
        $result ['msg'] = '添加成功';
        $this->ajaxReturn ( $result );
    }

    public function editOp(){
        $id = $_POST['id'];
        $start = $_POST['start'];
        $end = $_POST['end'];
        $rate = $_POST['rate'];

        //通过查找数据库是否存在id，确定是添加还是编辑
        $map['id'] = $id;
        $info = M('commition_rule')->where($map)->find();
        if ($info){
            $where['id'] = $id;
            $data['start'] = $start;
            $data['end'] = $end;
            $data['rate'] = $rate;
            $res = M('commition_rule')->where($where)->save($data);
            if ($res === false){
                $result ['status'] = 0;
                $result ['msg'] = '编辑失败';
                $this->ajaxReturn ( $result );
            }

            $result ['status'] = 1;
            $result ['msg'] = '编辑成功';
            $this->ajaxReturn ( $result );
        }else{
            $data['id'] = $id;
            $data['start'] = $start;
            $data['end'] = $end;
            $data['rate'] = $rate;
            $res = M('commition_rule')->add($data);
            if ($res === false){
                $result ['status'] = 0;
                $result ['msg'] = '添加失败';
                $this->ajaxReturn ( $result );
            }

            $result ['status'] = 1;
            $result ['msg'] = '添加成功';
            $this->ajaxReturn ( $result );
        }


    }

    public function delOp(){
        $id = $_POST['id'];

        $where['id'] = $id;
        $data['status'] = 0;
        $res = M('commition_rule')->where($where)->save($data);
        if ($res === false){
            $result ['status'] = 0;
            $result ['msg'] = '删除失败';
            $this->ajaxReturn ( $result );
        }

        $result ['status'] = 1;
        $result ['msg'] = '删除成功';
        $this->ajaxReturn ( $result );
    }

    public function export(){
        $from=I("get.from");

        $con="";
        $firstday = date('Y-m-01 00:00:00', strtotime($from));
        $lastday = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));
        $sql="SELECT * FROM ws_user u 
	      WHERE u.utype = 11 AND u.`status` = 1 AND u.is_freeze = 0 
	      AND u.is_check = 1 ORDER BY u.check_time ASC";

        $userList = M()->query($sql);
        $OrderModel = D('Order');
        $n=0;
        foreach($userList as $k=>$v){
            //个人当月销量
            $selfMonthSales = $OrderModel->getSelfOrderSales($v['user_id'],$firstday,$lastday);
            //个人奖金
            $selfAward = $OrderModel->getSelfAward($selfMonthSales);
            $ures = $this->getTeamAward($v['user_id'],$selfMonthSales,$firstday,$lastday);
            $userList[$k]['selfMonthSales'] = $selfMonthSales;
            $userList[$k]['selfAward'] = $selfAward;
            $userList[$k]['teamMonthSale'] = $ures['teamMonthSale'];
            $userList[$k]['teamMonthAward'] = $ures['teamMonthAward'];
            $userList[$k]['allAward'] = $ures['teamMonthAward'] + $selfAward;

        }
        $order['0']['title']=$from.'佣金记录';
        $dir='./Public/download/commission/';
        $outputFileName = $dir."commission data".'.xlsx';
        @unlink ($outputFileName);
        require_once VENDOR_PATH.'phpExcel/PHPExcel.php';
        require_once VENDOR_PATH.'phpExcel/PHPExcel/Writer/Excel2007.php';
        require_once VENDOR_PATH.'phpExcel/PHPExcel/Cell/DataType.php';

        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objExcel);
        $objProps = $objExcel->getProperties ();
        $objProps->setCreator ( 'order');
        $objProps->setLastModifiedBy("order");
        $objProps->setDescription("佣金列表");
        $objProps->setTitle ($order['0']['title']);
        $objProps->setSubject($order['0']['title']);
        $objProps->setKeywords ($order['0']['title']);
        $objProps->setCategory ("佣金列表");

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(1);
        $objActSheet = $objExcel->getActiveSheet ();
        //表头
        $objActSheet->setCellValue ( 'A1', '代理名称');

        $objActSheet->setCellValue ( "B1", '联系电话');
        $objActSheet->setCellValue ( "C1", '团队业绩');
        $objActSheet->setCellValue ( "D1", '团队奖金');
        $objActSheet->setCellValue ( "E1", '个人业绩');
        $objActSheet->setCellValue ( "F1", '个人奖金');
        $objActSheet->setCellValue ( "G1", '结算佣金');
        $objActSheet->setCellValue ( "H1", '微信号');
        $objActSheet->setCellValue ( "I1", '支付宝账号');
        $objActSheet->setCellValue ( "J1", '结算月份');
        $n = 1;
        foreach ($userList as $k=>$v){

            $n = $n + 1;
            $objActSheet->setCellValue ( 'A'.$n, $v['name']);
            $objActSheet->setCellValue ( 'B'.$n, $v['user_no']);
            $objActSheet->setCellValue ( 'C'.$n, $v['teamMonthSale']);
            $objActSheet->setCellValue ( 'D'.$n, $v['teamMonthAward']);
            $objActSheet->setCellValue ( 'E'.$n, $v['selfMonthSales']);
            $objActSheet->setCellValue ( 'F'.$n, $v['selfAward']);
            $objActSheet->setCellValue ( 'G'.$n, $v['allAward']);
            $objActSheet->setCellValue ( 'H'.$n, $v['wxAccount']);
            $objActSheet->setCellValue ( 'I'.$n, $v['alipayAccount']);
            $objActSheet->setCellValue ( 'J'.$n, $from);
        }


        $objWriter->save($outputFileName);
        $downloadObject=new \Think\Download;
        $config=
            [
                'file'=>$outputFileName,
                'name'=>'佣金列表'.'.xlsx',
            ];

        $downloadObject->initialize($config)->start();
    }

    public function getTeamAward($categoryID,$selfMonthSales,$month_start,$month_end){
        $OrderModel = D('Order');
        //初始化ID数组
        $array[] = $categoryID;
        $teamMonthSale = $selfMonthSales;
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
                $array[] = $v['user_id'];
                $ids .= ',' . $v['user_id'];
                //求出该代理月销售
                $agenceMonthSales = $OrderModel->getSelfOrderSales($v['user_id'],$month_start,$month_end);
                $teamMonthSale = $teamMonthSale + $agenceMonthSales;
            }
            $ids = substr($ids, 1, strlen($ids));
            $categoryID = $ids;
        }
        while (!empty($cate));

        $teamMonthAward = $OrderModel->getSelfAward($teamMonthSale);

        $ids = implode(',', $array);
        $res['ids'] = $ids;
        $res['idsArr'] = $array;
        $res['teamMonthAward'] = $teamMonthAward;
        $res['teamMonthSale'] = $teamMonthSale;
        return $res;
    }

}