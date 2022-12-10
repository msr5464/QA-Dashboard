var backend = "utils/units-data.php";
var pageName = "units.php";

function showDefaultCharts(verticalName, tableName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    fetchCoverageNumbers_GaugeChart_All(tableName, startDate, endDate, isPodDataActive);
    fetchCoverageDelta_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
    fetchFullCoverageData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
}

function showProjectCharts(verticalName, tableName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    fetchCoverageNumbers_GaugeChart(tableName, projectName, timeFilter, startDate, endDate);
    fetchLastSevenResults_ColumnChart(tableName, projectName, timeFilter, startDate, endDate);
    fetchTestcaseCountTrend_LineChart(tableName, projectName, timeFilter, startDate, endDate);
}

function fetchCoverageNumbers_GaugeChart_All(tableName, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageNumbers_All',
            arguments: [tableName, startDate, endDate,isPodDataActive]
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
                "plottooltext": "$label [based on number of Lines]: $dataValue%",
                "showBorder": "0",
                "captionfontcolor": "#686980",
                "captionfontsize": "15",
                "captionfont": "avenir-heavy",
                "captionalignment": "left",
                "showShadow": "0",
                "use3DLighting": "0",
                "showLabels": "0",
                "showValues": "0",
                "baseFontColor": fontColor,
                "bgAlpha": "0",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "canvasBgAlpha": "0",
                "doughnutRadius": "50",
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
                width: '96%',
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
        }
    });
};


function fetchCoverageDelta_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageDelta',
            arguments: [tableName, startDate, endDate, isPodDataActive]
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
                "theme": theme,
                "showValues": "1",
                "showsum": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
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


function fetchFullCoverageData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getFullCoverageData',
            arguments: [tableName, startDate, endDate, isPodDataActive]
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
                "theme": theme === "fusion" ? "zune": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
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


function fetchCoverageNumbers_GaugeChart(tableName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageNumbers_Project',
            arguments: [tableName, projectName, startDate, endDate]
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
                "theme": theme,
                "showToolTip": "1",
                "plottooltext": "Line Coverage: $dataValue",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"

            };

            apiChart1 = new FusionCharts({
                type: 'angulargauge',
                renderAt: 'gauge-chart-container1',
                width: '96%',
                height: '200',
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
        }
    });
};

function fetchLastSevenResults_ColumnChart(tableName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getLast7Records',
            arguments: [tableName, projectName, startDate, endDate]
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
                "theme": theme,
                "scrollheight": "12",
                "flatScrollBars": "1",
                "scrollShowButtons": "1",
                "numvisibleplot": "15",
                "drawCrossLine": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
            };

            apiChart = new FusionCharts({
                type: 'scrollcolumn2d',
                renderAt: 'column-chart-container3',
                width: '96%',
                height: '350',
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
                            categoryLabel = categoryLabel.substring(categoryLabel.lastIndexOf("\n") + 1);
                            var fullLabel = fullData.find(v => v.label.toString().includes(categoryLabel)).label;
                            var resultLink = fullLabel.split(linkSeperator)[1];

                            var allresultLink = resultLink.split(";");
                            if(allresultLink.length > 1)
                            {
                                var msgToReturn = '<B>"' + categoryLabel + '"</B> - This build contains data from multiple modules, here are the links for all :-';
                                for (i = 0; i < allresultLink.length; i++) {
                                    msgToReturn = msgToReturn + '<br> <a style="color:yellow" href="' + allresultLink[i] + '" target="_blank"> Results Link-' + (i+1) + '</a>';
                                }
                                return msgToReturn;
                            }
                            else
                            {
                                return 'Results Link for <B>"' + categoryLabel + '"</B> - <a style="color:yellow" href="' + resultLink + '" target="_blank">' + resultLink + '</a>';
                            }
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



function fetchTestcaseCountTrend_LineChart(tableName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestcaseCountTrend_Project',
            arguments: [tableName, projectName, startDate, endDate]
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
                "plottooltext": "$seriesName: $dataValue",
                "yAxisName": "Count",
                "theme": theme,
                "showValues": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
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