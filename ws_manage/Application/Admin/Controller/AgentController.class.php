<?php
namespace Admin\Controller;
use Think\Controller;
class AgentController extends PublicController {
    function _initialize(){
        $this->checkSessionStatus();
    }
    public function daili(){
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
    public function shenhe(){
        $userModel = D('User');
        $p = I('get.p')?I('get.p'):'1';
        $this->assign('pas',$p);
        $this->display();
    }

    public function search() {
        $userModel = D('User');
        $p = I('get.p')?I('get.p'):'1';
        $status = $_GET['status']?$_GET['status']:1;
        $agentList = $userModel->getFailedAgentList($p,$status);
        $this->assign('agentList',$agentList);

        //获取总数量
        $numres = $userModel->getFailedAgentNum($status);
        $num = $numres['0']['num'];
        //分页
        $Page      = new \Think\Page($num,C('AGENT_PAGE'));
        $Page -> setConfig('header','共%TOTAL_ROW%条');
        $Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('pas',$p);
        $this->assign('div','agent');
        $this->assign('status_type',$status);
        echo $this->fetch('ajax');
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

    //审核通过或者不通过
    public function changeAgentStatus(){
        $id = $_POST['id'];
        $type = $_POST['type'];
        $where['id'] = $id;
        if ($type == 1){
            $data['is_check'] = 1;
            $data['check_time'] = date('Y-m-d H:i:s',time());
        }else{
            $data['status'] = -1;
        }

        $res = M('User')->where($where)->save($data);
        if ($res === false){
            $result['status'] = 0;
            $result['msg'] = '操作失败';
            $this->ajaxReturn($result);
        }

        $result['status'] = 1;
        $result['msg'] = '操作成功';
        $this->ajaxReturn($result);
    }

    public function editOp(){
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $user_no = $_POST['user_no'];
        $password = trim($_POST['password']);
        $user_id = $_POST['user_id'];
        $alipayAccount = $_POST['alipayaccount'];
        $wxAccount = $_POST['wxaccount'];

        //获取用户原有信息
        $userInfo = M('user')->where(array('user_id'=>$user_id))->find();
        //如果用户账号和原来的一样，则不用检查账号是否已存在，若不一样，需要检查
        if ($userInfo['user_no'] != $user_no){
            //检查手机号是否已经注册
            $where1['user_no'] = $user_no;
            $where1['status'] = 1;
            $phoneres = M('User')->where($where1)->find();
            if ($phoneres){
                $result['msg'] = '该手机号已存在';
                $result['status'] = 0;
                $this->ajaxReturn($result);
            }
        }

        if (!empty($password)){
            $userData['password'] = md5($password);
        }

        //开启事务
        M('User')->startTrans();
        //向用户表新增数据
        $userWhere['user_id'] = $user_id;
        $userData['user_no'] = $user_no;
        $userData['name'] = $name;
        $userData['phone'] = $phone;
        $userData['alipayAccount'] = $alipayAccount;
        $userData['wxAccount'] = $wxAccount;
        $res = M('User')->where($userWhere)->save($userData);
        if ($res === false){
            M('User')->rollback();
            $result['msg'] = '编辑失败';
            $result['status'] = 0;
            $this->ajaxReturn($result);
        }

        M('User')->commit();
        $result['msg'] = '编辑成功';
        $result['status'] = 1;
        $this->ajaxReturn($result);
    }
}