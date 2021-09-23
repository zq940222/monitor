<?php

namespace app\admin\controller\equipment;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 设备管理
 *
 * @icon fa fa-circle-o
 */
class Equipment extends Backend
{
    
    /**
     * Equipment模型对象
     * @var \app\admin\model\Equipment
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Equipment;
        $this->view->assign("instrumentTypeList", $this->model->getInstrumentTypeList());
        $this->view->assign("effectiveRangeList", $this->model->getEffectiveRangeList());
        $this->view->assign("unitList", $this->model->getUnitList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
    public function index($ids=null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->relation(['company', 'building', 'floor'])
                ->where($where)
                ->where("floor_id", $ids)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $floor = model("Floor")->relation(["company","building"])->find($ids);
        $this->assign(compact('floor'));
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add($ids=null)
    {

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            //验证设备id是否唯一
            $res = model('Equipment')
                ->where("company_id", $params['company_id'])
                ->where("equipment_id", $params['equipment_id'])->count();
            if ($res > 0) {
                $this->error("设备id已存在");
            }
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $floor = model("Floor")->relation(["company","building"])->find($ids);
        $this->assign(compact('floor'));
        return $this->view->fetch();
    }

}
