<?php

namespace app\admin\controller\data;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据管理
 *
 * @icon fa fa-circle-o
 */
class Realtime extends Backend
{
    
    /**
     * Data模型对象
     * @var \app\admin\model\Data
     */
    protected $model = null;

    protected $noNeedLogin = ['pressure', 'flow'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Data;

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        $companyId = $this->auth->company_id;
        if ($companyId == 0) {
            $companyList = Db::name("company")->where('status', "1")->where('delete_time', null)->field('id,name')->select();
        }else {
            $companyList = Db::name("company")->where('id', $companyId)->select();
        }
        $this->assign("companyList", $companyList);
        return $this->view->fetch();
    }

    public function pressure()
    {
        $floorId = $this->request->request('floor_id', 0);
        //压力设备
        $equipments = Db::name("equipment")
            ->where('instrument_type', 1)
            ->where('floor_id', $floorId)
            ->where('status', 1)
            ->column('equipment_id');
        $floor = Db::name("floor")->find($floorId);
        $company = Db::name("company")->find($floor['company_id']);
        $IPC_id = $company['IPC_id'];
        $list = [];
        if (!empty($equipments)) {
            foreach ($equipments as $equipment) {
                $data = Db::name("data")
                    ->where("equipment_id", $equipment)
                    ->where("IPC_id",$IPC_id)
                    ->where("create_time", ">", time()-5 )
                    ->order("create_time","desc")
                    ->find();
                $list[] = $data;
            }
        }
        $this->assign("list", $list);
        return $this->view->fetch();
    }

    public function flow()
    {
        $floorId = $this->request->request('floor_id', 0);
        //流量设备
        $equipments = Db::name("equipment")
            ->where('instrument_type', 2)
            ->where('floor_id', $floorId)
            ->where('status', 1)
            ->column('equipment_id');
        $floor = Db::name("floor")->find($floorId);
        $company = Db::name("company")->find($floor['company_id']);
        $IPC_id = $company['IPC_id'];
        $list = [];
        if (!empty($equipments)) {
            foreach ($equipments as $equipment) {
                $data = Db::name("data")->where("equipment_id", $equipment)
                    ->where("IPC_id",$IPC_id)
                    ->order("create_time","desc")
                    ->limit(10)
                    ->select();
                $list[] = $data;
            }
        }
        $this->assign("list", $list);
        return $this->view->fetch();
    }
}
