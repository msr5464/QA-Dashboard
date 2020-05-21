$(function () {
    showDefaultCharts("7");
});

$(document).ready(function () {
    $(".filter").click(function () {
        $(".filter").removeClass("active");
        $(this).addClass("active");
    });

    $("#weeklyData").click(function () {
        showDefaultCharts("7");
    });

    $("#monthlyData").click(function () {
        showDefaultCharts("30");
    });

    $("#quarterlyData").click(function () {
        showDefaultCharts("90");
    });

    $("#yearlyData").click(function () {
        showDefaultCharts("365");
    });
});

function showDefaultCharts(timeFilter) {
    fetchResultsData_Production(timeFilter);
    fetchResultsData_Staging(timeFilter);
    fetchTestrailData_P0Coverage(timeFilter);
    fetchTestrailData_P1Coverage(timeFilter);
}

function fetchResultsData_Production(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getLatestResultsData_Production',
            arguments: [timeFilter]
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Thanos - Average Pass Percentage on Production [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label - $dataValue%",
                "theme": "zune"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
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

function fetchResultsData_Staging(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getLatestResultsData_Staging',
            arguments: [timeFilter]
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Thanos - Average Pass Percentage on Staging [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label - $dataValue%",
                "theme": "zune"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
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


function fetchTestrailData_P0Coverage(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_P0'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - P0 Automation Coverage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container3',
                width: '96%',
                height: '500',
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

function fetchTestrailData_P1Coverage(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_P1'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - P1 Automation Coverage [All Projects]",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'column-chart-container4',
                width: '96%',
                height: '500',
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
