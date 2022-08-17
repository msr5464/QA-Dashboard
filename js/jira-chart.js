var backend = "utils/jira-data.php";
var pageName = "jira.php";

function showDefaultCharts(verticalName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    fetchProdBugsData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
    fetchStgBugsData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive);
}

function showProjectCharts(verticalName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    fetchTotalTicketsTested_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate);
    fetchBugPriorityBreakdown_PieChart(verticalName, projectName, timeFilter, startDate, endDate);
    fetchBugCategoryBreakdown_PieChart(verticalName, projectName, timeFilter, startDate, endDate);
    fetchBugTrend_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate);
}

function fetchStgBugsData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getStgBugsData',
            arguments: [verticalName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            var trendLineCountAvg = 0;
            var trendLinePercAvg = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset1")
                    datasetData1 = value;
                if (key === "dataset2")
                    datasetData2 = value;
                if (key === "totalTicketsTested_sum")
                    resultValue1 = value;
                if (key === "totalStgBugs_sum")
                    resultValue2 = value;
                if (key === "totalProdBugs_sum")
                    resultValue3 = value;
                if (key === "trendLineCountAvg")
                    trendLineCountAvg = value;
                if (key === "trendLinePercAvg")
                    trendLinePercAvg = value;

            document.getElementById("gauge-chart-container1").innerHTML = resultValue1;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = resultValue2;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = resultValue3;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            });
            bugsFoundChart(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Staging' Bugs found", 3);
            bugPercantageChart(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "Staging Bug Ratio (per 100 tickets)", 4);
        }
    });
};

function fetchProdBugsData_ColumnChart(verticalName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProdBugsData',
            arguments: [verticalName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            var trendLineCountAvg = 0;
            var trendLinePercAvg = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset1")
                    datasetData1 = value;
                if (key === "dataset2")
                    datasetData2 = value;
                if (key === "trendLineCountAvg")
                    trendLineCountAvg = value;
                if (key === "trendLinePercAvg")
                    trendLinePercAvg = value;
            });
            bugsFoundChart(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Production' Bugs found", 1);
            bugPercantageChart(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "Production Bug Ratio (per 100 tickets)", 2);
        }
    });
};

function fetchTotalTicketsTested_GaugeChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalTicketsTested_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalTicketsTested")
                        resultValue1 = value;
                    if (key === "totalStgBugs")
                        resultValue2 = value;
                    if (key === "totalProdBugs")
                        resultValue3 = value;
                });
            }
            document.getElementById("gauge-chart-container1").innerHTML = resultValue1;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = resultValue2;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = resultValue3;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
        }
    });
};


function fetchBugPriorityBreakdown_PieChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugPriorityBreakdown_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {

            var chartProperties = {
                "caption": "'Priority wise' Bugs Breakdown for last " + timeFilter + " days [" + projectName + "]",
                "showpercentvalues": "1",
                "defaultcenterlabel": "Bugs Found",
                "aligncaptionwithcanvas": "0",
                "captionpadding": "0",
                "decimals": "1",
                "plottooltext": "$label: $dataValue",
                "centerlabel": "$label: $value",
                "theme": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
            };
            apiChart = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'pie-chart-container1',
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

function fetchBugCategoryBreakdown_PieChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCategoryBreakdown_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {

            var chartProperties = {
                "caption": "'Bugs Found By' Breakdown for last " + timeFilter + " days [" + projectName + "]",
                "showpercentvalues": "1",
                "defaultcenterlabel": "Bugs Found",
                "aligncaptionwithcanvas": "0",
                "captionpadding": "0",
                "decimals": "1",
                "plottooltext": "$label: $dataValue",
                "centerlabel": "$label: $value",
                "theme": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
            };
            apiChart = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'pie-chart-container2',
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

function fetchBugTrend_ColumnChart(verticalName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugTrend_Project',
            arguments: [verticalName, projectName, startDate, endDate]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset1")
                    datasetData1 = value;
                if (key === "dataset2")
                    datasetData2 = value;
            });

            var chartProperties = {
                "caption": "Trend of Bug Percentage for last " + timeFilter + " days [" + projectName + "]",
                "subCaption": "",
                "plottooltext": "$seriesName: $dataValue%",
                "yAxisName": "Percentage",
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
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData2,
                    "categories": categoriesData
                }
            });
            apiChart.render();

            var chartProperties = {
                "caption": "Trend of Bug Count for last " + timeFilter + " days [" + projectName + "]",
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
                renderAt: 'line-chart-container2',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData1,
                    "categories": categoriesData
                }
            });
            apiChart.render();
        }
    });
};

function bugsFoundChart(timeFilter, datasetValue, categoriesValue, trendLineCountAvg, message, chartNum) {
    var chartProperties = {
        "caption": message+" in last " + timeFilter + " days [All Projects]",
        "plottooltext": "$seriesName: $dataValue",
        "yAxisName": "Number of Bugs",
        "drawCrossLine": "1",
        "theme": theme,
        "showValues": "1",
        "showsum": "1",
        "rotatevalues": "0",
        "trendValueFont": "Arial",
        "trendValueFontSize": "13",
        "trendValueFontBold": "1",
        "trendValueFontItalic": "1",
        "trendValueAlpha": "70",
        "trendValueBorderColor": "ff0000",
        "trendValueBorderAlpha": "80",
        "trendValueBorderPadding": "2",
        "trendValueBorderRadius": "3",
        "trendValueBorderThickness": "2",
        "trendValueBorderDashed": "0",
        "trendValueBorderDashLen": "#123456",
        "trendValueBorderDashGap": "1",
        "toolTipBgcolor": "#484E69",
        "toolTipPadding": "7",
        "toolTipBorderRadius": "3",
        "toolTipBorderAlpha": "30",
        "tooltipBorderThickness": "0.7",
        "toolTipColor": "#FDFDFD"
    };
    if(isPodDataActive == '1')
    {
        chartProperties.captionFontSize = "22";
        chartProperties.valueFontSize = "22";
        chartProperties.labelFontSize = "16";
    }

    apiChart = new FusionCharts({
        type: 'stackedcolumn2d',
        renderAt: 'column-chart-container'+chartNum,
        width: '96%',
        height: '400',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "dataset": datasetValue,
            "categories": categoriesValue,
            "trendlines": [{
                "line": [{
                  "startvalue": trendLineCountAvg,
                  "displayvalue": "Avg: "+trendLineCountAvg,
                  "valueOnRight": "0",
                  "thickness": "2",
                  "dashed": "1",
                  "alpha": "70",
                  "tooltext": "Average: $startvalue"
                }]
            }]
        }
    });
    apiChart.render();
}

function bugPercantageChart(timeFilter, datasetValue, categoriesValue, trendLinePercAvg, message, chartNum) {
    var chartProperties = {
        "caption": message+" for last " + timeFilter + " days [All Projects]",
        "plottooltext": "$seriesName: $dataValue%",
        "yAxisName": "Percentage",
        "drawCrossLine": "1",
        "rotatevalues": "0",
        "theme": theme === "fusion" ? "zune": theme,
        "showValues": "1",
        "trendValueFont": "Arial",
        "trendValueFontSize": "13",
        "trendValueFontBold": "1",
        "trendValueFontItalic": "1",
        "trendValueAlpha": "70",
        "trendValueBorderColor": "ff0000",
        "trendValueBorderAlpha": "80",
        "trendValueBorderPadding": "2",
        "trendValueBorderRadius": "3",
        "trendValueBorderThickness": "2",
        "trendValueBorderDashed": "0",
        "trendValueBorderDashLen": "#123456",
        "trendValueBorderDashGap": "1",
        "toolTipBgcolor": "#484E69",
        "toolTipPadding": "7",
        "toolTipBorderRadius": "3",
        "toolTipBorderAlpha": "30",
        "tooltipBorderThickness": "0.7",
        "toolTipColor": "#FDFDFD"
    };
    if(isPodDataActive == '1')
    {
        chartProperties.captionFontSize = "22";
        chartProperties.valueFontSize = "22";
        chartProperties.labelFontSize = "16";
    }

    apiChart = new FusionCharts({
        type: 'mscombi3d',
        renderAt: 'column-chart-container'+chartNum,
        width: '96%',
        height: '400',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "dataset": datasetValue,
            "categories": categoriesValue,
            "trendlines": [{
                "line": [{
                  "startvalue": trendLinePercAvg,
                  "displayvalue": "Avg: "+trendLinePercAvg,
                  "valueOnRight": "0",
                  "thickness": "2",
                  "dashed": "1",
                  "alpha": "70",
                  "tooltext": "Average: $startvalue"
                }]
            }]
        }
    });
    apiChart.render();
}
