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
    fetchLastTenResults(projectName, timeFilter);
    fetchAvgPercentageData(projectName, timeFilter);
    fetchTotalCasesData(projectName, timeFilter);
    //showDefaultCharts(timeFilter);
}

function showDefaultCharts(timeFilter) {

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

function fetchLastTenResults(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getLatestResultsData_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Details of last " + timeFilter + " Automation Builds",
                "xAxisName": "Build Name",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label - $dataValue%",
                "theme": "ocean"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container1',
                width: '96%',
                height: '350',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": result
                },
                "events": {
                    "beforeRender": function (e, d) {
                        var messageBlock = document.createElement('p');
                        messageBlock.style.textAlign = "center";
                        var activatedMessage = 'Click on the plot to access the Results Link';

                        var getClickedMessage = function (categoryLabel, displayValue) {
                            var temp = "";
                            if (categoryLabel.includes("Golabs")) {
                                temp = categoryLabel.replace("Golabs-", "");
                            } else {
                                temp = categoryLabel.replace("Jenkins", projectName);
                                var position = temp.lastIndexOf("-");
                                temp = temp.substring(0, position) + "-Automation" + temp.substring(position);
                            }
                            var resultsLink = "http://52.221.7.215/" + projectName + "/" + temp + "/html/index.html";
                            return 'Results Url of <B>"' + categoryLabel + '"</B> - <a style="color:yellow" href="' + resultsLink + '" target="_blank">' + resultsLink + '</a>';
                        };
                        e.data.container.appendChild(messageBlock);

                        function dataPlotClickListener(e, a) {
                            var categoryLabel = e.data.categoryLabel;
                            var displayValue = e.data.displayValue;
                            var resMessage = getClickedMessage(categoryLabel, displayValue);
                            messageBlock.innerHTML = resMessage;
                        }

                        messageBlock.innerText = activatedMessage;
                        e.sender.addEventListener('dataplotclick', dataPlotClickListener);
                    }
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
            functionname: 'getTotalCasesResultsData_Project',
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
                "caption": "GroupName wise total cases for last " + timeFilter + " days",
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Total Testcases",
                "theme": "candy",
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


function fetchAvgPercentageData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getAvgResultsData_Project',
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
                "caption": "Average daily percentage for last " + timeFilter + " days",
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