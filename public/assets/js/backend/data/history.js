define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'data/history/index' + location.search,
                    add_url: 'data/history/add',
                    edit_url: 'data/history/edit',
                    del_url: 'data/history/del',
                    multi_url: 'data/history/multi',
                    import_url: 'data/history/import',
                    table: 'data',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                exportTypes: ["excel"],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'IPC_id', title: __('Ipc_id'), operate: false},
                        {field: 'equipment_id', title: __('Equipment_id'), operate: false},
                        {field: 'value', title: __('值'), operate: false},
                        {field: 'create_time', title: __('Create_time'), operate: false, formatter: Table.api.formatter.datetime},
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