var backend = "server/jira-data.php";
var pageName = "jira.php";

function showDefaultCharts(verticalName, timeFilter) {
    hideProjectCharts();
    fetchTotalBugsFound_ColumnChart(verticalName, timeFilter);
    fetchBugPercentage_ColumnChart(verticalName, timeFilter);
    fetchProductionBugsFound_ColumnChart(verticalName, timeFilter);
    fetchProdBugPercentage_ColumnChart(verticalName, timeFilter);
}

function showProjectCharts(verticalName, projectName, timeFilter) {
    hideDefaultCharts();
    fetchTotalTicketsTested_GaugeChart(verticalName, projectName, timeFilter);
    fetchBugPriorityBreakdown_PieChart(verticalName, projectName, timeFilter);
    fetchBugPercentageTrend_ColumnChart(verticalName, projectName, timeFilter);
    fetchBugCountTrend_ColumnChart(verticalName, projectName, timeFilter);
}

function fetchTotalBugsFound_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalBugsFound',
            arguments: [verticalName, timeFilter]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
                if (key === "totalTicketsTested_sum")
                    resultValue1 = value;
                if (key === "totalBugs_sum")
                    resultValue2 = value;
                if (key === "totalProdBugs_sum")
                    resultValue3 = value;

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

            var chartProperties = {
                "caption": "Total Bugs found in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue",
                "yAxisName": "Number of Bugs",
                "theme": "candy",
                "showValues": "1",
                "rotatevalues": "0"
            };

            apiChart = new FusionCharts({
                type: 'mscombi3d',
                renderAt: 'column-chart-container1',
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

function fetchBugPercentage_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugPercentage',
            arguments: [verticalName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Total Bug Ratio (per 100 tickets) for last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Ratio per 100 tickets",
                "rotatevalues": "0",
                "theme": "zune",
                "showValues": "1"
            };

            apiChart = new FusionCharts({
                type: 'mscombi2d',
                renderAt: 'column-chart-container2',
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


function fetchProductionBugsFound_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProductionBugsFound',
            arguments: [verticalName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Production Bugs found in last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName: $dataValue",
                "yAxisName": "Number of Bugs",
                "theme": "candy",
                "showValues": "1",
                "rotatevalues": "0"
            };

            apiChart = new FusionCharts({
                type: 'mscombi3d',
                renderAt: 'column-chart-container3',
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


function fetchProdBugPercentage_ColumnChart(verticalName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProdBugPercentage',
            arguments: [verticalName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Production Bug Ratio (per 100 tickets) for last " + timeFilter + " days [All Projects]",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Ratio per 100 tickets",
                "rotatevalues": "0",
                "theme": "zune",
                "showValues": "1"
            };

            apiChart = new FusionCharts({
                type: 'mscombi2d',
                renderAt: 'column-chart-container4',
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

function fetchTotalTicketsTested_GaugeChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalTicketsTested_Project',
            arguments: [verticalName, projectName, timeFilter]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "totalTicketsTested")
                        resultValue1 = value;
                    if (key === "totalBugs")
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


function fetchBugPriorityBreakdown_PieChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugPriorityBreakdown_Project',
            arguments: [verticalName, projectName, timeFilter]
        },
        success: function (result) {

            var chartProperties = {
                "caption": "Priority wise Bugs Breakdown for last " + timeFilter + " days for " + projectName,
                "showpercentvalues": "1",
                "defaultcenterlabel": "Bugs Found",
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
                    "data": result
                }
            });
            apiChart.render();
        }
    });
};

function fetchBugPercentageTrend_ColumnChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugPercentageTrend_Project',
            arguments: [verticalName, projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Trend of Bug Ratio (per 100 tickets) for last " + timeFilter + " days for " + projectName,
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue%",
                "yAxisName": "Ratio per 100 tickets",
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

function fetchBugCountTrend_ColumnChart(verticalName, projectName, timeFilter) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getBugCountTrend_Project',
            arguments: [verticalName, projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Trend of Bug Count for last " + timeFilter + " days for " + projectName,
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Count",
                "theme": "fusion",
                "showValues": "1"
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
};