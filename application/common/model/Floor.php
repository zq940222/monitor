<?php

namespace app\common\model;

use think\Model;


class Floor extends Model
{

    // 表名
    protected $name = 'floor';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

}
