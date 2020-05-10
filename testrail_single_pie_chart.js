$(function () {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_singleProject', arguments: ['Saudagar']},
        success: function(result) 
        {
            var chartProperties = 
            {
                "caption": "Testrail - Automation Cases Breakdown for Saudagar",
                "subCaption" : "",
                "showValues":"1",
                "showPercentInTooltip" : "1",
                "numberPrefix" : "",
                "enableMultiSlicing":"1",
                "theme": "gammel"
            };

            apiChart = new FusionCharts({
                type: 'pie3d',
                renderAt: 'chart-container1',
                width: '550',
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