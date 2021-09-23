<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    protected $noNeedRight = ['checkError'];

    /**
     * 查看
     */
    public function index()
    {
        $auth = $this->auth->getUserInfo();
        $companyId = $auth['company_id'];
        if (!empty($companyId)) {
            $company = model("company")->find($companyId);
        }else{
            $company = ["name"=>""];
        }
        $this->assign(compact("company"));
        return $this->view->fetch();
    }


}
