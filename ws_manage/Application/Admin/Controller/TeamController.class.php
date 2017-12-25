<?php
namespace Admin\Controller;
use Think\Controller;
class TeamController extends PublicController {
    function _initialize(){
        $this->checkSessionStatus();
    }
    //代理列表
    public function agencyList(){
        $userModel = D('User');
        $p = I('get.p')?I('get.p'):'1';
        $user_id = $_GET['uid'];
        $agentList = $userModel->getPassAgentList($p,$user_id);
        $this->assign('agentList',$agentList);

        //获取总数量
        $numres = $userModel->getPassAgentNum($user_id);
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('user_id',$user_id);
        $this->display();
    }

    //冻结代理
    public function freezeAgent(){
        $user_id = $_POST['user_id'];
        $where['user_id'] = $user_id;
        $data['is_freeze'] = 1;
        $res = M('User')->where($where)->save($data);
        if ($res == 0){
            $result['msg'] = '该代理已经被冻结';
        }elseif($res == 1){
            $result['msg'] = '该代理冻结成功';
        }
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //解冻代理
    public function unfreezeAgent(){
        $user_id = $_POST['user_id'];
        $where['user_id'] = $user_id;
        $data['is_freeze'] = 0;
        $res = M('User')->where($where)->save($data);
        if ($res == 0){
            $result['msg'] = '该代理已经被解冻';
        }elseif($res == 1){
            $result['msg'] = '该代理解冻成功';
        }
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }

    //佣金结算
    public function commissionSettlement(){
        $this->display();
    }
}