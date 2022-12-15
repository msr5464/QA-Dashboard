var backend = "utils/testrail-data.php";
var pageName = "testrail.php";

$(document).ready(function () {
    $("#goButton").click(function () {
        setDataIntoStorage("country",$("#countryDropdown").val());
        setDataIntoStorage("platform",$("#platformDropdown").val());
    });
    $("#addFiltersButton").click(function () {
        showDropdowns();
        $("#addFiltersButton").hide();
    });
});

function showDropdowns() {
    $("#selectProject").show();
    $("#projectNamesDropdown").val(projectName.replaceAll("'","").split(',')).trigger("chosen:updated");
    $("#countryDropdown").addClass("chosen-select").val(getDataFromStorage("country").split(',')).show().chosen();
    $("#platformDropdown").addClass("chosen-select").val(getDataFromStorage("platform").split(',')).show().chosen();
}

function showDefaultCharts(verticalName, tableName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    fetchCoverageNumbers_GaugeChart_All(tableName, startDate, endDate, isPodDataActive);
    fetchAutomatedCountChange_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
    //fetchP0CoverageChange_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
    //fetchP1CoverageChange_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
    fetchFullCoverageData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
    fetchTestcaseDistribution_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
}

function showProjectCharts(verticalName, tableName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    fetchCoverageNumbers_GaugeChart(tableName, projectName, timeFilter, startDate, endDate);
    fetchTotalvsAutomatedCount_ColumnChart(tableName, projectName, timeFilter, startDate, endDate);
    fetchTestcaseCountTrend_LineChart(tableName, projectName, timeFilter, startDate, endDate);
    //window.setTimeout('$("#addFiltersButton").show();', 1000); - Uncomment it to enable the dropdowns
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
            var resultValue4 = 0;
            var resultValue5 = 0;
            var resultValue6 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "FullCoverage-data")
                        resultValue1 = value;
                    if (key === "P0Coverage-data")
                        resultValue2 = value;
                    if (key === "P1Coverage-data")
                        resultValue3 = value;
                    if (key === "FullAutomation-data")
                        resultValue4 = value;
                    if (key === "P0Automation-data")
                        resultValue5 = value;
                    if (key === "P1Automation-data")
                        resultValue6 = value;
                });
            }
            enableGaugeChart(resultValue1, 1);
            enableGaugeChart(resultValue2, 2);
            enableGaugeChart(resultValue3, 3);
            enableLinearChart(resultValue4, 1);
            enableLinearChart(resultValue5, 2);
            enableLinearChart(resultValue6, 3);
        }
    });
};

function fetchAutomatedCountChange_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAutomatedCountChange',
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
                "caption": "Count of New testcases automated in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue cases",
                "yAxisName": "Testcase Count",
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
                "palettecolors": "5c70cc,70cc5c,f2726f",
                "toolTipColor": "#FDFDFD"
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


function fetchP0CoverageChange_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getP0CoverageChange',
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
                "caption": "Change in P0 Automation Percentage in last " + timeFilter + " days [All Projects]",
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

function fetchP1CoverageChange_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getP1CoverageChange',
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
                "caption": "Change in P1 Automation Percentage in last " + timeFilter + " days [All Projects]",
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
                "placevaluesinside": "0",
                "rotatevalues": "0",
                "showvalues": "1",
                "numbersuffix": "%",
                "scrollheight": "12",
                "flatScrollBars": "1",
                "scrollShowButtons": "1",
                "numvisibleplot": "50",
                "drawCrossLine": "1",
                "theme": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"

            };

            apiChart = new FusionCharts({
                type: 'scrollcolumn2d',
                renderAt: 'column-chart-container4',
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

function fetchTestcaseDistribution_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTestcaseCountDistribution',
            arguments: [tableName, startDate, endDate, isPodDataActive]
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
                "theme": theme,
                "showsum": "1",
                "scrollheight": "12",
                "flatScrollBars": "1",
                "scrollShowButtons": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
            };

            apiChart = new FusionCharts({
                type: 'scrollstackedcolumn2d',
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
            var resultValue4 = 0;
            var resultValue5 = 0;
            var resultValue6 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "FullCoverage-data")
                        resultValue1 = value;
                    if (key === "P0Coverage-data")
                        resultValue2 = value;
                    if (key === "P1Coverage-data")
                        resultValue3 = value;
                    if (key === "FullAutomation-data")
                        resultValue4 = value;
                    if (key === "P0Automation-data")
                        resultValue5 = value;
                    if (key === "P1Automation-data")
                        resultValue6 = value;
                });
            }
            enableGaugeChart(resultValue1, 1);
            enableGaugeChart(resultValue2, 2);
            enableGaugeChart(resultValue3, 3);
            enableLinearChart(resultValue4, 1);
            enableLinearChart(resultValue5, 2);
            enableLinearChart(resultValue6, 3);
        }
    });
};

function fetchTotalvsAutomatedCount_ColumnChart(tableName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalvsAutomatedCount_Project',
            arguments: [tableName, projectName, startDate, endDate]
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
                "plottooltext": "$seriesName: $dataValue",
                "showValues": "1",
                "theme": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
            };
            apiChart = new FusionCharts({
                type: 'overlappedColumn2d',
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
                "caption": "Trend of Testcase Count for last " + timeFilter + " days [" + projectName + "]",
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

function enableLinearChart(result, placeholderNum) {

    for (i = 0; i < result.length; i++) {
        $.each(result[i], function (key, value) {
            if (key === "automatableCases")
                totalTestcases = value;
            if (key === "alreadyAutomated")
                alreadyAutomated = value;
        });
    }

    var chartProperties = {
        "showValues": "0",
        "showBorder": "0",
        "baseFont": "Nunito Sans",
        "showTickMarks": "0",
        "showTickValues": "0",
        "pointerRadius": "0",
        "pointerBgAlpha": "0",
        "pointerBorderAlpha": "0",
        "gaugeFillMix": "{light+0}",
        "showValue": "0",
        "showGaugeBorder": "0",
        "chartTopMargin": "15",
        "chartBottomMargin": "25",
        "chartLeftMargin": "5",
        "chartRightMargin": "5",
        "transposeAnimation":"1",
        "baseFontColor": "#ffffff",
        "bgAlpha": "0"
    };

    apiChart1 = new FusionCharts({
            type: "hlineargauge",
            dataFormat: "JSON",
            width: "80%",
            height: "50",
            renderAt: 'linear-chart-container'+placeholderNum,
            dataSource: {
            "chart": chartProperties,
            "colorRange": {
                "color": [{
                    "minValue": "0",
                    "maxValue": alreadyAutomated,
                    "code": "#92C35F"
                },
                {
                    "minValue": alreadyAutomated,
                    "maxValue": totalTestcases,
                    "code": "#48526F"
                }
                ]
            },
            "annotations": {
                "groups": [{
                    "items": [
                        {
                        "id": "1",
                        "type": "text",
                        "text": "Already Automated",
                        "fontSize": "11",
                        "color": fontColor,
                        "align": "Left",
                        "x": "$canvasStartX",
                        "y": "$canvasStartY - 10"
                    }, 
                    {
                        "id": "3",
                        "type": "text",
                        "text": "Total Automatable",
                        "fontSize": "11",
                        "bold": "0",
                        "color": fontColor,
                        "align": "Right",
                        "x": "$canvasEndX",
                        "y": "$canvasStartY - 10"
                    }, {
                        "id": "4",
                        "type": "text",
                        "text": alreadyAutomated + " tests",
                        "fontSize": "14",
                        "font": "arial black",
                        "bold": "1",
                        "color": fontColor,
                        "align": "Left",
                        "x": "$canvasStartX",
                        "y": "$canvasEndY + 13"
                    }, {
                        "id": "5",
                        "type": "text",
                        "text": totalTestcases + " tests",
                        "fontSize": "14",
                        "font": "arial black",
                        "bold": "1",
                        "color": fontColor,
                        "align": "Right",
                        "x": "$canvasEndX",
                        "y": "$canvasEndY + 13"
                    }]
                }]
            }
        }
    });
    apiChart1.render();
};

function enableGaugeChart(result, placeholderNum)
{
    var chartProperties = {
        "plottooltext": "$label: $dataValue%",
        "showBorder": "0",
        "showShadow": "0",
        "use3DLighting": "0",
        "showLabels": "0",
        "showValues": "0",
        "paletteColors": "#efefef,#11DFF6",
        "baseFontColor": fontColor,
        "bgAlpha": "0",
        "toolTipBgcolor": "#484E69",
        "toolTipPadding": "7",
        "toolTipBorderRadius": "3",
        "toolTipBorderAlpha": "30",
        "tooltipBorderThickness": "0.7",
        "toolTipColor": "#FDFDFD",
        "canvasBgAlpha": "0",
        "doughnutRadius": "46",
        "pieRadius": "60",
        "enableSlicing": "0",
        "plotBorderAlpha": "0",
        "showToolTip": "1",
        "baseFontSize": "14",
        "defaultCenterLabel": null,
        "centerLabelBold": "1",
        "centerLabelFontSize": "24",
        "enableRotation": "0",
        "captionfont": "avenir-heavy",
        "baseFont": "avenir-medium",
        "startingAngle": "90",
        "chartTopMargin": "25",
        "animateClockwise": "1"
    };

    apiChart = new FusionCharts({
        type: 'doughnut2d',
        renderAt: 'gauge-chart-container'+placeholderNum,
        width: '92%',
        height: '130',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "data": result
        },
        "events": {
            "beforeInitialize": function () {
                if (result) {
                    var passPercentage = result[1].value;
                    chartProperties.defaultCenterLabel = passPercentage + "%";
                }
            }
        }
    });
    apiChart.render();
}