var defaultFilter = "30";
var currentYear = "2020";
var projectName = 0;
var backend = "server/results-data.php";

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

function showDefaultCharts(timeFilter) {
    hideProjectCharts();
    fetchAvgPercentageProd_ColumnChart(timeFilter);
    fetchAvgPercentageStg_ColumnChart(timeFilter);
    fetchAvgExecutionTimeProd_ColumnChart(timeFilter);
    fetchAvgExecutionTimeStg_ColumnChart(timeFilter);
}

function showProjectCharts(projectName, timeFilter) {
    hideDefaultCharts();
    fetchAvgPercentage_GaugeChart(projectName, timeFilter);
    fetchLastSevenResults_ColumnChart(projectName, timeFilter);
    fetchAvgDailyPercentage_LineChart(projectName, timeFilter);
    fetchAvgDailyExecutionTime_LineChart(projectName, timeFilter);
    fetchTotalCasesGroupwise_LineChart(projectName, timeFilter);
}

function fetchAvgPercentageProd_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [timeFilter, 'Production', 'production']
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Thanos - Average Prod Percentage for last " + timeFilter + " days [All Projects]",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": "zune"
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
                }
            });
            apiChart.render();
        }
    });
};

function fetchAvgPercentageStg_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [timeFilter, 'Staging', 'regression']
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Thanos - Average Staging Percentage for last " + timeFilter + " days [All Projects]",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": "zune"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container2',
                width: '96%',
                height: '350',
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

function fetchAvgExecutionTimeProd_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [timeFilter, 'Production', 'production']
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Thanos - Average Prod Execution Time for last " + timeFilter + " days [All Projects]",
                "yAxisName": "Time Taken (in minutes)",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue minutes",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container3',
                width: '96%',
                height: '350',
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

function fetchAvgExecutionTimeStg_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [timeFilter, 'Staging', 'regression']
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Thanos - Average Staging Execution Time for last " + timeFilter + " days [All Projects]",
                "yAxisName": "Time Taken (in minutes)",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue minutes",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container4',
                width: '96%',
                height: '350',
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

function fetchAvgPercentage_GaugeChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "Production-data")
                        resultValue1 = value;
                    if (key === "Sandbox-data")
                        resultValue2 = value;
                    if (key === "Staging-data")
                        resultValue3 = value;
                });
            }

            var chartProperties = {
                "plottooltext": "$label: $dataValue%",
                "showBorder": "0",
                "captionfontcolor": "#686980",
                "captionfontsize": "16",
                "captionalignment": "left",
                "showShadow": "0",
                "use3DLighting": "0",
                "showLabels": "0",
                "showValues": "0",
                "paletteColors": "#efefef,#11DFF6",
                "bgColor": "#1D1B41",
                "bgAlpha": "0",
                "canvasBgAlpha": "0",
                "doughnutRadius": "57",
                "pieRadius": "70",
                "enableSlicing": "0",
                "plotBorderAlpha": "0",
                "showToolTip": "1",
                "baseFontSize": "14",
                "logoURL": "images/shield.svg",
                "logoScale": "4",
                "logoAlpha": "100",
                "logoPosition": "TR",
                "logoTopMargin": "2",
                "defaultCenterLabel": null,
                "centerLabelBold": "1",
                "centerLabelFontSize": "25",
                "enableRotation": "0",
                "captionfont": "avenir-heavy",
                "baseFont": "avenir-medium",
                "startingAngle": "90",
                "animateClockwise": "1"
            };

            apiChart1 = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'gauge-chart-container1',
                width: '88%',
                height: '160',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultValue1
                },
                "events": {
                    "beforeInitialize": function () {
                        if (resultValue1) {
                            var passPercentage = resultValue1[1].value;
                            chartProperties.defaultCenterLabel = passPercentage + "%";
                        }
                    }
                }
            });
            apiChart1.render();

            apiChart2 = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'gauge-chart-container2',
                width: '88%',
                height: '160',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultValue2
                },
                "events": {
                    "beforeInitialize": function () {
                        if (resultValue2) {
                            var passPercentage = resultValue2[1].value;
                            chartProperties.defaultCenterLabel = passPercentage + "%";
                        }
                    }
                }
            });
            apiChart2.render();

            apiChart3 = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'gauge-chart-container3',
                width: '88%',
                height: '160',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultValue3
                },
                "events": {
                    "beforeInitialize": function () {
                        if (resultValue3) {
                            var passPercentage = resultValue3[1].value;
                            chartProperties.defaultCenterLabel = passPercentage + "%";
                        }
                    }
                }
            });
            apiChart3.render();
        }
    });
};

function fetchLastSevenResults_ColumnChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getLast7Records',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var linkSeperator = ", link- ";
            const fullData = JSON.parse(JSON.stringify(result));

            var chartProperties = {
                "caption": "Details of last " + timeFilter + " Automation Builds",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": "ocean"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container5',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                "events": {
                    "beforeInitialize": function (eventObj, dataObj) {
                        for (var dataS in Object.entries(result)) {
                            result.find(v => v.label.toString().includes(linkSeperator)).label = result.find(v => v.label.toString().includes(linkSeperator)).label.toString().split(linkSeperator)[0];
                        }
                    },
                    "beforeRender": function (e, d) {
                        var messageBlock = document.createElement('p');
                        messageBlock.style.textAlign = "center";
                        var activatedMessage = 'Click on the plot to access the Results Link';
                        var getClickedMessage = function (categoryLabel, displayValue) {
                            var fullLabel = fullData.find(v => v.label.toString().includes(categoryLabel)).label;
                            var resultsLink = fullLabel.split(linkSeperator)[1];
                            categoryLabel = categoryLabel.substring(categoryLabel.lastIndexOf("\n") + 1);
                            return 'Results Link for <B>"' + categoryLabel + '"</B> - <a style="color:yellow" href="' + resultsLink + '" target="_blank">' + resultsLink + '</a>';
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
                },
                dataSource: {
                    "chart": chartProperties,
                    "data": result
                },
            });

            apiChart.render();
        }
    });
};

function fetchAvgDailyPercentage_LineChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgPercentage_Project',
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

function fetchAvgDailyExecutionTime_LineChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgExecutionTime_Project',
            arguments: [projectName, timeFilter, "'regression','production'"]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily Execution Time for last " + timeFilter + " days",
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue minutes",
                "yAxisName": "Time Taken (in minutes)",
                "theme": "candy",
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

function fetchTotalCasesGroupwise_LineChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalCasesGroupwise_Project',
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
                "theme": "fusion",
                "showValues": "1"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container3',
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