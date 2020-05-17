var projectName = 0;
$(function () {
    validateAndExecute("7");
});

$(document).ready(function () {
    $("#projectName").click(function () {
        $("#selectProject").show();
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
        executeAll(projectName, timeFilter);
    } else {
        $("#projectName").hide();
    }
}

function executeAll(projectName, timeFilter) {
    generateGaugeData(projectName, timeFilter);
    fetchPieChartData(projectName, timeFilter);
    fetchTotalCasesData(projectName, timeFilter);
    fetchAutomationCoverageData(timeFilter);
    fetchP0CoverageData(timeFilter);
    fetchP1CoverageData(timeFilter);
    fetchP2CoverageData(timeFilter);
}

function generateGaugeData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestrailCoverageData_Project', arguments: [projectName, timeFilter]},
        success: function(result) 
        {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i=0;i<result.length;i++)
            {
                $.each(result[i], function(key, value)
                {
                    if(key==="totalCoverage")
                        resultValue1 = value;
                    if(key==="P0Coverage")
                        resultValue2 = value;
                    if(key==="P1Coverage")
                        resultValue3 = value;
                });
            }

            var chartProperties1 = 
            {
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
                    "colorRange": 
                    {
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
                    "dials": 
                    {
                        "dial": [{
                            "value": resultValue1
                        }]
                    }
                }
            });
            apiChart1.render();

            var chartProperties2 = 
            {
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
                    "colorRange": 
                    {
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
                    "dials": 
                    {
                        "dial": [{
                            "value": resultValue2
                        }]
                    }
                }
            });
            apiChart2.render();

            var chartProperties3 = 
            {
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
                    "colorRange": 
                    {
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
                    "dials": 
                    {
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
            functionname: 'getTotalCasesTestrailData_Project_Pie',
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
                "caption": "Testrail - Automation Cases Breakdown for "+projectName,
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
                width: '1250',
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

function fetchTotalCasesData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getTotalCasesTestrailData_Project_Line',
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
                "caption": "Distribution of total cases for last " + timeFilter + " days for "+projectName,
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Total Testcases",
                "theme": "fusion",
                "showValues": "1"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container1',
                width: '1250',
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

function fetchAutomationCoverageData(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_Coverage'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - Full Automation Coverage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container1',
                width: '1250',
                height: '500',
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

function fetchP0CoverageData(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_P0'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - P0 Automation Coverage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container2',
                width: '1250',
                height: '500',
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

function fetchP1CoverageData(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_P1'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - P1 Automation Coverage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container3',
                width: '1250',
                height: '500',
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

function fetchP2CoverageData(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_P2'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - P2 Automation Coverage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container4',
                width: '1250',
                height: '500',
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