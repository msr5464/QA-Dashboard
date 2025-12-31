var backend = "utils/data/bugs/bugs-prod-data.php";
var pageName = "bugs-prod.php";

$(document).ready(function () {
    $("#goButton").click(function () {
        setDataIntoStorage("prodBugCategory", $("#prodBugCategoryDropdown").val());
    });
    $("#addFiltersButton").click(function () {
        showDropdowns();
        $("#addFiltersButton").hide();
    });
});

function showDropdowns() {
    $("#selectProject").show();
    $("#projectNamesDropdown").val(projectName.replaceAll("'", "").split(',')).trigger("chosen:updated");
    $("#prodBugCategoryDropdown").addClass("chosen-select").val(getDataFromStorage("prodBugCategory").split(',')).show().chosen();
}

function showDefaultCharts(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    hideProjectCharts();
    fetchTotalBugs_GaugeChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchBugCountData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchOverallBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchFirstReviewBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
}

function showProjectCharts(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    $("#issuesBody").empty();
    prodBugCategory = getDataFromStorage("prodBugCategory");
    if (prodBugCategory == null || prodBugCategory == "") {
        prodBugCategory = "PaymentGateway";
        setDataIntoStorage("prodBugCategory", prodBugCategory);
    }
    fetchTotalBugs_GaugeChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName);
    fetchBugCountData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    fetchOverallBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    fetchFirstReviewBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    getBugCountTrend_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    getBugSlaTrend_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    getBugCountTrendByPriority_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    showIssuesList_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    showBugsReviewTable_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory);
    window.setTimeout('$("#addFiltersButton").show();', 1000);
}

function fetchTotalBugs_GaugeChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalBugs',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive]
        },
        success: function (result) {
            $.each(result, function (index, value) {
                if (index == "totalBugs")
                    totalBugs = value;
                if (index == "paymentGatewayBugs")
                    paymentGatewayBugs = value;
                if (index == "invalidBugs")
                    invalidBugs = value;
                if (index == "othersBugs")
                    othersBugs = value;
            });

            document.getElementById("gauge-chart-container1").innerHTML = totalBugs;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = paymentGatewayBugs;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = invalidBugs;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            document.getElementById("gauge-chart-container4").innerHTML = othersBugs;
            document.getElementById("gauge-chart-container4").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container4").classList.add('bigFont');
        }
    });
};

function showIssuesList_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getIssuesList_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
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
            $('#headerRow').html(prodBugCategory.replaceAll(',', ' & ') + " - Summarised view of PRD bugs reported in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]");
        }
    });
};

function showBugsReviewTable_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsReviewTable_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            $.each(result, function (index, value) {
                tableColumns = "";
                $.each(value, function (indexi, valuei) {
                    tableColumns = tableColumns + "<td>" + valuei + "</td>";
                });
                $('#bugsReviewTable').find('tbody').append("<tr>" + tableColumns + "</tr>");
            });
            $('#bugsReviewTableHeaderRow').html(prodBugCategory.replaceAll(',', ' & ') + " - PRD Bugs' Tech Root Cause Summary for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]");
        }
    });
};

function fetchBugCountData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugCountChart(timeFilter, datasetData, categoriesData, "Count of PRD bugs reported in last " + timeFilter + " days [All Projects]", 1, startDate, endDate);
        }
    });
};

function fetchOverallBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getOverallBugSlaData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugSlaChart(timeFilter, datasetData, categoriesData, "PRD bugs resolved within 'Overall' SLA in last " + timeFilter + " days [All Projects]", 2, startDate, endDate);
        }
    });
};

function fetchFirstReviewBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getFirstReviewBugSlaData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugSlaChart(timeFilter, datasetData, categoriesData, "PRD bugs triaged within 'First Review' SLA in last " + timeFilter + " days [All Projects]", 5, startDate, endDate);
        }
    });
};

function fetchTotalBugs_GaugeChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalBugs_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName]
        },
        success: function (result) {
            $.each(result, function (index, value) {
                if (index == "totalBugs")
                    totalBugs = value;
                if (index == "paymentGatewayBugs")
                    paymentGatewayBugs = value;
                if (index == "invalidBugs")
                    invalidBugs = value;
                if (index == "othersBugs")
                    othersBugs = value;
            });

            document.getElementById("gauge-chart-container1").innerHTML = totalBugs;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = paymentGatewayBugs;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = invalidBugs;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            document.getElementById("gauge-chart-container4").innerHTML = othersBugs;
            document.getElementById("gauge-chart-container4").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container4").classList.add('bigFont');
        }
    });
};

function fetchBugCountData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            var resultValue1 = 0;
            var trendLineCountAvg = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugCountChart(timeFilter, datasetData, categoriesData, prodBugCategory.replaceAll(',', ' & ') + " - Count of PRD bugs reported in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 3, startDate, endDate);
        }
    });
};

function fetchOverallBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getOverallBugSlaData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            var resultValue1 = 0;
            var trendLineCountAvg = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugSlaChart(timeFilter, datasetData, categoriesData, prodBugCategory.replaceAll(',', ' & ') + " - PRD bugs resolved within 'Overall' SLA in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 4, startDate, endDate);
        }
    });
};

function fetchFirstReviewBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getFirstReviewBugSlaData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            var resultValue1 = 0;
            var trendLineCountAvg = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugSlaChart(timeFilter, datasetData, categoriesData, prodBugCategory.replaceAll(',', ' & ') + " - PRD bugs triaged within 'First Review' SLA in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 6, startDate, endDate);
        }
    });
};

function getBugCountTrend_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountTrend_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            var chartData = extractChartData(result);
            categoriesData = chartData.categories;
            datasetData = chartData.dataset;

            var chartProperties = createTrendChartProperties(
                prodBugCategory.replaceAll(',', ' & ') + " - Trend of PRD bugs Count in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "Data shown for period (" + startDate + " → " + endDate + ")",
                "Percentage",
                {
                    "placevaluesinside": "0"
                }
            );

            apiChart = renderTrendChart(
                'scrollcolumn2d',
                'column-chart-container7',
                chartProperties.caption,
                chartProperties.subcaption,
                chartProperties.yAxisName,
                datasetData,
                categoriesData,
                {
                    "placevaluesinside": "0"
                },
                350
            );
        }
    });
};

function getBugSlaTrend_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugSlaTrend_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            var chartData = extractChartData(result);
            categoriesData = chartData.categories;
            datasetData = chartData.dataset;

            var chartProperties = createTrendChartProperties(
                prodBugCategory.replaceAll(',', ' & ') + " - Trend of PRD bugs SLA in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "Data shown for period (" + startDate + " → " + endDate + ")",
                "Percentage",
                {
                    "placevaluesinside": "0",
                    "numbersuffix": "%"
                }
            );

            apiChart = renderTrendChart(
                'scrollcolumn2d',
                'column-chart-container8',
                chartProperties.caption,
                chartProperties.subcaption,
                chartProperties.yAxisName,
                datasetData,
                categoriesData,
                {
                    "placevaluesinside": "0",
                    "numbersuffix": "%"
                },
                350
            );
        }
    });
};

function getBugCountTrendByPriority_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, prodBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountTrendByPriority_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, prodBugCategory]
        },
        success: function (result) {
            var chartData = extractChartData(result);
            categoriesData = chartData.categories;
            datasetData = chartData.dataset;

            var chartProperties = createBaseChartProperties(
                prodBugCategory.replaceAll(',', ' & ') + " - Bug Count Trend by Priority in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "Data shown for period (" + startDate + " → " + endDate + ")",
                "Number of Bugs",
                {
                    "plottooltext": "$seriesName: $dataValue bugs",
                    "placevaluesinside": "0"
                }
            );

            apiChart = renderChart(
                'msline',
                'line-chart-container1',
                chartProperties,
                datasetData,
                categoriesData,
                null,
                350
            );
        }
    });
};

// bugCountChart is now defined in common-chart-functions.js

// bugSlaChart is now defined in common-chart-functions.js