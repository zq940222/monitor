define(['jquery', 'bootstrap', 'backend', 'table', 'form','echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        index: function () {
            var companyId = $("#company").val();
            //查询可查询得楼号
            $.ajax({
                url:"ajax/getBuildingByCompanyId",
                type:"POST",
                data:{company_id:companyId},
                success:function (res) {
                    if (res.code == 1){
                        let buildings = res.data;
                        //渲染楼号
                        for (let i = 0; i < buildings.length; i++) {
                            if (i == 0){
                                //默认选中第一个楼
                                $("#building").append("<a href='javascript:void(0)'><div class='building col-xs-4 col-sm-6 btn-success'  data-value='"+buildings[i]['id']+"'>"+buildings[i]['name']+"</div></a>");
                                var buildingId = buildings[i]['id'];
                                $.ajax({
                                    url:"ajax/getFloorByBuildingId",
                                    type:"POST",
                                    data:{building_id:buildingId},
                                    success:function (res) {
                                        if (res.code == 1){
                                            let floors = res.data;
                                            //渲染楼号
                                            for (let i = 0; i < floors.length; i++) {
                                                if (i == 0){
                                                    //默认选中第一个楼
                                                    $("#floor").append("<a href='javascript:void(0)'><div class='floor col-xs-4 col-sm-6 btn-success' data-value='"+floors[i]['id']+"'>"+floors[i]['name']+"</div></a>");
                                                    getData(floors[i]['id']);
                                                }else {
                                                    $("#floor").append("<a href='javascript:void(0)'><div class='floor col-xs-4 col-sm-6 btn-default' data-value='"+floors[i]['id']+"'>"+floors[i]['name']+"</div></a>");
                                                }
                                            }
                                        }
                                    }
                                });
                            }else {
                                $("#building").append("<a href='javascript:void(0)'><div class='building col-xs-4 col-sm-6 btn-default' data-value='"+buildings[i]['id']+"'>"+buildings[i]['name']+"</div></a>");
                            }
                        }
                    }
                }
            });


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
//绑定楼change事件
$("#building").on('click','.building', function () {
    $(".building").removeClass('btn-success');
    $(".building").addClass('btn-default');
    $(this).removeClass('btn-default');
    $(this).addClass('btn-success');
    //清除层数据
    $("#floor").empty();
    $("#pressure").empty();
    $("#flow").empty();
    //获取当前选中楼的层数据
    var buildingId = $(this).data('value');
    $.ajax({
        url:"ajax/getFloorByBuildingId",
        type:"POST",
        data:{building_id:buildingId},
        success:function (res) {
            if (res.code == 1){
                let floors = res.data;
                //渲染楼号
                for (let i = 0; i < floors.length; i++) {
                    if (i == 0){
                        //默认选中第一个楼
                        $("#floor").append("<a href='javascript:void(0)'><div class='floor col-xs-4 col-sm-6 btn-success' data-value='"+floors[i]['id']+"'>"+floors[i]['name']+"</div></a>");
                        getData(floors[i]['id']);
                    }else {
                        $("#floor").append("<a href='javascript:void(0)'><div class='floor col-xs-4 col-sm-6 btn-default' data-value='"+floors[i]['id']+"'>"+floors[i]['name']+"</div></a>");
                    }
                }
            }
        }
    })
});

//绑定单位change事件
$("#company").on("change", function () {
    //清楚楼和层
    $("#building").empty();
    $("#floor").empty();
    $("#pressure").empty();
    $("#flow").empty();
    var companyId = $("#company").val();
    //查询可查询得楼号
    $.ajax({
        url:"ajax/getBuildingAndFloorByCompanyId",
        type:"POST",
        data:{company_id:companyId},
        success:function (res) {
            if (res.code == 1){
                let buildings = res.data;
                //渲染楼号
                for (let i = 0; i < buildings.length; i++) {
                    if (i == 0){
                        //默认选中第一个楼
                        $("#building").append("<a href='javascript:void(0)'><div class='building col-xs-4 col-sm-6 btn-success'  data-value='"+buildings[i]['id']+"'>"+buildings[i]['name']+"</div></a>");
                        var buildingId = buildings[i]['id'];
                        $.ajax({
                            url:"ajax/getFloorByBuildingId",
                            type:"POST",
                            data:{building_id:buildingId},
                            success:function (res) {
                                if (res.code == 1){
                                    let floors = res.data;
                                    //渲染楼号
                                    for (let i = 0; i < floors.length; i++) {
                                        if (i == 0){
                                            //默认选中第一个楼
                                            $("#floor").append("<a href='javascript:void(0)'><div class='floor col-xs-4 col-sm-6 btn-success'>"+floors[i]['name']+"</div></a>");
                                            getData(floors[i]['id']);
                                        }else {
                                            $("#floor").append("<a href='javascript:void(0)'><div class='floor col-xs-4 col-sm-6 btn-default'>"+floors[i]['name']+"</div></a>");
                                        }
                                    }
                                }
                            }
                        });
                    }else {
                        $("#building").append("<a href='javascript:void(0)'><div class='building col-xs-4 col-sm-6 btn-default' data-value='"+buildings[i]['id']+"'>"+buildings[i]['name']+"</div></a>");
                    }
                }
            }
        }
    });
})

