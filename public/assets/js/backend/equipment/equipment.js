define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'equipment/equipment/index' + location.search,
                    add_url: 'equipment/equipment/add',
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
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate:false},
                        {field: 'instrument_type', title: __('Instrument_type'),searchList: {"1":__('Instrument_type 1'),"2":__('Instrument_type 2')},visible:false},
                        {field: 'instrument_type_text', title: __('Instrument_type'), operate:false},
                        {field: 'equipment_addr', title: __('Equipment_addr'), operate:false},
                        {field: 'monitor_object', title: __('Monitor_object'), operate: 'LIKE'},
                        {field: 'gateway_addr', title: __('Gateway_addr'), operate: false},
                        {field: 'HIAL', title: __('Hial'), operate:false,visible:false},
                        {field: 'LoAL', title: __('Loal'), operate:false,visible:false},
                        {field: 'effective_range', title: __('Effective_range'), operate:false,visible:false},
                        {field: 'unit', title: __('Unit'), operate:false,visible:false,},
                        {field: 'decimal_offset', title: __('Decimal_offset'), operate:false,visible:false},
                        {field: 'alias', title: __('Alias'), operate:false},
                        {field: 'status', title: __('Status'), operate:false, searchList: {"1":__('Status 1'),"-1":__('Status -1')}, formatter: Table.api.formatter.status},
                        {field: 'company_id', title: __('Company_id'), operate:false,visible:false},
                        {field: 'building_id', title: __('Building_id'), operate:false,visible:false},
                        {field: 'floor_id', title: __('Floor_id'), operate:false,visible:false},
                        {field: 'create_time', title: __('Create_time'), operate:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:false,visible:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'delete_time', title: __('Delete_time'), operate:false,visible:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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