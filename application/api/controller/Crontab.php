<?php


namespace app\api\controller;


use app\common\controller\Api;

class Crontab extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    //删除无用数据
    public function deleteUnusedData()
    {
        $expireTime = strtotime(date("Y-m-d", time())) - (6 * 30 * 24 * 60 * 60);
        model("Data")
            ->where("create_time", "<", $expireTime)
            ->delete();
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
            $expireTime = strtotime(date("Y-m-d", time())) - ($data_storage_time * 24 * 60 * 60);
            $this->deleteData($expireTime, $IPC_id);
        }
        $this->success("成功");
    }

    private function deleteData($expireTime, $IPC_id)
    {
        model("Data")->where("IPC_id", $IPC_id)
            ->where("create_time", "<", $expireTime)
            ->delete();
    }
}