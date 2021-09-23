<?php


namespace app\api\controller;


use app\admin\model\Floor;
use app\common\controller\Api;

class Data extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
    'code':'1',
    'msg':'返回成功'
    })
     */
    public function test()
    {
        $this->success('返回成功', $this->request->param());
    }

    /**
     * 上传数据
     *
     * @ApiTitle    (上传数据)
     * @ApiSummary  (工控机上报数据)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/data/upload)
     * @ApiParams   (name="data", type="object", sample="{'equipment_id':'int','value':'int'}", description="设备编号和测量数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="成功")
     * @ApiReturn   ({
    'code':'1',
    'msg':'成功'
    })
     */
    public function upload()
    {
        $params = $this->request->post('data/a',[]);
        $IPC_id = $this->request->post('IPC_id',"");
        if (empty($IPC_id)) {
            $this->error("IPC_id不能为空");
        }
        if (is_array($params)) {
            foreach ($params as &$param) {
                $param['IPC_id'] = $IPC_id;
                $param['value'] = json_encode($param['value']);
            }
            $model = new \app\admin\model\Data();
            $ret = $model->saveAll($params);
            if ($ret) {
                $this->success('成功');
            }else {
                $this->error('失败');
            }

        } else {
            $this->error('数据有误！');
        }

        $this->error('失败');
    }

    //通过层id获取数据
    public function getDataByFloorId()
    {
        $floorId = $this->request->request('floor_id', 0);
        //获取该楼层的单位信息和设备信息
        $floor = Floor::where("status", 1)
            ->field("id, company_id")->with([
                "company" => function($query){
                    $query->field("id, IPC_id");
                },
                "equipments" => function($query){
                    $query->field("id, equipment_id, instrument_type, monitor_object, floor_id, effective_range")
                        ->where("status", 1);
                }
            ])->find($floorId);
        //所有设备列表
        $equipmentLists = $floor->equipments;
        //所有设备编号
        $equipmentIds = array_column($equipmentLists, "equipment_id");
        //获取该楼层数据
        //30秒前时间戳
        $beforeTime = time() - 30;
        $data = model("data")->where("IPC_id", $floor->company->IPC_id)->where("equipment_id", "in", $equipmentIds)
            ->where("create_time", ">", $beforeTime)->order("create_time","asc")->select();
        //所有压力设备
        $pressureLists = [];
        //所有流量设备
        $flowLists = [];
        foreach ($equipmentLists as $equipmentList) {
            if ($equipmentList['instrument_type'] == 1) {
                $pressureLists[] = $equipmentList;
            }
            if ($equipmentList['instrument_type'] == 2) {
                $flowLists[] = $equipmentList;
            }
        }
        //重新组合数据
        //压力数据
        foreach ($pressureLists as &$pressureList) {
            if ($pressureList['effective_range'] == 1) {
                $pressureList['max'] = 25;
                $pressureList['min'] = 0;
            }
            if ($pressureList['effective_range'] == 2) {
                $pressureList['max'] = 1.6;
                $pressureList['min'] = 0;
            }
            if ($pressureList['effective_range'] == 3){
                $pressureList['max'] = 0;
                $pressureList['min'] = -0.1;
            }
            $value = 0;
            foreach ($data as $v){
                if ($v['equipment_id'] == $pressureList['equipment_id']
                    && $v['create_time'] > (time() - 5)) {
                    $value = json_decode($v['value'], true)[0];;
                }
            }
            $pressureList['value'] = $value;
        }
        //流量数据
        foreach ($flowLists as $flowList) {
            $flowList['time'] = [
                date("H:i:s", (time() - 25)),
                date("H:i:s", (time() - 20)),
                date("H:i:s", (time() - 15)),
                date("H:i:s", (time() - 10)),
                date("H:i:s", (time() - 5)),
                date("H:i:s", time()),
            ];

            $flowList['total'] = 0;
            $valueList = ["0","0","0","0","0","0"];
            foreach ($data as $v){
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 30) && $v['create_time'] < (time() - 25)) {
                    $valueList[0] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 25) && $v['create_time'] < (time() - 20)) {
                    $valueList[1] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 20) && $v['create_time'] < (time() - 15)) {
                    $valueList[2] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 15) && $v['create_time'] < (time() - 10)) {
                    $valueList[3] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 10) && $v['create_time'] < (time() - 5)) {
                    $valueList[4] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 5) && $v['create_time'] < (time())) {
                    $valueList[5] = json_decode($v['value'], true)[0];
                    $flowList['total'] = json_decode($v['value'], true)[1];
                }
            }
            $flowList['value'] = $valueList;
        }
        $list['pressure'] = $pressureLists;
        $list['flow'] = $flowLists;
        $this->success('', '', $list);
    }
}