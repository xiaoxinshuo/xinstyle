<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends PublicController {

    function _initialize(){
        //var_dump(111);
    }

    public function index(){
        $this->checkSessionStatus();
        $user_id = $_SESSION['user_id'];
        $userModel = D('User');
        $orderModel = D('Order');
        //本周时间和本月时间
        $week_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
        $month_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
        //代理总人数
        $tmpUserCount = $userModel -> getUserCount();
        $totalUserCount = $tmpUserCount['0']['num'];
        //本周代理人数
        $tmpWeekUserCount = $userModel -> getUserCount($week_start);
        $weekUserCount = $tmpWeekUserCount['0']['num'];
        //本月代理人数
        $tmpMonthUserCount = $userModel -> getUserCount($month_start);
        $monthUserCount = $tmpMonthUserCount['0']['num'];

        //本周和本月订单金额
        $tmpWeekMoney = $orderModel -> getOrderMoney($week_start);
        $weekMoney = $tmpWeekMoney['0']['money'];
        $tmpMonthMoney = $orderModel -> getOrderMoney($month_start);
        $monthMoney = $tmpMonthMoney['0']['money'];
        if (!$weekMoney){
            $weekMoney = 0;
        }
        if (!$monthMoney){
            $monthMoney = 0;
        }

        $this->assign('totalUserCount',$totalUserCount);
        $this->assign('weekUserCount',$weekUserCount);
        $this->assign('monthUserCount',$monthUserCount);
        $this->assign('weekMoney',$weekMoney);
        $this->assign('monthMoney',$monthMoney);
        $this->display();
    }


    //登录
    public function login(){
        $this->display();
    }

    //验证登录操作
    public function loginOp(){
        $user_no = I('post.userno');
        $password = I('post.password');

        $map['user_no'] = $user_no;
        $map['password'] = md5($password);
        $map['status']  = array('gt',0);
        $map['utype']=1;
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

    public function tuichu()
    {
        session(null);
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    public function changepwd(){
        $this->checkSessionStatus();
        $this->display();
    }

    public function changePwdOp(){
        $name = I('post.name');
        $password = I('post.pwd');
        $password = trim($password);
        $map['name'] = $name;
        $map['password'] = md5($password);
        $where['user_id'] = $_SESSION['user_id'];
        $res = M('user')->where($where)->save($map);
        if (!$res){
            $result['status'] = 0;
            $result['msg'] = '编辑失败';
            $this->ajaxReturn($result);
        }


        session('username',$name);

        $result['status'] = 1;
        $result['msg'] = '编辑成功';
        $this->ajaxReturn($result);
    }
}