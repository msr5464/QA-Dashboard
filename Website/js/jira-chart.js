var backend = "utils/jira-data.php";
var pageName = "jira.php";

$(document).ready(function () {
    $("#goButton").click(function () {
        setDataIntoStorage("environment",$("#environmentDropdown").val());
    });
    $("#addFiltersButton").click(function () {
        showDropdowns();
        $("#addFiltersButton").hide();
    });
});

function showDropdowns() {
    $("#selectProject").show();
    $("#projectNamesDropdown").val(projectName.replaceAll("'","").split(',')).trigger("chosen:updated");
    $("#environmentDropdown").addClass("chosen-select").val(getDataFromStorage("environment").split(',')).show().chosen();
}

function showDefaultCharts(verticalName, tableName, timeFilter, startDate, endDate) {
    hideProjectCharts();
    fetchProdBugsData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
    fetchStgBugsData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive);
}

function showProjectCharts(verticalName, tableName, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    $("#issuesBody").empty();
    environment = getDataFromStorage("environment");
    if (environment == null || environment == "")
        environment = "Staging,Production"
    
    fetchTotalTicketsTested_GaugeChart(tableName, projectName, timeFilter, startDate, endDate);
    fetchTotalBugsCount_GaugeChart(tableName, projectName, timeFilter, startDate, endDate);
    fetchBugsCategoryData_ColumnChart_Project(tableName, timeFilter, startDate, endDate, projectName, environment);
    fetchBugsFoundByData_ColumnChart_Project(tableName, timeFilter, startDate, endDate, projectName, environment);
    showIssuesList_Project(tableName, timeFilter, startDate, endDate, projectName, environment);
    window.setTimeout('$("#addFiltersButton").show();', 1000);
}

function fetchStgBugsData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getStgBugsData',
            arguments: [tableName, startDate, endDate, isPodDataActive]
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
            bugsFoundChartWithTrendLine(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Staging' Bugs found", 3);
            bugPercantageChartWithTrendLine(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "Staging Bug Ratio (per 100 tickets)", 4);
        }
    });
};

function fetchProdBugsData_ColumnChart(tableName, timeFilter, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProdBugsData',
            arguments: [tableName, startDate, endDate, isPodDataActive]
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
            bugsFoundChartWithTrendLine(timeFilter, datasetData1, categoriesData, trendLineCountAvg, "Count of 'Production' Bugs found", 1);
            bugPercantageChartWithTrendLine(timeFilter, datasetData2, categoriesData, trendLinePercAvg, "Production Bug Ratio (per 100 tickets)", 2);
        }
    });
};

function fetchTotalTicketsTested_GaugeChart(tableName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalTicketsTested_Project',
            arguments: [tableName, projectName, startDate, endDate]
        },
        success: function (result) {
            var resultValue1 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalTicketsTested")
                        resultValue1 = value;
                });
            }
            document.getElementById("gauge-chart-container1").innerHTML = resultValue1;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
        }
    });
};

function fetchTotalBugsCount_GaugeChart(tableName, projectName, timeFilter, startDate, endDate) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalBugsCount_Project',
            arguments: [tableName, projectName, startDate, endDate]
        },
        success: function (result) {
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalStgBugs")
                        resultValue2 = value;
                    if (key === "totalProdBugs")
                        resultValue3 = value;
                });
            }
            document.getElementById("gauge-chart-container2").innerHTML = resultValue2;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = resultValue3;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
        }
    });
};


        
function bugsFoundChartWithTrendLine(timeFilter, datasetValue, categoriesValue, trendLineCountAvg, message, chartNum) {
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

function bugPercantageChartWithTrendLine(timeFilter, datasetValue, categoriesValue, trendLinePercAvg, message, chartNum) {
    var chartProperties = {
        "caption": message+" for last " + timeFilter + " days [All Projects]",
        "plottooltext": "$seriesName: $dataValue",
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

function fetchBugsCategoryData_ColumnChart_Project(tableName, timeFilter, startDate, endDate, projectName, environment) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsData_Project',
            arguments: [tableName, startDate, endDate, projectName, "bugCategory", environment]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugsFoundChart(timeFilter, datasetData, categoriesData, environment.replaceAll(',',' & ')+" - 'Bugs Category' segregation for last " + timeFilter + " days [" + projectName + "]", 5);
        }
    });
};

function fetchBugsFoundByData_ColumnChart_Project(tableName, timeFilter, startDate, endDate, projectName, environment) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsData_Project',
            arguments: [tableName, startDate, endDate, projectName, "bugFoundBy", environment]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugsFoundChart(timeFilter, datasetData, categoriesData, environment.replaceAll(',',' & ')+" - 'Bugs Found By' segregation for last " + timeFilter + " days [" + projectName + "]", 6);
        }
    });
};

function bugsFoundChart(timeFilter, datasetValue, categoriesValue, message, chartNum) {
    var chartProperties = {
        "caption": message,
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
        renderAt: 'column-chart-container'+chartNum,
        width: '96%',
        height: '400',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "dataset": datasetValue,
            "categories": categoriesValue
        }
    });
    apiChart.render();
}


function showIssuesList_Project(tableName, timeFilter, startDate, endDate, projectName, environment) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getIssuesList_Project',
            arguments: [tableName, startDate, endDate, projectName, environment]
        },
        success: function (result) {
            $.each(result, function (index, value) {
                tableColumns = "";
                $.each(value, function (indexi, valuei) {
                    if(indexi == "issueId")
                        valuei = "<a style='color:yellow' target='_blank' href='https://go-jek.atlassian.net/browse/"+valuei+"'>"+valuei+"</a>";

                    tableColumns = tableColumns+"<td>"+valuei+"</td>";
                });
                $('#issuesList').find('tbody').append("<tr>"+tableColumns+"</tr>");
            });
            $('#headerRow').html(environment.replaceAll(',',' & ')+" - Detailed Issues List for last " + timeFilter + " days [" + projectName + "]");
        }
    });
};