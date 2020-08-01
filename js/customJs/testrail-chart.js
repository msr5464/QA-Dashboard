var backend = "server/testrail-data.php";

function showDefaultCharts(timeFilter) {
    hideProjectCharts();
    fetchTotalAutomationCoverage_ColumnChart(timeFilter);
    fetchTotalP0Coverage_ColumnChart(timeFilter);
    fetchTotalP1Coverage_ColumnChart(timeFilter);
    //fetchTotalP2Coverage_ColumnChart(timeFilter);
    fetchAutomatedCountChange_ColumnChart(timeFilter);
    fetchP0CoverageChange_ColumnChart(timeFilter);
    fetchP1CoverageChange_ColumnChart(timeFilter);
    fetchTestcaseDistribution_ColumnChart(timeFilter);

}

function showProjectCharts(projectName, timeFilter) {
    hideDefaultCharts();
    fetchCoverageNumbers_GaugeChart(projectName, timeFilter);
    fetchTestCasesBreakdown_PieChart(projectName, timeFilter);
    fetchTotalvsAutomatedCount_ColumnChart(projectName, timeFilter);
    fetchTestcaseCountTrend_LineChart(projectName, timeFilter);
}

function fetchTotalAutomationCoverage_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalAutomationCoverage'
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Full Automation Coverage Percentage [All Projects]",
                "plottooltext": "$label: $dataValue% automated",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "0",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container1',
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

function fetchTotalP0Coverage_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalP0Coverage'
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Only P0 Automation Coverage Percentage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "0",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container2',
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

function fetchTotalP1Coverage_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalP1Coverage'
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Only P1 Automation Coverage Percentage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "0",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container3',
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

function fetchTotalP2Coverage_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalP2Coverage'
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Only P2 Automation Coverage Percentage [All Projects]",
                "plottooltext": "$seriesName: $dataValue%",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "0",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container4',
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


function fetchAutomatedCountChange_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAutomatedCountChange',
            arguments: [timeFilter]
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
                renderAt: 'column-chart-container5',
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


function fetchP0CoverageChange_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getP0CoverageChange',
            arguments: [timeFilter]
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
                renderAt: 'column-chart-container6',
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

function fetchP1CoverageChange_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getP1CoverageChange',
            arguments: [timeFilter]
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
                renderAt: 'column-chart-container7',
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

function fetchTestcaseDistribution_ColumnChart(timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestcaseCountDistribution',
            arguments: [timeFilter]
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

            var chartProperties = {
                "caption": "Overall Testcase Distribution in Testrail [All Projects]",
                "placevaluesinside": "0",
                "showvalues": "0",
                "plottooltext": "$seriesName: $dataValue",
                "theme": "fusion",
                "showsum": "1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2d',
                renderAt: 'column-chart-container8',
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

function fetchCoverageNumbers_GaugeChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getCoverageNumbers_Project',
            arguments: [projectName, timeFilter]
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
                width: '88%',
                height: '160',
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
                width: '88%',
                height: '160',
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
                width: '88%',
                height: '160',
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

function fetchTestCasesBreakdown_PieChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestCasesBreakdown_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalCases")
                        resultValue1 = value;
                    if (key === "fullResult")
                        resultValue2 = value;
                });
            }

            var chartProperties = {
                "caption": "Testcases Breakdown in tags for " + projectName,
                "showpercentvalues": "1",
                "defaultcenterlabel": "Automation Testcases",
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
                    "data": resultValue2
                },
                "events": {
                    "beforeInitialize": function () {
                        if (resultValue1) {
                            var passPercentage = resultValue1[1].value;
                            chartProperties.defaultcenterlabel = "Total Cases: " + resultValue1;
                        }
                    }
                }
            });
            apiChart.render();
        }
    });
};

function fetchTotalvsAutomatedCount_ColumnChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalvsAutomatedCount_Project',
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
                "caption": "Already Automated vs Total Automation cases for " + projectName,
                "yAxisName": "Testcase Count",
                "plottooltext": "$seriesName - $dataValue",
                "showValues": "1",
                "theme": "fusion"
            };
            apiChart = new FusionCharts({
                type: 'overlappedColumn2d',
                renderAt: 'column-chart-container9',
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

function fetchTestcaseCountTrend_LineChart(projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestcaseCountTrend_Project',
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
                "caption": "Trend of Testcase Count for last " + timeFilter + " days for " + projectName,
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Total Testcases",
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