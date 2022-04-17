var backend = "utils/testrail-data.php";
var pageName = "testrail.php";

function showDefaultCharts(verticalName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    fetchCoverageNumbers_GaugeChart_All(verticalName, startDate, endDate, isPodDataActive);
    fetchAutomatedCountChange_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
    //fetchP0CoverageChange_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
    //fetchP1CoverageChange_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
    fetchFullCoverageData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
    fetchTestcaseDistribution_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
}

function showProjectCharts(verticalName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    fetchCoverageNumbers_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate);
    fetchTotalvsAutomatedCount_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate);
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
                    if (key === "totalCoverage")
                        resultValue1 = value;
                    if (key === "P0Coverage")
                        resultValue2 = value;
                    if (key === "P1Coverage")
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


function fetchAutomatedCountChange_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAutomatedCountChange',
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
                "caption": "Count of testcases automated in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue cases",
                "yAxisName": "Testcase Count",
                "divlineColor": "#999999",
                "divLineDashed": "1",
                "theme": "fusion",
                "showValues": "1",
                "showsum": "1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2dline',
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


function fetchP0CoverageChange_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getP0CoverageChange',
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
                "caption": "Change in P0 Automation Percentage in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue%",
                "yAxisName": "Percentage",
                "divlineColor": "#999999",
                "divLineDashed": "1",
                "theme": "fusion",
                "showValues": "1",
                "showsum": "1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2dline',
                renderAt: 'column-chart-container2',
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

function fetchP1CoverageChange_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getP1CoverageChange',
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
                "caption": "Change in P1 Automation Percentage in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue%",
                "yAxisName": "Percentage",
                "divlineColor": "#999999",
                "divLineDashed": "1",
                "theme": "fusion",
                "showValues": "1",
                "showsum": "1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2dline',
                renderAt: 'column-chart-container3',
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
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var today = new Date().toISOString().slice(0, 10);
            if(today === endDate)
                endDate = "'today'";
            var chartProperties = {
                "caption": "Automation Coverage Percentage as on " + endDate + " [All Projects]",
                "plottooltext": "$label: $dataValue% automated",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "1",
                "showvalues": "1",
                numbersuffix: "%",
                "flatscrollbars": "0",
                "scrollheight": "10",
                "scrollColor": "#fff",
                "numvisibleplot": "50",
                "drawCrossLine": "1",
                "theme": "fusion"
            };

            apiChart = new FusionCharts({
                type: 'scrollcolumn2d',
                renderAt: 'column-chart-container4',
                width: '96%',
                height: '450',
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

function fetchTestcaseDistribution_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestcaseCountDistribution',
            arguments: [verticalName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            var resultValue2 = 0;
            var resultValue3 = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var today = new Date().toISOString().slice(0, 10);
            if(today === endDate)
                endDate = "'today'";
            var chartProperties = {
                "caption": "Overall Testcase Distribution as on " + endDate + " [All Projects]",
                "placevaluesinside": "0",
                "showvalues": "0",
                "drawCrossLine": "1",
                "plottooltext": "$seriesName: $dataValue",
                "theme": "fusion",
                "showsum": "1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2d',
                renderAt: 'column-chart-container5',
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
                    if (key === "P0Coverage")
                        resultValue2 = value;
                    if (key === "P1Coverage")
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

function fetchTotalvsAutomatedCount_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalvsAutomatedCount_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var today = new Date().toISOString().slice(0, 10);
            if(today === endDate)
                endDate = "'today'";
            var chartProperties = {
                "caption": "Already Automated vs Total Automation cases as on " + endDate + " [" + projectName + "]",
                "yAxisName": "Testcase Count",
                "plottooltext": "$seriesName - $dataValue",
                "showValues": "1",
                "theme": "fusion"
            };
            apiChart = new FusionCharts({
                type: 'overlappedColumn2d',
                renderAt: 'column-chart-container6',
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
                "caption": "Trend of Testcase Count for last " + timeFilter + " days [" + projectName + "]",
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