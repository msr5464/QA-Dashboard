$(function () {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getResultsData_Latest'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Average Pass Percentage of Automation cases",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label - $dataValue%",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'chart-container1',
                width: '100%',
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
});