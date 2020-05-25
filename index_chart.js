$(function () {
    showDefaultCharts("7");
});

$(document).ready(function () {
    $(".filter").click(function () {
        $(".filter").removeClass("active");
        $(this).addClass("active");
    });

    $("#weeklyData").click(function () {
        showDefaultCharts("7");
    });

    $("#monthlyData").click(function () {
        showDefaultCharts("30");
    });

    $("#quarterlyData").click(function () {
        showDefaultCharts("90");
    });

    $("#yearlyData").click(function () {
        showDefaultCharts("365");
    });
});

function showDefaultCharts(timeFilter) {

}