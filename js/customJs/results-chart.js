var backend = "server/results-data.php";
var pageName = "results.php";

function showDefaultCharts(verticalName, timeFilter) {
    hideProjectCharts();
    fetchAvgPercentageForRegression_ColumnChart(verticalName, timeFilter);
    fetchAvgPercentageForSanity_ColumnChart(verticalName, timeFilter);
    fetchAvgPercentageForProduction_ColumnChart(verticalName, timeFilter);
    fetchAvgExecutionTimeForRegression_ColumnChart(verticalName, timeFilter);
    fetchAvgExecutionTimeForSanity_ColumnChart(verticalName, timeFilter);
    fetchAvgExecutionTimeForProduction_ColumnChart(verticalName, timeFilter);
}

function showProjectCharts(verticalName, projectName, timeFilter) {
    hideDefaultCharts();
    fetchAvgPercentage_GaugeChart(verticalName, projectName, timeFilter);
    fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter);
    fetchAvgDailyPercentage_LineChart(verticalName, projectName, timeFilter);
    fetchAvgDailyExecutionTime_LineChart(verticalName, projectName, timeFilter);
    fetchTotalCasesGroupwise_LineChart(verticalName, projectName, timeFilter);
}

function fetchAvgPercentageForRegression_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [verticalName, timeFilter, 'environment1', 'regression']
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
                renderAt: 'column-chart-container1',
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

function fetchAvgPercentageForSanity_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [verticalName, timeFilter, 'environment1', 'sanity']
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
                renderAt: 'column-chart-container2',
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

function fetchAvgPercentageForProduction_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [verticalName, timeFilter, 'environment2', 'production']
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
                renderAt: 'column-chart-container3',
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

function fetchAvgExecutionTimeForRegression_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [verticalName, timeFilter, 'environment1', 'regression']
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
                "caption": "Average '"+groupNameValue+"' execution time for last " + timeFilter + " days [on "+environmentValue+"]",
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
                    "data": resultsData
                }
            });
            apiChart.render();
        }
    });
};

function fetchAvgExecutionTimeForSanity_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [verticalName, timeFilter, 'environment1', 'sanity']
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
                "caption": "Average '"+groupNameValue+"' execution time for last " + timeFilter + " days [on "+environmentValue+"]",
                "yAxisName": "Time Taken (in minutes)",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue minutes",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container5',
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

function fetchAvgExecutionTimeForProduction_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [verticalName, timeFilter, 'environment2', 'production']
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
                "caption": "Average '"+groupNameValue+"' execution time for last " + timeFilter + " days [on "+environmentValue+"]",
                "yAxisName": "Time Taken (in minutes)",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue minutes",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container6',
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

function fetchAvgPercentage_GaugeChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage_Project',
            arguments: [verticalName, projectName, timeFilter, "'regression','production'"]
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

function fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getLast7Records',
            arguments: [verticalName, projectName, "30"]
        },
        success: function (result) {
            var linkSeperator = ", link- ";
            const fullData = JSON.parse(JSON.stringify(result));

            var chartProperties = {
                "caption": "Details of last " + 30 + " Automation Builds for " + projectName,
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": "ocean"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container7',
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

function fetchAvgDailyPercentage_LineChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgPercentage_Project',
            arguments: [verticalName, projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily percentage for last " + timeFilter + " days for " + projectName,
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

function fetchAvgDailyExecutionTime_LineChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgExecutionTime_Project',
            arguments: [verticalName, projectName, timeFilter]
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

function fetchTotalCasesGroupwise_LineChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalCasesGroupwise_Project',
            arguments: [verticalName, projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Tag/Group wise total cases for last " + timeFilter + " days for " + projectName,
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