var backend = "utils/units-data.php";
var pageName = "units.php";

function showDefaultCharts(verticalName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    fetchCoverageNumbers_GaugeChart_All(verticalName, startDate, endDate, isPodDataActive);
    fetchCoverageDelta_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
    fetchFullCoverageData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
}

function showProjectCharts(verticalName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    fetchCoverageNumbers_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate);
    fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate);
    fetchTestcaseCountTrend_LineChart(verticalName, projectName, timeFilter, startDate, endDate);
}

function fetchCoverageNumbers_GaugeChart_All(verticalName, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageNumbers_All',
            arguments: [verticalName, startDate, endDate,isPodDataActive]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "coverage-data")
                        resultValue1 = value;
                        resultValue2 = value;
                        resultValue3 = value;
                });
            }

            var chartProperties = {
                "plottooltext": "$label: $dataValue%",
                "showBorder": "0",
                "captionfontcolor": "#686980",
                "captionfontsize": "15",
                "captionfont": "avenir-heavy",
                "captionalignment": "left",
                "showShadow": "0",
                "use3DLighting": "0",
                "showLabels": "0",
                "showValues": "0",
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
    });
};


function fetchCoverageDelta_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageDelta',
            arguments: [verticalName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Change in Unit Tests Coverage Percentage in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue%",
                "yAxisName": "Percentage",
                "divlineColor": "#999999",
                "divLineDashed": "1",
                "theme": "candy",
                "showValues": "1",
                "showsum": "1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2d',
                renderAt: 'column-chart-container1',
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


function fetchFullCoverageData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getFullCoverageData',
            arguments: [verticalName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "FullCoverage")
                    fullCoverageValue = value;
            });

            var today = new Date().toISOString().slice(0, 10);
            if(today === endDate)
                endDate = "'today'";
            var chartProperties = {
                "caption": "Unit Tests Coverage Percentage as on " + endDate + " [All Projects]",
                "plottooltext": "$label: $dataValue%",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "0",
                "showValues": "1",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container2',
                width: '96%',
                height: '350',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": fullCoverageValue
                }
            });
            apiChart.render();
        }
    });
};


function fetchCoverageNumbers_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageNumbers_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalCoverage")
                        resultValue1 = value;
                        resultValue2 = value;
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
                width: '92%',
                height: '150',
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
                width: '92%',
                height: '150',
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
                width: '92%',
                height: '150',
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

function fetchLastSevenResults_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getLast7Records',
            arguments: [verticalName, projectName, startDate, endDate]
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
                "caption": "Code Coverage builds for last " + timeFilter + " days [" + projectName + "]",
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
                renderAt: 'column-chart-container3',
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



function fetchTestcaseCountTrend_LineChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestcaseCountTrend_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Trend of Unit Test Coverage for last " + timeFilter + " days [" + projectName + "]",
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Count",
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