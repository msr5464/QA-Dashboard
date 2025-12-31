var backend = "utils/data/bugs/bugs-total-data.php";
var pageName = "bugs-total.php";

$(document).ready(function () {
    $("#goButton").click(function () {
        setDataIntoStorage("bugCategory", $("#bugCategoryDropdown").val());
    });
    $("#addFiltersButton").click(function () {
        showDropdowns();
        $("#addFiltersButton").hide();
    });
});

function showDropdowns() {
    $("#selectProject").show();
    $("#projectNamesDropdown").val(projectName.replaceAll("'", "").split(',')).trigger("chosen:updated");
    $("#bugCategoryDropdown").addClass("chosen-select").val(getDataFromStorage("bugCategory").split(',')).show().chosen();
}

function showDefaultCharts(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    hideProjectCharts();
    fetchTotalBugsCount_GaugeChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchProdBugsData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchQualityScore_PRD(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchFctBugsData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchQualityScore_FCT(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchStgBugsData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchQualityScore_STG(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
}

function showProjectCharts(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    $("#issuesBody").empty();
    bugCategory = getDataFromStorage("bugCategory");
    if (bugCategory == null || bugCategory == "") {
        bugCategory = "STG,FCT,PRD";
        setDataIntoStorage("bugCategory", bugCategory);
    }
    fetchTotalBugsCount_GaugeChart_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate);
    fetchProductAreaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory);
    fetchBugFoundByData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory);
    fetchTeamNameData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory);
    showIssuesList_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory);
    fetchProdBugLeakage_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate);
    fetchProdBugLeakage_LineChart_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate);
    getBugCountTrendByPriority_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory);
    getBugRatioTrend_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory);
    fetchQualityScoreTrend_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate, bugCategory);
    window.setTimeout('$("#addFiltersButton").show();', 1000);
}

function fetchTotalBugsCount_GaugeChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalBugsCount',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive]
        },
        success: function (result) {
            var resultValue2 = 0;
            var resultValue3 = 0;
            var resultValue4 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalStgBugs")
                        resultValue2 = value;
                    if (key === "totalFctBugs")
                        resultValue3 = value;
                    if (key === "totalPrdBugs")
                        resultValue4 = value;
                });
            }
            var totalBugs = parseInt(resultValue2 || 0) + parseInt(resultValue3 || 0) + parseInt(resultValue4 || 0);
            document.getElementById("gauge-chart-container1").innerHTML = totalBugs;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = resultValue4;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = resultValue3;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            document.getElementById("gauge-chart-container4").innerHTML = resultValue2;
            document.getElementById("gauge-chart-container4").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container4").classList.add('bigFont');
        }
    });
};

function fetchStgBugsData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountAndPercentageData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive, 'STG']
        },
        success: function (result) {
            var resultValue1 = 0;
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
            bugsFoundChartWithTrendLine(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Staging' Bugs found", 11, startDate, endDate);
            bugRatioChartWithTrendLine(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "'Staging' Bug Ratio w.r.t Tickets Tested", 12, startDate, endDate);
        }
    });
};

function fetchFctBugsData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountAndPercentageData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive, 'FCT']
        },
        success: function (result) {
            var resultValue1 = 0;
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
            bugsFoundChartWithTrendLine(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Regression' Bugs found", 4, startDate, endDate);
            bugRatioChartWithTrendLine(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "'Regression' Bug Ratio w.r.t Tickets Tested", 5, startDate, endDate);
        }
    });
};

function fetchProdBugsData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountAndPercentageData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive, 'PRD']
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
            bugsFoundChartWithTrendLine(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Production' Bugs found", 1, startDate, endDate);
            bugRatioChartWithTrendLine(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "'Production' Bug Ratio w.r.t Tickets Tested", 2, startDate, endDate);
        }
    });
};

function fetchProdBugLeakage_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProdBugLeakage_Project',
            arguments: [tableNamePrefix, projectName, startDate, endDate]
        },
        success: function (result) {
            var categoriesData = null;
            var datasetData = null;
            // The backend returns { categories: [...], dataset: [...] }
            if (result.categories) categoriesData = result.categories;
            if (result.dataset) datasetData = result.dataset;

            var chartProperties = {
                "caption": "Production Bugs Leakage % for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
                "plottooltext": "$seriesName: $dataValue%",
                "yAxisName": "Bug Leakage %",
                "drawCrossLine": "1",
                "theme": theme === "fusion" ? "zune" : theme,
                "showValues": "1",
                "numbersuffix": "%",
                "showsum": "0",
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

            apiChart = new FusionCharts({
                type: 'stackedcolumn2d',
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
}

function fetchTotalBugsCount_GaugeChart_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalBugsCount_Project',
            arguments: [tableNamePrefix, projectName, startDate, endDate]
        },
        success: function (result) {
            var resultValue2 = 0;
            var resultValue3 = 0;
            var resultValue4 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalStgBugs")
                        resultValue2 = value;
                    if (key === "totalFctBugs")
                        resultValue3 = value;
                    if (key === "totalPrdBugs")
                        resultValue4 = value;
                });
            }
            var totalBugs = parseInt(resultValue2 || 0) + parseInt(resultValue3 || 0) + parseInt(resultValue4 || 0);
            document.getElementById("gauge-chart-container1").innerHTML = totalBugs;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = resultValue4;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = resultValue3;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            document.getElementById("gauge-chart-container4").innerHTML = resultValue2;
            document.getElementById("gauge-chart-container4").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container4").classList.add('bigFont');
        }
    });
};



function bugsFoundChartWithTrendLine(timeFilter, datasetValue, categoriesValue, trendLineCountAvg, message, chartNum, startDate, endDate) {
    var chartProperties = {
        "caption": message + " in last " + timeFilter + " days [All Projects]",
        "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
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

    apiChart = new FusionCharts({
        type: 'stackedcolumn2d',
        renderAt: 'column-chart-container' + chartNum,
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
                    "displayvalue": "Avg: " + trendLineCountAvg,
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

function bugRatioChartWithTrendLine(timeFilter, datasetValue, categoriesValue, trendLineAvg, message, chartNum, startDate, endDate) {
    var chartProperties = {
        "caption": message + " in last " + timeFilter + " days [All Projects]",
        "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
        "plottooltext": "$seriesName: $dataValue",
        "yAxisName": "Bug Ratio",
        "drawCrossLine": "1",
        "theme": theme === "fusion" ? "zune" : theme,
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

    apiChart = new FusionCharts({
        type: 'mscombi3d',
        renderAt: 'column-chart-container' + chartNum,
        width: '96%',
        height: '400',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "dataset": datasetValue,
            "categories": categoriesValue,
            "trendlines": [{
                "line": [{
                    "startvalue": trendLineAvg,
                    "displayvalue": "Avg: " + trendLineAvg,
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

function fetchTeamNameData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, "teamName", bugCategory]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugsFoundChart(timeFilter, datasetData, categoriesData, bugCategory.replaceAll(',', ' & ') + " - 'Team Name' segregation for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 8, startDate, endDate);
        }
    });
};

function fetchProductAreaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, "productArea", bugCategory]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugsFoundChart(timeFilter, datasetData, categoriesData, bugCategory.replaceAll(',', ' & ') + " - 'Product Area' segregation for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 7, startDate, endDate);
        }
    });
};

function fetchBugFoundByData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, "bugFoundBy", bugCategory]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugsFoundChart(timeFilter, datasetData, categoriesData, bugCategory.replaceAll(',', ' & ') + " - 'Bug Found By' segregation for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 10, startDate, endDate);
        }
    });
};

// bugsFoundChart is now defined in common-chart-functions.js


function showIssuesList_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getIssuesList_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, bugCategory]
        },
        success: function (result) {
            $.each(result, function (index, value) {
                tableColumns = "";
                $.each(value, function (indexi, valuei) {
                    if (indexi == "issueId")
                        valuei = "<a target='_blank' href='https://your-org.atlassian.net/browse/" + valuei + "'>" + valuei + "</a>";

                    tableColumns = tableColumns + "<td>" + valuei + "</td>";
                });
                $('#issuesList').find('tbody').append("<tr>" + tableColumns + "</tr>");
            });
            $('#headerRow').html(bugCategory.replaceAll(',', ' & ') + " - Summarised view of Bugs reported in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]");
        }
    });
};

function fetchProdBugLeakage_LineChart_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProdBugLeakageTrend_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, 'STG,FCT,PRD']
        },
        success: function (result) {
            var categoriesData = null;
            var datasetData = null;
            // The backend returns { categories: [...], dataset: [...] }
            if (result.categories) categoriesData = result.categories;
            if (result.dataset) datasetData = result.dataset;

            var chartProperties = {
                "caption": "Production Bugs Leakage % Trend for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
                "plottooltext": "$seriesName: $dataValue%",
                "yAxisName": "Bug Leakage %",
                "drawCrossLine": "1",
                "theme": theme === "fusion" ? "zune" : theme,
                "showValues": "1",
                "numbersuffix": "%",
                "showsum": "0",
                "rotatevalues": "0",
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
                    "dataset": datasetData,
                    "categories": categoriesData
                }
            });
            apiChart.render();
        }
    });
}

function getBugRatioTrend_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugRatioTrend_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, bugCategory]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": bugCategory.replaceAll(',', ' & ') + " - Bug Ratio Trend in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
                "plottooltext": "$seriesName: $dataValue",
                "yAxisName": "Bug Ratio",
                "placevaluesinside": "0",
                "rotatevalues": "0",
                "showvalues": "1",
                "theme": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
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

function getBugCountTrendByPriority_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountTrendByPriority_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, bugCategory]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": bugCategory.replaceAll(',', ' & ') + " - Bug Count Trend by Priority in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
                "plottooltext": "$seriesName: $dataValue bugs",
                "yAxisName": "Number of Bugs",
                "placevaluesinside": "0",
                "rotatevalues": "0",
                "showvalues": "1",
                "theme": theme,
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

function qualityScoreChart(timeFilter, datasetValue, categoriesValue, trendLineAvg, message, chartNum, startDate, endDate) {
    var chartProperties = {
        "caption": message + " in last " + timeFilter + " days [All Projects]",
        "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
        "plottooltext": "$seriesName: $dataValue",
        "yAxisName": "Quality Score",
        "drawCrossLine": "1",
        "theme": theme === "fusion" ? "zune" : theme,
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

    apiChart = new FusionCharts({
        type: 'mscombi3d',
        renderAt: 'column-chart-container' + chartNum,
        width: '96%',
        height: '400',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "dataset": datasetValue,
            "categories": categoriesValue,
            "trendlines": [{
                "line": [{
                    "startvalue": trendLineAvg,
                    "displayvalue": "Avg: " + trendLineAvg,
                    "valueOnRight": "0",
                    "thickness": "2",
                    "dashed": "1",
                    "alpha": "70",
                    "tooltext": "Average Quality Score: $startvalue"
                }]
            }]
        }
    });
    apiChart.render();
}

function fetchQualityScore_STG(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getQualityScore_All',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive, 'STG']
        },
        success: function (result) {
            var categoriesData = null;
            var datasetData = null;
            var trendLineAvg = 0;

            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
                if (key === "trendLineQualityScoreAvg")
                    trendLineAvg = value;
            });

            qualityScoreChart(timeFilter, datasetData, categoriesData, trendLineAvg, "'Staging' Quality Score", 13, startDate, endDate);
        }
    });
}

function fetchQualityScore_FCT(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getQualityScore_All',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive, 'FCT']
        },
        success: function (result) {
            var categoriesData = null;
            var datasetData = null;
            var trendLineAvg = 0;

            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
                if (key === "trendLineQualityScoreAvg")
                    trendLineAvg = value;
            });

            qualityScoreChart(timeFilter, datasetData, categoriesData, trendLineAvg, "'Regression' Quality Score", 6, startDate, endDate);
        }
    });
}

function fetchQualityScore_PRD(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getQualityScore_All',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive, 'PRD']
        },
        success: function (result) {
            var categoriesData = null;
            var datasetData = null;
            var trendLineAvg = 0;

            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
                if (key === "trendLineQualityScoreAvg")
                    trendLineAvg = value;
            });

            qualityScoreChart(timeFilter, datasetData, categoriesData, trendLineAvg, "'Production' Quality Score", 3, startDate, endDate);
        }
    });
}

function fetchQualityScoreTrend_Project(tableNamePrefix, projectName, timeFilter, startDate, endDate, bugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getQualityScoreTrend_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, bugCategory]
        },
        success: function (result) {
            var categoriesData = result.categories || null;
            var datasetData = result.dataset || null;

            var chartProperties = {
                "caption": bugCategory.replaceAll(',', ' & ') + " - Quality Score Trend - last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")",
                "plottooltext": "<b>$label</b><br>Quality Score: <b>$dataValue</b>",
                "yAxisName": "Quality Score",
                "theme": theme,
                "showValues": "1",
                "lineThickness": "2",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD"
            };

            var chart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container4',
                width: '96%',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "categories": categoriesData,
                    "dataset": datasetData
                }
            });
            chart.render();
        }
    });
}