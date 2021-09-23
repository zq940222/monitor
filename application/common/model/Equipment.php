<?php

namespace app\common\model;

use think\Model;


class Equipment extends Model
{

    // 表名
    protected $name = 'equipment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = "create_time";
    protected $updateTime = "update_time";
    protected $deleteTime = "delete_time";


    public function company()
    {
        return $this->belongsTo("Company", "company_id", "id");
    }

    public function building()
    {
        return $this->belongsTo("Building", "building_id", "id");
    }

    public function floor()
    {
        return $this->belongsTo("Floor", "floor_id", "id");
    }
}
