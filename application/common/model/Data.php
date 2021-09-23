<?php

namespace app\common\model;

use think\Model;


class Data extends Model
{

    // 表名
    protected $name = 'data';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = "int";

    // 定义时间戳字段名
    protected $createTime = "create_time";
    protected $updateTime = false;
    protected $deleteTime = false;

}
