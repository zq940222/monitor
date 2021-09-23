<?php

namespace app\admin\model;

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

    // 追加属性
    protected $append = [
        'instrument_type_text',
        'effective_range_text',
        'unit_text',
        'status_text',
        'create_time_text',
        'update_time_text',
        'delete_time_text'
    ];
    

    
    public function getInstrumentTypeList()
    {
        return ['1' => __('Instrument_type 1'), '2' => __('Instrument_type 2')];
    }

    public function getEffectiveRangeList()
    {
        return ['0' =>"无",'1' => __('Effective_range 1'), '2' => __('Effective_range 2'), '3' => __('Effective_range 3')];
    }

    public function getUnitList()
    {
        return ['1' => __('Unit 1'), '2' => __('Unit 2'), '3' => __('Unit 3')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '-1' => __('Status -1')];
    }


    public function getInstrumentTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['instrument_type']) ? $data['instrument_type'] : '');
        $list = $this->getInstrumentTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getEffectiveRangeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['effective_range']) ? $data['effective_range'] : '');
        $list = $this->getEffectiveRangeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getUnitTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['unit']) ? $data['unit'] : '');
        $list = $this->getUnitList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getDeleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delete_time']) ? $data['delete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setDeleteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

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
