<?php

namespace app\admin\controller;

use app\admin\model\Data;
use app\admin\model\Floor;
use app\common\controller\Backend;
use app\common\exception\UploadException;
use app\common\library\Upload;
use think\addons\Service;
use think\Cache;
use think\Config;
use think\Db;
use think\Lang;
use think\Validate;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Backend
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['lang'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();

        //设置过滤方法
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);
    }

    /**
     * 加载语言包
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        header("Cache-Control: public");
        header("Pragma: cache");

        $offset = 30 * 60 * 60 * 24; // 缓存一个月
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");

        $controllername = input("controllername");
        //默认只加载了控制器对应的语言名，你还根据控制器名来加载额外的语言包
        $this->loadlang($controllername);
        return jsonp(Lang::get(), 200, [], ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        //必须设定cdnurl为空,否则cdnurl函数计算错误
        Config::set('upload.cdnurl', '');
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), '', ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), '', ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }
    }

    /**
     * 通用排序
     */
    public function weigh()
    {
        //排序的数组
        $ids = $this->request->post("ids");
        //拖动的记录ID
        $changeid = $this->request->post("changeid");
        //操作字段
        $field = $this->request->post("field");
        //操作的数据表
        $table = $this->request->post("table");
        if (!Validate::is($table, "alphaDash")) {
            $this->error();
        }
        //主键
        $pk = $this->request->post("pk");
        //排序的方式
        $orderway = strtolower($this->request->post("orderway", ""));
        $orderway = $orderway == 'asc' ? 'ASC' : 'DESC';
        $sour = $weighdata = [];
        $ids = explode(',', $ids);
        $prikey = $pk && preg_match("/^[a-z0-9\-_]+$/i", $pk) ? $pk : (Db::name($table)->getPk() ?: 'id');
        $pid = $this->request->post("pid", "");
        //限制更新的字段
        $field = in_array($field, ['weigh']) ? $field : 'weigh';

        // 如果设定了pid的值,此时只匹配满足条件的ID,其它忽略
        if ($pid !== '') {
            $hasids = [];
            $list = Db::name($table)->where($prikey, 'in', $ids)->where('pid', 'in', $pid)->field("{$prikey},pid")->select();
            foreach ($list as $k => $v) {
                $hasids[] = $v[$prikey];
            }
            $ids = array_values(array_intersect($ids, $hasids));
        }

        $list = Db::name($table)->field("$prikey,$field")->where($prikey, 'in', $ids)->order($field, $orderway)->select();
        foreach ($list as $k => $v) {
            $sour[] = $v[$prikey];
            $weighdata[$v[$prikey]] = $v[$field];
        }
        $position = array_search($changeid, $ids);
        $desc_id = $sour[$position];    //移动到目标的ID值,取出所处改变前位置的值
        $sour_id = $changeid;
        $weighids = array();
        $temp = array_values(array_diff_assoc($ids, $sour));
        foreach ($temp as $m => $n) {
            if ($n == $sour_id) {
                $offset = $desc_id;
            } else {
                if ($sour_id == $temp[0]) {
                    $offset = isset($temp[$m + 1]) ? $temp[$m + 1] : $sour_id;
                } else {
                    $offset = isset($temp[$m - 1]) ? $temp[$m - 1] : $sour_id;
                }
            }
            if (!isset($weighdata[$offset])) {
                continue;
            }
            $weighids[$n] = $weighdata[$offset];
            Db::name($table)->where($prikey, $n)->update([$field => $weighdata[$offset]]);
        }
        $this->success();
    }

    /**
     * 清空系统缓存
     */
    public function wipecache()
    {
        try {
            $type = $this->request->request("type");
            switch ($type) {
                case 'all':
                    // no break
                case 'content':
                    //内容缓存
                    rmdirs(CACHE_PATH, false);
                    Cache::clear();
                    if ($type == 'content') {
                        break;
                    }
                case 'template':
                    // 模板缓存
                    rmdirs(TEMP_PATH, false);
                    if ($type == 'template') {
                        break;
                    }
                case 'addons':
                    // 插件缓存
                    Service::refresh();
                    if ($type == 'addons') {
                        break;
                    }
                case 'browser':
                    // 浏览器缓存
                    // 只有生产环境下才修改
                    if (!config('app_debug')) {
                        $version = config('site.version');
                        $newversion = preg_replace_callback("/(.*)\.([0-9]+)\$/", function ($match) {
                            return $match[1] . '.' . ($match[2] + 1);
                        }, $version);
                        if ($newversion && $newversion != $version) {
                            Db::startTrans();
                            try {
                                \app\common\model\Config::where('name', 'version')->update(['value' => $newversion]);
                                \app\common\model\Config::refreshFile();
                                Db::commit();
                            } catch (\Exception $e) {
                                Db::rollback();
                                exception($e->getMessage());
                            }
                        }
                    }
                    if ($type == 'browser') {
                        break;
                    }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        \think\Hook::listen("wipecache_after");
        $this->success();
    }

    /**
     * 读取分类数据,联动列表
     */
    public function category()
    {
        $type = $this->request->get('type', '');
        $pid = $this->request->get('pid', '');
        $where = ['status' => 'normal'];
        $categorylist = null;
        if ($pid || $pid === '0') {
            $where['pid'] = $pid;
        }
        if ($type) {
            $where['type'] = $type;
        }

        $categorylist = Db::name('category')->where($where)->field('id as value,name')->order('weigh desc,id desc')->select();

        $this->success('', '', $categorylist);
    }

    /**
     * 读取省市区数据,联动列表
     */
    public function area()
    {
        $params = $this->request->get("row/a");
        if (!empty($params)) {
            $province = isset($params['province']) ? $params['province'] : '';
            $city = isset($params['city']) ? $params['city'] : '';
        } else {
            $province = $this->request->get('province', '');
            $city = $this->request->get('city', '');
        }
        $where = ['pid' => 0, 'level' => 1];
        $provincelist = null;
        if ($province !== '') {
            $where['pid'] = $province;
            $where['level'] = 2;
            if ($city !== '') {
                $where['pid'] = $city;
                $where['level'] = 3;
            }
        }
        $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();
        $this->success('', '', $provincelist);
    }

    /**
     * 生成后缀图标
     */
    public function icon()
    {
        $suffix = $this->request->request("suffix");
        header('Content-type: image/svg+xml');
        $suffix = $suffix ? $suffix : "FILE";
        echo build_suffix_image($suffix);
        exit;
    }

    public function company()
    {
        $list = Db::name("company")->where('status', "1")->where('delete_time', null)->field('id as value,name')->select();
        $this->success('', '', $list);
    }

    public function building()
    {
        $params = $this->request->get("row/a");
        if (!empty($params)) {
            $company = $params['company_id'] ?? '';
        } else {
            $company = $this->request->get('company_id', '');
        }
        $where = ['status' => "1", 'delete_time' => null];
        $where['company_id'] = $company;
        $list = Db::name("building")->where($where)->field('id as value,name')->select();
        $this->success('', '', $list);
    }

    public function floor()
    {
        $params = $this->request->get("row/a");
        if (!empty($params)) {
            $building_id = $params['building_id'] ?? '';
        } else {
            $building_id = $this->request->get('building_id', '');
        }
        $where = ['status' => "1", 'delete_time' => null];
        $where['building_id'] = $building_id;
        $list = Db::name("floor")->where($where)->field('id as value,name')->select();
        $this->success('', '', $list);
    }

    //通过单位id获取楼号
    public function getBuildingByCompanyId()
    {
        $companyId =  $this->request->request('company_id', 0);
        $list = Db::name("building")->where('company_id', $companyId)->field('id,name')->select();
        $this->success('', '', $list);
    }

    //通过楼id获取层号
    public function getFloorByBuildingId()
    {
        $buildingId =  $this->request->request('building_id', 0);
        $list = Db::name("floor")->where('building_id', $buildingId)->field('id,name')->select();
        $this->success('', '', $list);
    }

    public function getBuildingAndFloorByCompanyId()
    {
        $companyId =  $this->request->request('company_id', 0);
        $list = model("building")->where('company_id', $companyId)
            ->where("status", 1)
            ->field('id,name')
            ->with([
                'floor' => function($query){
                    $query->where("status",1)
                        ->field("id, name, building_id");
                }
            ])
            ->select();
        $this->success("", "", $list);
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
        $data = Data::where("IPC_id", $floor->company->IPC_id)->where("equipment_id", "in", $equipmentIds)
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
                && $v['create_time'] > (time() - 30) && $v['create_time'] <= (time() - 25)) {
                    $valueList[0] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 25) && $v['create_time'] <= (time() - 20)) {
                    $valueList[1] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 20) && $v['create_time'] <= (time() - 15)) {
                    $valueList[2] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 15) && $v['create_time'] <= (time() - 10)) {
                    $valueList[3] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 10) && $v['create_time'] <= (time() - 5)) {
                    $valueList[4] = json_decode($v['value'], true)[0];
                }
                if ($v['equipment_id'] == $flowList['equipment_id']
                    && $v['create_time'] > (time() - 5) && $v['create_time'] <= (time())) {
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
