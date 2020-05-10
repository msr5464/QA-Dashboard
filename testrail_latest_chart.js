$(function () {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {functionname: 'getTestRailData_Latest'},
        success: function (result) 
        {
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
                "placevaluesinside": "1",
                "showvalues": "0",
                "plottooltext": "$label - $seriesName - $dataValue",
                "theme": "candy"
            };
            apiChart = new FusionCharts({
                type: 'stackedcolumn3d',
                renderAt: 'chart-container2',
                width: '100%',
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
});