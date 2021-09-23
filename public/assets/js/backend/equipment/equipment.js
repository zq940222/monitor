define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'equipment/equipment/index?ids=' + $("input[name=floor_id]").val() + location.search,
                    add_url: 'equipment/equipment/add?ids=' + $("input[name=floor_id]").val(),
                    edit_url: 'equipment/equipment/edit',
                    del_url: 'equipment/equipment/del',
                    multi_url: 'equipment/equipment/multi',
                    import_url: 'equipment/equipment/import',
                    table: 'equipment',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate:false},
                        {
                            field: 'company_id',
                            title: __('Company_id'),
                            visible:false,
                            searchList: $.getJSON('equipment/company/searchlist'),
                            addClass: "selectpicker"
                        },
                        {field: 'company.name', title: __('单位'), operate:false},
                        {field: 'building_id', title: __('Building_id'), visible:false, operate:false},
                        {field: 'building.name', title: __('楼'), operate:false},
                        {field: 'floor_id', title: __('Floor_id'), visible:false, operate:false},
                        {field: 'floor.name', title: __('层'), operate:false},
                        {field: 'equipment_id', title: __('Equipment_id'), operate: 'LIKE'},
                        {field: 'instrument_type', title: __('Instrument_type'), searchList: {"1":__('Instrument_type 1'),"2":__('Instrument_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'monitor_object', title: __('Monitor_object'), operate: 'LIKE'},
                        {field: 'HIAL', title: __('Hial'), operate:false, visible:false},
                        {field: 'LoAL', title: __('Loal'), operate:false, visible:false},
                        {field: 'effective_range', title: __('Effective_range'), operate:false, visible: false},
                        {field: 'unit', title: __('Unit'), operate:false, visible: false, formatter: Table.api.formatter.normal},
                        {field: 'decimal_offset', title: __('Decimal_offset'), operate:false, visible:false},
                        {field: 'alias', title: __('Alias'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"-1":__('Status -1')}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), operate:false},
                        {field: 'update_time', title: __('Update_time'), operate:false, visible:false},
                        {field: 'delete_time', title: __('Delete_time'), operate:false, visible:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});