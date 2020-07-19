var defaultFilter = "30";
var projectName = 0;

$(function () {
    activateFilter();
});

$(document).ready(function () {
    $("#projectName").click(function () {
        $("#selectProject").show();
        showDefaultCharts(getFilter());
        $("#projectName").hide();
    });

    $(".filter").click(function () {
        $(".filter").removeClass("active");
        $(this).addClass("active");
    });

    $("#weeklyData").click(function () {
        saveFilter("7");
        validateAndExecute(getFilter());
    });

    $("#monthlyData").click(function () {
        saveFilter("30");
        validateAndExecute(getFilter());
    });

    $("#quarterlyData").click(function () {
        saveFilter("90");
        validateAndExecute(getFilter());
    });

    $("#yearlyData").click(function () {
        saveFilter("365");
        validateAndExecute(getFilter());
    });
});

function activateFilter() {
    var currentFilter = getFilter();
    if (!currentFilter) {
        saveFilter(defaultFilter);
        currentFilter = defaultFilter;
    }
    switch (currentFilter) {
        case '7':
            document.getElementById("week").classList.add("active");
            break;
        case '30':
            document.getElementById("month").classList.add("active");
            break;
        case '90':
            document.getElementById("quarter").classList.add("active");
            break;
        case '365':
            document.getElementById("year").classList.add("active");
            break;
    }
    validateAndExecute(currentFilter);
}

function saveFilter(value) {
    localStorage.setItem("appiledFilter", value);
}

function getFilter() {
    return localStorage.getItem("appiledFilter");
}

function hideProjectCharts() {
    $(".defaultChart").show();
    $("#projectName").hide();
    $(".projectChart").hide();
    $("#warning").show();
    $("#selectProject").show();
    $("#projectName").html("");
    $(".gauge").html("Project not selected.<br>No data to display!");
    $(".gauge").removeClass("bigFont");
    $(".gauge").addClass("custom-text-2");
}

function hideDefaultCharts() {
    $("#selectProject").hide();
    $("#warning").hide();
    $(".defaultChart").hide();
}

function validateAndExecute(timeFilter) {
    projectName = $("#projectName").html();
    if (projectName.length != 0) {
        showProjectCharts(projectName, timeFilter);
    } else {
        showDefaultCharts(timeFilter);
    }
}