<?php


namespace app\api\controller;


use app\common\controller\Api;
use think\Db;
use think\Exception;

class Crontab extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    //删除无用数据
    public function deleteUnusedData()
    {
        //删除一分钟之前没有报警的数据
        $beforeOneMinute = time() - 60;
        $dataModel = new \app\admin\model\Data();
        $dataLists = $dataModel->where('create_time', '<', $beforeOneMinute)->select();
        $eqLists = model("Equipment")->field("id, equipment_id, instrument_type, HIAL, LoAL, company_id")
            ->where("status", 1)
            ->with([
                'company' => function($query){
                    $query->field("id, IPC_id");
                }
            ])->select();
        //所有数据id
        $dataIds = array_column($dataLists, "id");
        //报警的数据的id
        $ids = [];
        foreach ($dataLists as $dataList) {
            //找出压力数据 value字段 数组只有一个值的
            $value = json_decode($dataList['value'], true);
            if (count($value) != 1) {
                continue;
            }
            foreach ($eqLists as $eqList) {
                if ($dataList['equipment_id'] == $eqList['equipment_id'] &&
                $dataList['IPC_id'] == $eqList['company']['IPC_id']) {
                    if ($value < $eqList['LoAL'] || $value > $eqList['HIAL']) {
                        $ids[] = $dataList['id'];
                    }
                }
            }
        }
        //所有数据id 和报警数据id 的差集
        $delIds = array_diff($dataIds, $ids);
        $res = $dataModel->where("id", "in", $delIds)->delete();
        $this->success("删除成功", $res);
    }

    //定时删除时间范围外的数据
    public function deleteExpireData()
    {
        //查询所有单位
        $companyList = model("Company")->select();

        foreach ($companyList as $item) {
            //查询要删除的数据
            $IPC_id = $item['IPC_id'];
            $data_storage_time = $item['data_storage_time'];
            $this->deleteData($data_storage_time, $IPC_id);
        }
        $this->success("删除成功");
    }

    private function deleteData($data_storage_time, $IPC_id)
    {
        //查天数+1天的表
        $day = $data_storage_time + 1;
        $date = date("Ymd", strtotime(time(),"-$day day"));
        $table = "data_".$date;
        try {
            Db::name($table)->where("IPC_id", $IPC_id)->delete();
        }catch (Exception $exception) {
            return "无可删除数据";
        }

    }

    /**
     * 添加data新表
     */
    public function addDataTable()
    {
        $today = date("Ymd", time());
        $sql = "CREATE TABLE m_data_".$today." LIKE m_data;";
        Db::query($sql);
    }
}