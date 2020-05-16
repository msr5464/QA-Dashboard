$(function () {
    fetchResultsData("7");
    fetchTestrailData("7");
});

$(document).ready(function() {
    $(".filter").click(function(){
        $(".filter").removeClass("active");
        $(this).addClass("active"); 
    });

    $("#weeklyData").click(function(){
        fetchResultsData("7");
        fetchTestrailData("7");
    });

    $("#monthlyData").click(function(){
        fetchResultsData("30");
        fetchTestrailData("30");
    });

    $("#quarterlyData").click(function(){
        fetchResultsData("90");
        fetchTestrailData("90");
    });

    $("#yearlyData").click(function(){
        fetchResultsData("365");
        fetchTestrailData("365");
    });
});

function fetchResultsData(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getLatestResultsData_All', arguments: [timeFilter]},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Average Pass Percentage of Automation cases on Staging",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label - $dataValue%",
                "theme": "zune"
            };

            apiChart = new FusionCharts({
                type: 'column2d',
                renderAt: 'chart-container1',
                width: '95%',
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


function fetchTestrailData(timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getLatestTestrailData_All', arguments: [timeFilter]},
        success: function (result) 
        {
            var resultValue2 = 0;
            var resultValue3 = 0;
            $.each(result, function(key, value)
            {
                if(key==="categories")
                    categoriesData = value;
                if(key==="dataset")
                    datasetData = value;
            });

            var chartProperties = 
            {
                "caption": "Testrail - Automation Testcases Metrics",
                "placevaluesinside": "0",
                "showvalues": "0",
                "plottooltext": "$label - $seriesName - $dataValue",
                "theme": "fusion",
                "showsum":"1"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn2d',
                renderAt: 'chart-container2',
                width: '96%',
                height: '500',
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