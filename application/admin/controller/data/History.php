<?php

namespace app\admin\controller\data;

use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 数据管理
 *
 * @icon fa fa-circle-o
 */
class History extends Backend
{
    
    /**
     * Data模型对象
     * @var \app\admin\model\Data
     */
    protected $model = null;

    protected $searchFields = 'equipment_id';

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
        $auth = $this->auth->getUserInfo();
        $companyId = $auth['company_id'];
        $IPC_id = '';
        if (!empty($companyId)) {
            $company = model("Company")->find($companyId);
            $IPC_id = $company['IPC_id'];
        }
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $filter = $this->request->request("filter");
            $search = $this->request->request("search");
            $day = "";
            $filter = json_decode($filter, true);
            if (!empty($filter) && isset($filter['day'])) {
                $day = $filter['day'];
            }
            $table = "data_".date("Ymd", time());
            if (!empty($day)) {
                $day = date("Ymd", strtotime($day));
                $table = "data_".$day;
            }
            try{
                if (empty($IPC_id)) {
                    $list = Db::name($table)
                        ->where("equipment_id", "like", "%$search%")
                        ->order($sort, $order)
                        ->paginate($limit);
                }else{
                    $list = Db::name($table)
                        ->where("equipment_id", "like", "%$search%")
                        ->where("IPC_id" ,$IPC_id)
                        ->order($sort, $order)
                        ->paginate($limit);
                }
            }catch (Exception $exception) {
                $result = array("total" => 0, "rows" => []);
                return json($result);
            }

            //处理数据
            //压力值不变，流量值只保留总量
            foreach ($list->items() as &$item) {
                if (is_array(json_decode($item['value'],true))) {
                    $item['value'] = json_decode($item['value'],true);
                }
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
}
