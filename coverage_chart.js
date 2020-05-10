$(function () {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_Coverage'},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Test Automation Coverage",
                "xAxisName": "Project Name",
                "yAxisName": "Percentage",
                "rotatevalues": "3",
                "theme": "gammel"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'chart-container1',
                width: '1250',
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
});