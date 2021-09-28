<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Validate;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login', 'checkError'];
    protected $noNeedRight = ['index', 'logout', 'checkError'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('fixedmenu', $fixedmenu);
        $this->view->assign('referermenu', $referermenu);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }

    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        if ($this->auth->isLogin()) {
            $this->success(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'require|token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];
            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }
            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = $background ? (stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background) : '';
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $this->auth->logout();
        Hook::listen("admin_logout_after", $this->request);
        $this->success(__('Logout successful'), 'index/login');
    }

    //检查报警
    public function checkError()
    {
        $auth = $this->auth->getUserInfo();
        $companyId = $auth['company_id'];
        if (empty($companyId)) {
            $this->error("没有对应单位");
        }
        //查询该公司所有压力设备
        $company = model("Company")->find($companyId);
        $equipments = model("Equipment")
            ->field("equipment_id,HIAL,LoAL,company_id,building_id,floor_id,monitor_object")
            ->relation(["company", "building", "floor"])
            ->where("company_id", $companyId)
            ->where("instrument_type", 1)
            ->where("status", 1)->select();
        $equipmentIdList = array_column($equipments, "equipment_id");
        //检查所有设备5秒内数据有没有报警
        $errorMsgList = [];
        $dataLists = model("data")
            ->where("equipment_id", "in", $equipmentIdList)
            ->where("IPC_id","=", $company['IPC_id'])
            ->where("create_time", ">", time()-12 )
            ->select();
        foreach ($equipments as $equipment) {
            if (!in_array($equipment['equipment_id'], array_column($dataLists, "equipment_id"))) {
                $errorMsg = "楼:".$equipment['building']['name']."层:".$equipment['floor']['name']."检测对象:".$equipment['monitor_object']. " 0";
                $errorMsgList[] =  $errorMsg;
            }else {
                foreach ($dataLists as $dataList) {
                    if ($equipment['equipment_id'] == $dataList['equipment_id']){
                        //检查数据是否在报警值外
                        if ($dataList['value'] > $equipment['HIAL']) {
                            //大于上限
                            $errorMsg = "楼:".$equipment['building']['name']."层:".$equipment['floor']['name']."检测对象:".$equipment['monitor_object']." ".$dataList['value'];
                            $errorMsgList[] =  $errorMsg;
                        }
                        if ($dataList['value'] < $equipment['LoAL']) {
                            //小于下限
                            $errorMsg = "楼:".$equipment['building']['name']."层:".$equipment['floor']['name']."检测对象:".$equipment['monitor_object']." ".$dataList['value'];
                            $errorMsgList[] = $errorMsg;
                        }
                    }
                }
            }

        }
        if (empty($errorMsgList)){
            $this->error("没有报警数据!");
        }else{
            $this->success("报警", "",$errorMsgList);
        }

    }
}
