$(function() {
    $.ajax({

        url: 'http://localhost:8282/chart_data.php',
        type: 'GET',
        success: function(data) {
            chartData = data;
            var chartProperties = {
                "caption": "Average Pass Percentage of Automation cases",
                "xAxisName": "Project Name",
                "yAxisName": "Percantage",
                "rotatevalues": "2",
                "theme": "candy"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'chart-container1',
                width: '950',
                height: '400',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": chartData
                }
            });
            apiChart.render();
        }
    });
});