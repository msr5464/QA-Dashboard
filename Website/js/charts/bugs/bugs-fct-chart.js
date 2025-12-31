var backend = "utils/data/bugs/bugs-fct-data.php";
var pageName = "bugs-fct.php";

$(document).ready(function () {
    $("#goButton").click(function () {
        setDataIntoStorage("fctBugCategory", $("#fctBugCategoryDropdown").val());
    });
    $("#addFiltersButton").click(function () {
        showDropdowns();
        $("#addFiltersButton").hide();
    });
});

function showDropdowns() {
    $("#selectProject").show();
    $("#projectNamesDropdown").val(projectName.replaceAll("'", "").split(',')).trigger("chosen:updated");
    $("#fctBugCategoryDropdown").addClass("chosen-select").val(getDataFromStorage("fctBugCategory").split(',')).show().chosen();
}

function showDefaultCharts(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    hideProjectCharts();
    fetchTotalBugs_GaugeChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchBugCountData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchOverallBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
    fetchDevelopmentBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive);
}

function showProjectCharts(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    $("#issuesBody").empty();
    $("#bugsReviewTableBody").empty();
    fctBugCategory = getDataFromStorage("fctBugCategory");
    if (fctBugCategory == null || fctBugCategory == "") {
        fctBugCategory = "PaymentGateway,Partner,Invalid";
        setDataIntoStorage("fctBugCategory", fctBugCategory);
    }
    fetchTotalBugs_GaugeChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName);
    fetchBugCountData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    fetchOverallBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    fetchDevelopmentBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    getBugCountTrend_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    getBugSlaTrend_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    getBugCountTrendByPriority_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    showIssuesList_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
    showBugsReviewTable_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory);
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
                if (index == "partnerBugs")
                    partnerBugs = value;
                if (index == "invalidBugs")
                    invalidBugs = value;
            });

            document.getElementById("gauge-chart-container1").innerHTML = totalBugs;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = paymentGatewayBugs;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = partnerBugs;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            document.getElementById("gauge-chart-container4").innerHTML = invalidBugs;
            document.getElementById("gauge-chart-container4").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container4").classList.add('bigFont');
        }
    });
};

function showIssuesList_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getIssuesList_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, fctBugCategory]
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
            $('#headerRow').html(fctBugCategory.replaceAll(',', ' & ') + " - List of FCT bugs for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]");
        }
    });
};

function showBugsReviewTable_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugsReviewTable_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, fctBugCategory]
        },
        success: function (result) {
            $.each(result, function (index, value) {
                tableColumns = "";
                $.each(value, function (indexi, valuei) {
                    tableColumns = tableColumns + "<td>" + valuei + "</td>";
                });
                $('#bugsReviewTable').find('tbody').append("<tr>" + tableColumns + "</tr>");
            });
            $('#bugsReviewTableHeaderRow').html(fctBugCategory.replaceAll(',', ' & ') + " - FCT Bugs Review Summary for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]");
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
            bugCountChart(timeFilter, datasetData, categoriesData, "Count of FCT Bugs reported in last " + timeFilter + " days [All Projects]", 1, startDate, endDate);
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
            bugSlaChart(timeFilter, datasetData, categoriesData, "FCT Bugs resolved within 'Overall' SLA in last " + timeFilter + " days [All Projects]", 2, startDate, endDate);
        }
    });
};

function fetchDevelopmentBugSlaData_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDevelopmentBugSlaData',
            arguments: [tableNamePrefix, startDate, endDate, isVerticalDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            bugSlaChart(timeFilter, datasetData, categoriesData, "FCT Bugs resolved within 'Development' SLA in last " + timeFilter + " days [All Projects]", 5, startDate, endDate);
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
                if (index == "partnerBugs")
                    partnerBugs = value;
                if (index == "invalidBugs")
                    invalidBugs = value;
            });

            document.getElementById("gauge-chart-container1").innerHTML = totalBugs;
            document.getElementById("gauge-chart-container1").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container1").classList.add('bigFont');
            document.getElementById("gauge-chart-container2").innerHTML = paymentGatewayBugs;
            document.getElementById("gauge-chart-container2").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container2").classList.add('bigFont');
            document.getElementById("gauge-chart-container3").innerHTML = partnerBugs;
            document.getElementById("gauge-chart-container3").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container3").classList.add('bigFont');
            document.getElementById("gauge-chart-container4").innerHTML = invalidBugs;
            document.getElementById("gauge-chart-container4").classList.remove('custom-text-2');
            document.getElementById("gauge-chart-container4").classList.add('bigFont');
        }
    });
};

function fetchBugCountData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, fctBugCategory]
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
            bugCountChart(timeFilter, datasetData, categoriesData, fctBugCategory.replaceAll(',', ' & ') + " - Count of FCT Bugs reported in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 3, startDate, endDate);
        }
    });
};

function fetchOverallBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getOverallBugSlaData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, fctBugCategory]
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
            bugSlaChart(timeFilter, datasetData, categoriesData, fctBugCategory.replaceAll(',', ' & ') + " - FCT Bugs resolved within 'Overall' SLA in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 4, startDate, endDate);
        }
    });
};

function fetchDevelopmentBugSlaData_ColumnChart_Project(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDevelopmentBugSlaData_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, fctBugCategory]
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
            bugSlaChart(timeFilter, datasetData, categoriesData, fctBugCategory.replaceAll(',', ' & ') + " - FCT Bugs resolved within 'Development' SLA in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]", 6, startDate, endDate);
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
                prodBugCategory.replaceAll(',', ' & ') + " - Trend of FCT Bugs Count in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
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
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = createTrendChartProperties(
                prodBugCategory.replaceAll(',', ' & ') + " - Trend of FCT Bugs SLA in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
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

function getBugCountTrendByPriority_LineChart(tableNamePrefix, timeFilter, startDate, endDate, projectName, fctBugCategory) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountTrendByPriority_Project',
            arguments: [tableNamePrefix, startDate, endDate, projectName, fctBugCategory]
        },
        success: function (result) {
            var chartData = extractChartData(result);
            categoriesData = chartData.categories;
            datasetData = chartData.dataset;

            var chartProperties = createBaseChartProperties(
                fctBugCategory.replaceAll(',', ' & ') + " - Bug Count Trend by Priority in last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
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