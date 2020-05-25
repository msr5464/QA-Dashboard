var projectName = 0;
$(function () {
    validateAndExecute("7");
});

$(document).ready(function () {
    $("#projectName").click(function () {
        $("#selectProject").show();
        showDefaultCharts("7");
        $("#projectName").hide();
    });

    $(".filter").click(function () {
        $(".filter").removeClass("active");
        $(this).addClass("active");
    });

    $("#weeklyData").click(function(){
        validateAndExecute("7");
    });

    $("#monthlyData").click(function(){
        validateAndExecute("30");
    });

    $("#quarterlyData").click(function(){
        validateAndExecute("90");
    });

    $("#yearlyData").click(function(){
        validateAndExecute("365");
    });
});

function validateAndExecute(timeFilter) {
    projectName = $("#projectName").html();
    if (projectName.length != 0) {
        $("#selectProject").hide();
        $("#warning").hide();
        showProjectCharts(projectName, timeFilter);
        $(".defaultChart").hide();
    } else {
        showDefaultCharts(timeFilter);
        $("#projectName").hide();
        $(".projectChart").hide();
    }
}

function showProjectCharts(projectName, timeFilter) {
    generateGaugeData(projectName, timeFilter);
    fetchPieChartData(projectName, timeFilter);
    fetchJiraData_AllPercentages_Project(projectName, timeFilter);
    fetchJiraData_AllNumbers_Project(projectName, timeFilter);
}

function showDefaultCharts(timeFilter) {
    fetchJiraData_TotalBugs_All(timeFilter);
    fetchJiraData_BugPercentage(timeFilter);
}

function fetchJiraData_TotalBugs_All(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getJiraData_TotalBugs_All', arguments: [timeFilter]},
        success: function(result) 
        {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = 
            {
                "caption": "Total Bugs found in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue",
                "yAxisName": "Number of Bugs",
                "divlineColor": "#999999",
                "divLineDashed": "1",
                "theme": "fusion",
                "showValues": "1",
                "showsum": "0"
            };

            apiChart = new FusionCharts({
                type: 'stackedcolumn2dline',
                renderAt: 'column-chart-container1',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData,
                    "categories": categoriesData
                }
            });
            apiChart.render();
        }
    });
};

function fetchJiraData_BugPercentage(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getJiraData_BugPercentage_All', arguments: [timeFilter]},
        success: function(result) 
        {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = 
            {
                "caption": "Overall Bug Percentage for 2020 [All Projects]",
                "plottooltext": "$seriesName - $dataValue%",
                "yAxisName": "Percentage",
                "rotatevalues": "0",
                "theme": "zune",
                "showValues":"1"
            };

            apiChart = new FusionCharts({
                type: 'mscombi2d',
                renderAt: 'column-chart-container2',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData,
                    "categories": categoriesData
                }
            });
            apiChart.render();
        }
    });
};

function generateGaugeData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getAvgPassPercentage_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "Production")
                        resultValue1 = value;
                    if (key === "Sandbox")
                        resultValue2 = value;
                    if (key === "Staging")
                        resultValue3 = value;
                });
            }
        }
    });
};


function fetchPieChartData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getJiraData_Project_Pie',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {

            var chartProperties = {
                "caption": "Priority wise Bugs Breakdown for last " + timeFilter + " days for "+projectName,
                "showpercentvalues": "1",
                "defaultcenterlabel": "Bugs Found",
                "aligncaptionwithcanvas": "0",
                "captionpadding": "0",
                "decimals": "1",
                "plottooltext": "$label: $dataValue",
                "centerlabel": "$label: $value",
                "theme": "candy"
            };
            apiChart = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'pie-chart-container1',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": result
                }
            });
            apiChart.render();
        }
    });
};

function fetchJiraData_AllPercentages_Project(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getJiraData_AllPercentages_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Trend of Bug Percentage for last " + timeFilter + " days for "+projectName,
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue%",
                "yAxisName": "Percentage",
                "theme": "fusion",
                "showValues": "1"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container1',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData,
                    "categories": categoriesData
                }
            });
            apiChart.render();
        }
    });
};

function fetchJiraData_AllNumbers_Project(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getJiraData_AllNumbers_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Trend of Bug Count for last " + timeFilter + " days for "+projectName,
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue%",
                "yAxisName": "Percentage",
                "theme": "fusion",
                "showValues": "1"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container2',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData,
                    "categories": categoriesData
                }
            });
            apiChart.render();
        }
    });
};