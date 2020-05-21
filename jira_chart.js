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
        //$("#footer").hide();
        $(".projectChart").hide();
    }
}

function showProjectCharts(projectName, timeFilter) {
    generateGaugeData(projectName, timeFilter);
    fetchPieChartData(projectName, timeFilter);
    fetchJiraData_AllPercentages_Project(projectName, timeFilter);
    fetchJiraData_AllNumbers_Project(projectName, timeFilter);
    //showDefaultCharts(timeFilter);
}

function showDefaultCharts(timeFilter) {
    fetchJiraData_TotalBugs_All(timeFilter);
    fetchJiraData_BugPercentage_All(timeFilter);
}

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

            var chartProperties1 = {
                "caption": "",
                "lowerLimit": "0",
                "upperLimit": "100",
                "showValue": "1",
                "numberSuffix": "%",
                "theme": "fusion",
                "showToolTip": "1"
            };

            apiChart1 = new FusionCharts({
                type: 'angulargauge',
                renderAt: 'gauge-chart-container1',
                width: '350',
                height: '180',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties1,
                    "colorRange": {
                        "color": [{
                            "minValue": "0",
                            "maxValue": "50",
                            "code": "#F2726F"
                        }, {
                            "minValue": "50",
                            "maxValue": "75",
                            "code": "#FFC533"
                        }, {
                            "minValue": "75",
                            "maxValue": "100",
                            "code": "#62B58F"
                        }]
                    },
                    "dials": {
                        "dial": [{
                            "value": resultValue1
                        }]
                    }
                }
            });
            apiChart1.render();

            var chartProperties2 = {
                "caption": "",
                "lowerLimit": "0",
                "upperLimit": "100",
                "showValue": "1",
                "numberSuffix": "%",
                "theme": "fusion",
                "showToolTip": "1"
            };

            apiChart2 = new FusionCharts({
                type: 'angulargauge',
                renderAt: 'gauge-chart-container2',
                width: '350',
                height: '180',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties2,
                    "colorRange": {
                        "color": [{
                            "minValue": "0",
                            "maxValue": "50",
                            "code": "#F2726F"
                        }, {
                            "minValue": "50",
                            "maxValue": "75",
                            "code": "#FFC533"
                        }, {
                            "minValue": "75",
                            "maxValue": "100",
                            "code": "#62B58F"
                        }]
                    },
                    "dials": {
                        "dial": [{
                            "value": resultValue2
                        }]
                    }
                }
            });
            apiChart2.render();

            var chartProperties3 = {
                "caption": "",
                "lowerLimit": "0",
                "upperLimit": "100",
                "showValue": "1",
                "numberSuffix": "%",
                "theme": "fusion",
                "showToolTip": "1"
            };

            apiChart3 = new FusionCharts({
                type: 'angulargauge',
                renderAt: 'gauge-chart-container3',
                width: '350',
                height: '180',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties3,
                    "colorRange": {
                        "color": [{
                            "minValue": "0",
                            "maxValue": "50",
                            "code": "#F2726F"
                        }, {
                            "minValue": "50",
                            "maxValue": "75",
                            "code": "#FFC533"
                        }, {
                            "minValue": "75",
                            "maxValue": "100",
                            "code": "#62B58F"
                        }]
                    },
                    "dials": {
                        "dial": [{
                            "value": resultValue3
                        }]
                    }
                }
            });
            apiChart3.render();
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
                "subCaption" : "",
                "showValues":"1",
                "showPercentInTooltip" : "1",
                "numberPrefix" : "",
                "enableMultiSlicing":"1",
                "theme": "gammel"
            };
            apiChart = new FusionCharts({
                type: 'pie3d',
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
                height: '350',
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
                height: '350',
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
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Number of Bugs",
                "divlineColor": "#999999",
                "divLineDashed": "1",
                "theme": "fusion",
                "showValues": "0",
                "showsum": "1"
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

function fetchJiraData_BugPercentage_All(timeFilter) {
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
                "caption": "Bug Percentage w.r.t Tickets Tested for last " + timeFilter + " days [All Projects]",
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