var projectName = 0;
$(function () {
    validateAndExecute("7");
});

$(document).ready(function () {
    $("#projectName").click(function () {
        $("#selectProject").show();
        showDefaultCharts("7");
        $("#projectName").hide();
    });

    $(".filter").click(function () {
        $(".filter").removeClass("active");
        $(this).addClass("active");
    });

    $("#weeklyData").click(function(){
        validateAndExecute("7");
    });

    $("#monthlyData").click(function(){
        validateAndExecute("30");
    });

    $("#quarterlyData").click(function(){
        validateAndExecute("90");
    });

    $("#yearlyData").click(function(){
        validateAndExecute("365");
    });
});

function validateAndExecute(timeFilter) {
    projectName = $("#projectName").html();
    if (projectName.length != 0) {
        $("#selectProject").hide();
        $("#warning").hide();
        showProjectCharts(projectName, timeFilter);
        $(".defaultChart").hide();
    } else {
        showDefaultCharts(timeFilter);
        $("#projectName").hide();
        //$("#footer").hide();
        $(".projectChart").hide();
    }
}

function showProjectCharts(projectName, timeFilter) {
    generateGaugeData(projectName, timeFilter);
    fetchLastTenResults(projectName, timeFilter);
    fetchAvgPercentageData(projectName, timeFilter);
    fetchGroupWiseData(projectName, timeFilter);
    //showDefaultCharts(timeFilter);
}

function showDefaultCharts(timeFilter) {

}

function generateGaugeData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getAvgPassPercentage_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "Production-data")
                        resultValue1 = value;
                    if (key === "Sandbox-data")
                        resultValue2 = value;
                    if (key === "Staging-data")
                        resultValue3 = value;
                });
            }

            var chartProperties = {
                "showBorder": "0",
                "captionfontcolor": "#686980",
                "captionfontsize": "16",
                "captionalignment": "left",
                "showShadow": "0",
                "use3DLighting": "0",
                "showLabels": "0",
                "showValues": "0",
                "paletteColors": "#efefef,#11DFF6",
                "bgColor": "#1D1B41",
                "bgAlpha": "0",
                "canvasBgAlpha": "0",
                "doughnutRadius": "75",
                "pieRadius": "90",
                "enableSlicing": "0",
                "plotBorderAlpha": "0",
                "showToolTip": "0",
                "baseFontSize": "14",
                "logoURL": "shield.svg",
                "logoScale": "4",
                "logoAlpha": "100",
                "logoPosition": "TR",
                "logoTopMargin": "2",
                "defaultCenterLabel": null,
                "centerLabelBold": "1",
                "centerLabelFontSize": "25",
                "enableRotation": "0",
                "captionfont": "avenir-heavy",
                "baseFont": "avenir-medium",
                "startingAngle": "90",
                "animateClockwise": "1"
            };

            apiChart1 = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'gauge-chart-container1',
                width: '350',
                height: '200',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultValue1
                },
                "events": {
                    "beforeInitialize": function () {
                        if(resultValue1)
                        {
                            var passPercentage = resultValue1[1].value;
                            chartProperties.defaultCenterLabel = passPercentage+"%";
                        }
                    }
                }
            });
            apiChart1.render();

            apiChart2 = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'gauge-chart-container2',
                width: '350',
                height: '200',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultValue2
                },
                "events": {
                    "beforeInitialize": function () {
                        if(resultValue2)
                        {
                            var passPercentage = resultValue2[1].value;
                            chartProperties.defaultCenterLabel = passPercentage+"%";
                        }
                    }
                }
            });
            apiChart2.render();

            apiChart3 = new FusionCharts({
                type: 'doughnut2d',
                renderAt: 'gauge-chart-container3',
                width: '350',
                height: '200',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultValue3
                },
                "events": {
                    "beforeInitialize": function () {
                        if(resultValue3)
                        {
                            var passPercentage = resultValue3[1].value;
                            chartProperties.defaultCenterLabel = passPercentage+"%";
                        }
                    }
                }
            });
            apiChart3.render();
        }
    });
};

function fetchLastTenResults(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getResultsData_Last10_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            var chartProperties = {
                "caption": "Details of last " + timeFilter + " Automation Builds",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label - $dataValue%",
                "theme": "ocean"
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
                },
                "events": {
                    "beforeRender": function (e, d) {
                        var messageBlock = document.createElement('p');
                        messageBlock.style.textAlign = "center";
                        var activatedMessage = 'Click on the plot to access the Results Link';

                        var getClickedMessage = function (categoryLabel, displayValue) {
                            var temp = "";
                            if (categoryLabel.includes("Golabs")) {
                                temp = categoryLabel.replace("Golabs-", "");
                            } else {
                                temp = categoryLabel.replace("Jenkins", projectName);
                                var position = temp.lastIndexOf("-");
                                temp = temp.substring(0, position) + "-Automation" + temp.substring(position);
                            }
                            var resultsLink = "http://52.221.7.215/" + projectName + "/" + temp + "/html/index.html";
                            return 'Results Url of <B>"' + categoryLabel + '"</B> - <a style="color:yellow" href="' + resultsLink + '" target="_blank">' + resultsLink + '</a>';
                        };
                        e.data.container.appendChild(messageBlock);

                        function dataPlotClickListener(e, a) {
                            var categoryLabel = e.data.categoryLabel;
                            var displayValue = e.data.displayValue;
                            var resMessage = getClickedMessage(categoryLabel, displayValue);
                            messageBlock.innerHTML = resMessage;
                        }

                        messageBlock.innerText = activatedMessage;
                        e.sender.addEventListener('dataplotclick', dataPlotClickListener);
                    }
                }
            });

            apiChart.render();
        }
    });
};

function fetchAvgPercentageData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getAvgResultsData_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily percentage for last " + timeFilter + " days",
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue%",
                "yAxisName": "Percentage",
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

function fetchGroupWiseData(projectName, timeFilter) {
    $.ajax({
        url: 'data_generator.php',
        type: 'GET',
        data: {
            functionname: 'getTotalCasesResultsData_Project',
            arguments: [projectName, timeFilter]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "GroupName wise total cases for last " + timeFilter + " days",
                "subCaption": "",
                "plottooltext": "$seriesName - $dataValue",
                "yAxisName": "Total Testcases",
                "theme": "candy",
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