<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends PublicController {
    public function index(){
        $this->checkSessionStatus();
        $user_id = $_SESSION['user_id'];

        $OrderModel = D('Order');
        //本月起始时间
        $month_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
        //今日时间
        $day_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d"),date("Y")));

        //个人当月销量
        $selfMonthSales = $OrderModel->getSelfOrderSales($user_id,$month_start);
        $this->assign('selfMonthSales',$selfMonthSales);

        //个人当日销量
        $selfTodaySales = $OrderModel->getSelfOrderSales($user_id,$day_start);

        //累计销量
        $selfAllSales = $OrderModel->getSelfOrderSales($user_id);
        $this->assign('selfAllSales',$selfAllSales);


        $res = $this->getAllChildIds($user_id);
        //团队日销
        $teamTodaySales = $res['teamTodaySales'] + $selfTodaySales;
        $this->assign('teamTodaySales',$teamTodaySales);

        //团队月销
        $teamMonthSales = $res['teamMoneySales'] + $selfMonthSales;
        $this->assign('teamMonthSales',$teamMonthSales);

        $res = $this->getTeamAward($user_id,$selfMonthSales);
        //团队奖金
        $teamMoneyAward = $res['teamMoneyAward'];
        //个人奖金
        $selfAward = $OrderModel->getSelfAward($selfMonthSales);
        //佣金
        $totalAward = $teamMoneyAward + $selfAward;
        $this->assign('teamMoneyAward',$teamMoneyAward);
        $this->assign('selfAward',$selfAward);
        $this->assign('totalAward',$totalAward);

        //获取banner
        $bannerList = M('advert')->where(array('status'=>1))->select();
        foreach ($bannerList as $k=>$v){
            $bannerList[$k]['image_url'] = '/Public/File/'.$v['image_url'];
        }
        $this->assign('bannerList',$bannerList);

        $this->display();
    }

    //忘记密码
    public function lostPwd(){
        $this->display();
    }

    //公告
    public function notifyList(){
        $this->display();
    }

    //登录
    public function login(){
        $this->display();
    }
    //退出
    public function tuichu()
    {
        session(null);
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //验证登录操作
    public function loginOp(){
        $username = I('post.username');
        $password = I('post.password');

        $map['user_no'] = $username;
        $map['password'] = md5($password);
        $map['status']  = array('gt',0);
        $map['is_freeze']=0;
        $map['is_check']=1;
        $userRes = M('User')->where($map)->find();
        if ($userRes){
            $sid = session_id();
            //记录登录信息
            $data['session_id'] = $sid;
            $data['user_id'] = $userRes['user_id'];
            $data['user_no'] = $userRes['user_no'];
            $data['time'] = time();
            $data['login_time'] = date('Y-m-d H:i:s');
            $data['server_ip'] = get_client_ip();
            $addres = M('user_login_info')->add($data);
            if (!$addres){
                $result['status'] = 0;
                $result['msg'] = '添加登录信息失败';
                $this->ajaxReturn($result);
            }

            session('sid',$sid);
            session('user_no',$userRes['user_no']);
            session('user_id',$userRes['user_id']);
            session('username',$userRes['name']);

            $result['status'] = 1;
            $result['msg'] = '登录成功';
            $this->ajaxReturn($result);
        }else{
            $result['status'] = 0;
            $result['msg'] = '登录失败';
            $this->ajaxReturn($result);
        }

    }

    public function ajaxAward(){
        $user_id = $_SESSION['user_id'];
        $OrderModel = D('Order');
        //本月起始时间
        $month_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
        //个人当月销量
        $selfMonthSales = $OrderModel->getSelfOrderSales($user_id,$month_start);
        $res = $this->getTeamAward($user_id,$selfMonthSales);
        //团队奖金
        $teamMoneyAward = $res['teamMoneyAward'];
        //个人奖金
        $selfAward = $OrderModel->getSelfAward($selfMonthSales);
        //佣金
        $totalAward = $teamMoneyAward + $selfAward;
        $result['teamMoneyAward'] = $teamMoneyAward;
        $result['selfAward'] = $selfAward;
        $result['totalAward'] = $totalAward;
        $result['status'] = 1;
        $this->ajaxReturn($result);

    }

    public function getAllChildIds($categoryID){
        //本月起始时间
        $month_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
        //今日时间
        $day_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d"),date("Y")));

        $OrderModel = D('Order');
        //初始化ID数组
        $array[] = $categoryID;
        $teamMoneySales = 0.00;
        $teamTodaySales = 0.00;
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
                $agenceMonthSales = $OrderModel->getSelfOrderSales($v['user_id'],$month_start);
                $teamMoneySales = $teamMoneySales + $agenceMonthSales;
                //日销售
                $agenceTodaySales = $OrderModel->getSelfOrderSales($v['user_id'],$day_start);
                $teamTodaySales = $teamTodaySales + $agenceTodaySales;
            }
            $ids = substr($ids, 1, strlen($ids));
            $categoryID = $ids;
        }
        while (!empty($cate));
        $ids = implode(',', $array);
        $res['ids'] = $ids;
        $res['idsArr'] = $array;
        $res['teamMoneySales'] = $teamMoneySales;
        $res['teamTodaySales'] = $teamTodaySales;
        return $res;    //  返回字符串
        //return $array //返回数组
    }

    public function getTeamAward($categoryID,$selfMonthSales){
        //本月起始时间
        $month_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));

        $OrderModel = D('Order');
        //初始化ID数组
        $array[] = $categoryID;
        $teamMoneyAward = 0.00;
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
                $agenceMonthSales = $OrderModel->getSelfOrderSales($v['user_id'],$month_start);
                $teamMonthSale = $teamMonthSale + $agenceMonthSales;
            }
            $ids = substr($ids, 1, strlen($ids));
            $categoryID = $ids;
        }
        while (!empty($cate));

        $teamMoneyAward = $OrderModel->getSelfAward($teamMonthSale);

        $ids = implode(',', $array);
        $res['ids'] = $ids;
        $res['idsArr'] = $array;
        $res['teamMoneyAward'] = $teamMoneyAward;
        return $res;    //  返回字符串
        //return $array //返回数组
    }


}