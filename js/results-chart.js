var backend = "utils/results-data.php";
var pageName = "results.php";

function showDefaultCharts(verticalName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    var environment1 = getDataFromVerticalTable(verticalName, "environment1");
    var environment2 = getDataFromVerticalTable(verticalName, "environment2");
    var groupName1 = getDataFromVerticalTable(verticalName, "groupName1");
    var groupName2 = getDataFromVerticalTable(verticalName, "groupName2");
    fetchAvgPercentage_GaugeChart(verticalName, startDate, endDate, "'regression', 'production'");
    fetchAvgPercentage_ColumnChart(verticalName, timeFilter, startDate, endDate, environment1, groupName1, 1);
    fetchAvgPercentage_ColumnChart(verticalName, timeFilter, startDate, endDate, environment2, groupName2, 2);
    fetchAvgExecutionTime_ColumnChart(verticalName, timeFilter, startDate, endDate, environment1, groupName1, 3);
    fetchAvgExecutionTime_ColumnChart(verticalName, timeFilter, startDate, endDate, environment2, groupName2, 4);
    window.setTimeout(hideBlankCharts, 2500);
}

function showProjectCharts(verticalName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    var environment1 = getDataFromVerticalTable(verticalName, "environment1");
    var environment2 = getDataFromVerticalTable(verticalName, "environment2");
    var groupName1 = getDataFromVerticalTable(verticalName, "groupName1");
    var groupName2 = getDataFromVerticalTable(verticalName, "groupName2");
    fetchAvgPercForProject_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate, "'regression', 'production'");
    fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, groupName1, 7);
    fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate, environment2, groupName2, 8);
    fetchAvgDailyPercentage_LineChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, environment2, groupName1, groupName2);
    fetchAvgDailyExecutionTime_LineChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, environment2, groupName1, groupName2);
    fetchTotalCasesGroupwise_LineChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, environment2, groupName1, groupName2);
}

function fetchAvgPercentage_GaugeChart(verticalName, startDate, endDate, groupNames) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage_All',
            arguments: [verticalName, startDate, endDate, groupNames]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "Production-data")
                        resultValue3 = value;
                    if (key === "Sandbox-data")
                        resultValue2 = value;
                    if (key === "Staging-data")
                        resultValue1 = value;
                });
            }
            enableGaugeCharts(resultValue1, resultValue2, resultValue3);
        }
    });
}

function fetchAvgPercentage_ColumnChart(verticalName, timeFilter, startDate, endDate, environment, groupName, chartNum) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [verticalName, startDate, endDate, environment, groupName]
        },
        success: function (result) {
            var resultsData = 0;
            var environmentValue = 0;
            var groupNameValue = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "resultsData")
                        resultsData = value;
                    if (key === "environmentValue")
                        environmentValue = value;
                    if (key === "groupNameValue")
                        groupNameValue = value;
                });
            }

            var chartProperties = {
                "caption": "Average '"+groupNameValue+"' percentage for last " + timeFilter + " days [on "+environmentValue+"]",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": "zune"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container'+chartNum,
                width: '96%',
                height: '350',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultsData
                }
            });
            apiChart.render();
        }
    });
};

function fetchAvgExecutionTime_ColumnChart(verticalName, timeFilter, startDate, endDate, environment, groupName, chartNum) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [verticalName, startDate, endDate, environment, groupName]
        },
        success: function (result) {
            var resultsData = 0;
            var environmentValue = 0;
            var groupNameValue = 0;
            var categoriesData = 0;
            var datasetData = 0;
            var trendLineAvg = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "categories")
                    categoriesData = value;
                    if (key === "dataset")
                        datasetData = value;
                    if (key === "environmentValue")
                        environmentValue = value;
                    if (key === "groupNameValue")
                        groupNameValue = value;
                    if (key === "trendLineAvg")
                        trendLineAvg = value;
                });
            }

            if (groupNameValue === "")
            {
                categoriesData = 0;
                datasetData = 0;
            }
            var chartProperties = {
                "caption": "Average '"+groupNameValue+"' execution time for last " + timeFilter + " days [on "+environmentValue+"]",
                "rotatevalues": "0",
                "showvalues": "1",
                "theme": "candy",
                "trendValueFont": "Arial",
                "trendValueFontSize": "13",
                "trendValueFontBold": "1",
                "trendValueFontItalic": "1",
                "trendValueAlpha": "70",
                "trendValueBorderColor": "ff0000",
                "trendValueBorderAlpha": "80",
                "trendValueBorderPadding": "2",
                "trendValueBorderRadius": "3",
                "trendValueBorderThickness": "1",
                "trendValueBorderDashed": "0",
                "trendValueBorderDashLen": "#123456",
                "trendValueBorderDashGap": "1"
            };

            apiChart = new FusionCharts({
                type: 'mscombi2d',
                renderAt: 'column-chart-container'+chartNum,
                width: '96%',
                height: '350',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData,
                    "categories": categoriesData,
                    "trendlines": [{
                "line": [{
                  "startvalue": trendLineAvg,
                  "displayvalue": "Avg: "+trendLineAvg,
                  "valueOnRight": "0",
                  "thickness": "3",
                  "dashed": "1",
                  "alpha": "70",
                  "tooltext": "Average: $startvalue"
                }]
            }]
                }
            });
            apiChart.render();
        }
    });
};

function fetchAvgPercForProject_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate, groupNames) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage_Project',
            arguments: [verticalName, projectName, startDate, endDate, groupNames]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "Production-data")
                        resultValue3 = value;
                    if (key === "Sandbox-data")
                        resultValue2 = value;
                    if (key === "Staging-data")
                        resultValue1 = value;
                });
            }
            enableGaugeCharts(resultValue1, resultValue2, resultValue3);
        }
    });
}

function enableGaugeCharts(resultValue1, resultValue2, resultValue3) 
{
    var chartProperties = {
        "plottooltext": "$label: $dataValue%",
        "showBorder": "0",
        "captionfontcolor": "#686980",
        "captionfontsize": "15",
        "captionalignment": "left",
        "showShadow": "0",
        "use3DLighting": "0",
        "showLabels": "0",
        "showValues": "0",
        "paletteColors": "#efefef,#11DFF6",
        "bgColor": "#ffffff",
        "bgAlpha": "100",
        "canvasBgAlpha": "0",
        "doughnutRadius": "57",
        "pieRadius": "70",
        "enableSlicing": "0",
        "plotBorderAlpha": "0",
        "showToolTip": "1",
        "baseFontSize": "14",
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
        width: '92%',
        height: '150',
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
        width: '92%',
        height: '150',
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
        width: '92%',
        height: '150',
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

function fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate, environment, groupName, chartNum) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getLast7Records',
            arguments: [verticalName, projectName, startDate, endDate, environment, groupName]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            var linkSeperator = ", link- ";
            const fullData = JSON.parse(JSON.stringify(categoriesData[0].category));

            var chartProperties = {
                "caption": "Latest '"+groupName+"' builds for last " + timeFilter + " days on "+environment+" [" + projectName + "]",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": "candy",
                "flatscrollbars": "0",
                "scrollheight": "10",
                "scrollColor": "#fff",
                "numvisibleplot": "15",
                "drawCrossLine": "1"
            };

            apiChart = new FusionCharts({
                type: 'scrollcolumn2d',
                renderAt: 'column-chart-container' + chartNum,
                width: '96%',
                height: '400',
                dataFormat: 'json',
                "events": {
                    "beforeInitialize": function (eventObj, dataObj) {
                        for (var i = 0; i < categoriesData[0].category.length; i++) {
                          oldValue = categoriesData[0].category[i].label;
                          categoriesData[0].category[i].label = oldValue.split(linkSeperator)[0];
                        }
                    },
                    "beforeRender": function (e, d) {
                        var messageBlock = document.createElement('p');
                        messageBlock.style.textAlign = "center";
                        var activatedMessage = 'Click on the respective column to get Build Link';
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
                    "dataset": datasetData,
                    "categories": categoriesData
                }
            });

            apiChart.render();
        }
    });
};

function fetchAvgDailyPercentage_LineChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, environment2, groupName1, groupName2) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgPercentage_Project',
            arguments: [verticalName, projectName, startDate, endDate, environment1, environment2, groupName1, groupName2]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily percentage for last " + timeFilter + " days [" + projectName + "]",
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

function fetchAvgDailyExecutionTime_LineChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, environment2, groupName1, groupName2) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgExecutionTime_Project',
            arguments: [verticalName, projectName, startDate, endDate, environment1, environment2, groupName1, groupName2]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily Execution Time for last " + timeFilter + " days for " + projectName,
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

function fetchTotalCasesGroupwise_LineChart(verticalName, projectName, timeFilter, startDate, endDate, environment1, environment2, groupName1, groupName2) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalCasesGroupwise_Project',
            arguments: [verticalName, projectName, startDate, endDate, environment1, environment2, groupName1, groupName2]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Tag/Group wise total cases for last " + timeFilter + " days [" + projectName + "]",
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