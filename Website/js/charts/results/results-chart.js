var backend = "utils/data/results/results-data.php";
var pageName = "results.php";

// Initialize chart sizing when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(adjustChartSizes, 1000);
});

function showDefaultCharts(tableNamePrefix, timeFilter, startDate, endDate, isVerticalDataActive) {
    hideProjectCharts();
    var environmentAndGroupNamePair1 = getDataFromEntityTable(entityName, "environmentAndGroupNamePair1");
    var environmentAndGroupNamePair2 = getDataFromEntityTable(entityName, "environmentAndGroupNamePair2");
    var environmentAndGroupNamePair3 = getDataFromEntityTable(entityName, "environmentAndGroupNamePair3");
    var environment1 = environmentAndGroupNamePair1.split(",")[0];
    var groupName1 = environmentAndGroupNamePair1.split(",")[1];
    var environment2 = environmentAndGroupNamePair2.split(",")[0];
    var groupName2 = environmentAndGroupNamePair2.split(",")[1];
    var environment3 = environmentAndGroupNamePair3.split(",")[0];
    var groupName3 = environmentAndGroupNamePair3.split(",")[1];

    fetchAvgPercentage_GaugeChart(tableNamePrefix, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, isVerticalDataActive);
    
    // Adjust chart sizes for default view (3 charts)
    setTimeout(adjustChartSizes, 500);
    fetchAvgPercentage_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment1, groupName1, 1, isVerticalDataActive);
    fetchAvgPercentage_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment2, groupName2, 2, isVerticalDataActive);
    fetchAvgPercentage_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment3, groupName3, 3, isVerticalDataActive);
    fetchAvgExecutionTime_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment1, groupName1, 4, isVerticalDataActive);
    fetchAvgExecutionTime_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment2, groupName2, 5, isVerticalDataActive);
    fetchAvgExecutionTime_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment3, groupName3, 6, isVerticalDataActive);
    window.setTimeout(hideBlankCharts, 2000);
}

function showProjectCharts(tableNamePrefix, projectName, timeFilter, startDate, endDate) {
    hideDefaultCharts();
    
    // Initially hide all gauge containers
    for (var i = 1; i <= 4; i++) {
        var parentElement = document.getElementById("parent" + i);
        if (parentElement) {
            parentElement.style.display = "none";
        }
    }
    
    // Reset chart sizes when hiding all charts
    setTimeout(adjustChartSizes, 100);
    
    // Check if project has custom group configuration
    var environmentAndGroupPairs;
    // Clean project name by removing quotes if present
    var cleanProjectName = projectName.replace(/'/g, '');
    console.log(`Original project name: ${projectName}, Clean project name: ${cleanProjectName}`);
    
    if (isCustomGroupProject(cleanProjectName)) {
        // Use custom configuration from config.js
        environmentAndGroupPairs = getEnvironmentAndGroupNamePairs(cleanProjectName);
        console.log(`Using custom groups for ${cleanProjectName}:`, environmentAndGroupPairs);
    } else {
        // Use existing method for standard projects
        environmentAndGroupPairs = [
            getDataFromEntityTable(entityName, "environmentAndGroupNamePair1"),
            getDataFromEntityTable(entityName, "environmentAndGroupNamePair2"),
            getDataFromEntityTable(entityName, "environmentAndGroupNamePair3")
        ];
        console.log(`Using standard groups for ${cleanProjectName}:`, environmentAndGroupPairs);
    }
    
    // Extract environment and group names from pairs
    var environments = [];
    var groupNames = [];
    
    for (var i = 0; i < environmentAndGroupPairs.length; i++) {
        var pair = environmentAndGroupPairs[i].split(",");
        environments[i] = pair[0];
        groupNames[i] = pair[1];
    }
    
    // Prepare 4 arguments (4th will be null for 3-group projects)
    var environment4 = environmentAndGroupPairs.length === 4 ? environments[3] : null;
    var groupName4 = environmentAndGroupPairs.length === 4 ? groupNames[3] : null;
    
    // Use existing methods with 4 arguments for both 3-group and 4-group projects
    fetchAvgPercForProject_GaugeChart(tableNamePrefix, projectName, startDate, endDate, 
        environments[0], groupNames[0], environments[1], groupNames[1], 
        environments[2], groupNames[2], environment4, groupName4, isVerticalDataActive);
    
    // Fetch column charts for available groups
    for (var i = 0; i < environmentAndGroupPairs.length; i++) {
        fetchLastSevenResults_ColumnChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, 
            environments[i], groupNames[i], 7 + i);
    }
    
    // Use existing line chart methods with 4 arguments
    fetchAvgDailyPercentage_LineChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, 
        environments[0], groupNames[0], environments[1], groupNames[1], 
        environments[2], groupNames[2], environment4, groupName4);
    fetchAvgDailyExecutionTime_LineChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, 
        environments[0], groupNames[0], environments[1], groupNames[1], 
        environments[2], groupNames[2], environment4, groupName4);
    fetchTotalCasesGroupwise_LineChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, 
        environments[0], groupNames[0], environments[1], groupNames[1], 
        environments[2], groupNames[2], environment4, groupName4);
    
    window.setTimeout(hideBlankCharts, 2000);
    
    // Final adjustment of chart sizes after all charts are loaded
    setTimeout(adjustChartSizes, 2500);
}

function fetchAvgPercentage_GaugeChart(tableNamePrefix, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage_All',
            arguments: [tableNamePrefix, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, isVerticalDataActive]
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === environment1+"-"+groupName1)
                        resultValue1 = value;
                    if (key === environment2+"-"+groupName2)
                        resultValue2 = value;
                    if (key === environment3+"-"+groupName3)
                        resultValue3 = value;
                });
            }
            enableGaugeChart(resultValue1, groupName1, environment1, 1);
            enableGaugeChart(resultValue2, groupName2, environment2, 2);
            enableGaugeChart(resultValue3, groupName3, environment3, 3);
        }
    });
}

function fetchAvgPercentage_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment, groupName, chartNum, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage',
            arguments: [tableNamePrefix, startDate, endDate, environment, groupName, isVerticalDataActive]
        },
        success: function (result) {
            var resultsData = 0;
            var environmentValue = 0;
            var groupNameValue = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "resultsData")
                        resultsData = value;
                    if (key === "environmentValue")
                        environmentValue = value;
                    if (key === "groupNameValue")
                        groupNameValue = value;
                });
            }

            var chartProperties = {
                "caption": "Average '"+groupNameValue+"' percentage for last " + timeFilter + " days [on "+environmentValue+"]",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": theme === "fusion" ? "zune": theme,
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "10",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")"
            };

            apiChart = new FusionCharts({
                type: 'column3d',
                renderAt: 'column-chart-container'+chartNum,
                width: '96%',
                height: '350',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "data": resultsData
                }
            });
            apiChart.render();
        }
    });
};

function fetchAvgExecutionTime_ColumnChart(tableNamePrefix, timeFilter, startDate, endDate, environment, groupName, chartNum, isVerticalDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgExecutionTime',
            arguments: [tableNamePrefix, startDate, endDate, environment, groupName, isVerticalDataActive]
        },
        success: function (result) {
            var resultsData = 0;
            var environmentValue = 0;
            var groupNameValue = 0;
            var categoriesData = 0;
            var datasetData = 0;
            var trendLineAvg = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "categories")
                    categoriesData = value;
                    if (key === "dataset")
                        datasetData = value;
                    if (key === "environmentValue")
                        environmentValue = value;
                    if (key === "groupNameValue")
                        groupNameValue = value;
                    if (key === "trendLineAvg")
                        trendLineAvg = value;
                });
            }

            if (groupNameValue === "")
            {
                categoriesData = 0;
                datasetData = 0;
            }
            var chartProperties = {
                "caption": "Average '"+groupNameValue+"' execution time for last " + timeFilter + " days [on "+environmentValue+"]",
                "rotatevalues": "0",
                "showvalues": "1",
                "theme": theme,
                "trendValueFont": "Arial",
                "trendValueFontSize": "13",
                "trendValueFontBold": "1",
                "trendValueFontItalic": "1",
                "trendValueAlpha": "70",
                "trendValueBorderColor": "ff0000",
                "trendValueBorderAlpha": "80",
                "trendValueBorderPadding": "2",
                "trendValueBorderRadius": "3",
                "trendValueBorderThickness": "1",
                "trendValueBorderDashed": "0",
                "trendValueBorderDashLen": "#123456",
                "trendValueBorderDashGap": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")"
            };

            apiChart = new FusionCharts({
                type: 'mscombi2d',
                renderAt: 'column-chart-container'+chartNum,
                width: '96%',
                height: '350',
                dataFormat: 'json',
                dataSource: {
                    "chart": chartProperties,
                    "dataset": datasetData,
                    "categories": categoriesData,
                    "trendlines": [{
                "line": [{
                  "startvalue": trendLineAvg,
                  "displayvalue": "Avg: "+trendLineAvg,
                  "valueOnRight": "0",
                  "thickness": "3",
                  "dashed": "1",
                  "alpha": "70",
                  "tooltext": "Average: $startvalue"
                }]
            }]
                }
            });
            apiChart.render();
        }
    });
};

function fetchAvgPercForProject_GaugeChart(tableNamePrefix, projectName, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, environment4, groupName4, isVerticalDataActive) {
    // Build arguments array - include 4th group only if provided
    var ajaxArguments = [tableNamePrefix, projectName, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3];
    if (environment4 && groupName4) {
        ajaxArguments.push(environment4, groupName4);
    }
    ajaxArguments.push(isVerticalDataActive);
    
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getAvgPercentage_Project',
            arguments: ajaxArguments
        },
        success: function (result) {
            var resultValue1 = 0;
            var resultValue2 = 0;
            var resultValue3 = 0;
            var resultValue4 = 0;
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === environment1+"-"+groupName1)
                        resultValue1 = value;
                    if (key === environment2+"-"+groupName2)
                        resultValue2 = value;
                    if (key === environment3+"-"+groupName3)
                        resultValue3 = value;
                    if (environment4 && groupName4 && key === environment4+"-"+groupName4)
                        resultValue4 = value;
                });
            }
            enableGaugeChart(resultValue1, groupName1, environment1, 1); 
            enableGaugeChart(resultValue2, groupName2, environment2, 2); 
            enableGaugeChart(resultValue3, groupName3, environment3, 3);
            // Only render 4th gauge chart if 4th group is provided
            if (environment4 && groupName4) {
                enableGaugeChart(resultValue4, groupName4, environment4, 4); 
            }
        }
    });
}

function fetchLastSevenResults_ColumnChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, environment, groupName, chartNum) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getLast7Records',
            arguments: [tableNamePrefix, projectName, startDate, endDate, environment, groupName]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });
            var linkSeperator = ", link- ";
            const fullData = JSON.parse(JSON.stringify(categoriesData[0].category));

            var chartProperties = {
                "caption": "Latest '"+groupName+"' builds for last " + timeFilter + " days on "+environment+" [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "yAxisName": "Percentage",
                "placevaluesinside": "1",
                "rotatevalues": "0",
                "showvalues": "1",
                "plottooltext": "$label: $dataValue%",
                "theme": theme,
                "scrollheight": "12",
                "flatScrollBars": "1",
                "scrollShowButtons": "1",
                "numvisibleplot": "15",
                "drawCrossLine": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")"
            };

            apiChart = new FusionCharts({
                type: 'scrollcolumn2d',
                renderAt: 'column-chart-container' + chartNum,
                width: '96%',
                height: '350',
                dataFormat: 'json',
                "events": {
                    "beforeInitialize": function (eventObj, dataObj) {
                        for (var i = 0; i < categoriesData[0].category.length; i++) {
                          oldValue = categoriesData[0].category[i].label;
                          categoriesData[0].category[i].label = oldValue.split(linkSeperator)[0];
                        }
                    },
                    "beforeRender": function (e, d) {

                        if(!projectName.includes('Vertical -'))
                        {
                            var messageBlock = document.createElement('p');
                            messageBlock.style.textAlign = "center";
                            var activatedMessage = 'Click on the respective column to get Build Link';
                            var getClickedMessage = function (categoryLabel, displayValue) {
                                var fullLabel = fullData.find(v => v.label.toString().includes(categoryLabel)).label;
                                var resultLink = fullLabel.split(linkSeperator)[1];
                                categoryLabel = categoryLabel.substring(categoryLabel.lastIndexOf("\n") + 1);
                                return 'Results Link for <B>"' + categoryLabel + '"</B> - <a style="color:yellow" href="' + resultLink + '" target="_blank">' + resultLink + '</a>';
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
                },
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

function fetchAvgDailyPercentage_LineChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, environment4, groupName4) {
    // Build arguments array - include 4th group only if provided
    var ajaxArguments = [tableNamePrefix, projectName, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3];
    if (environment4 && groupName4) {
        ajaxArguments.push(environment4, groupName4);
    }
    
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgPercentage_Project',
            arguments: ajaxArguments
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily percentage for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subCaption": "",
                "plottooltext": "$seriesName percentage: $dataValue%",
                "yAxisName": "Percentage",
                "theme": theme,
                "showValues": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container1',
                width: '96%',
                height: '350',
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

function fetchAvgDailyExecutionTime_LineChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, environment4, groupName4) {
    // Build arguments array - include 4th group only if provided
    var ajaxArguments = [tableNamePrefix, projectName, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3];
    if (environment4 && groupName4) {
        ajaxArguments.push(environment4, groupName4);
    }
    
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getDailyAvgExecutionTime_Project',
            arguments: ajaxArguments
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Average daily Execution Time for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subCaption": "",
                "plottooltext": "$seriesName time: $dataValue minutes",
                "yAxisName": "Time Taken (in minutes)",
                "theme": theme,
                "showValues": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container2',
                width: '96%',
                height: '350',
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

function fetchTotalCasesGroupwise_LineChart(tableNamePrefix, projectName, timeFilter, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3, environment4, groupName4) {
    // Build arguments array - include 4th group only if provided
    var ajaxArguments = [tableNamePrefix, projectName, startDate, endDate, environment1, groupName1, environment2, groupName2, environment3, groupName3];
    if (environment4 && groupName4) {
        ajaxArguments.push(environment4, groupName4);
    }
    
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getTotalCasesGroupwise_Project',
            arguments: ajaxArguments
        },
        success: function (result) {
            $.each(result, function (key, value) {
                if (key === "categories")
                    categoriesData = value;
                if (key === "dataset")
                    datasetData = value;
            });

            var chartProperties = {
                "caption": "Tag/Group wise total cases for last " + timeFilter + " days [" + getAllProjectsLabel(projectName, '#projectNamesDropdown') + "]",
                "subCaption": "",
                "plottooltext": "$seriesName testcases: $dataValue",
                "yAxisName": "Total Testcases",
                "theme": theme,
                "showValues": "1",
                "toolTipBgcolor": "#484E69",
                "toolTipPadding": "7",
                "toolTipBorderRadius": "3",
                "toolTipBorderAlpha": "30",
                "tooltipBorderThickness": "0.7",
                "toolTipColor": "#FDFDFD",
                "subcaption": "Data shown for period (" + startDate + " → " + endDate + ")"
            };
            apiChart = new FusionCharts({
                type: 'msline',
                renderAt: 'line-chart-container3',
                width: '96%',
                height: '350',
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

function adjustChartSizes() {
    var visibleCharts = 0;
    var chartContainers = [];
    
    // Count visible charts and collect their containers
    for (var i = 1; i <= 4; i++) {
        var parentElement = document.getElementById("parent" + i);
        if (parentElement) {
            var isVisible = window.getComputedStyle(parentElement).display !== "none" && 
                           parentElement.style.display !== "none";
            if (isVisible) {
                visibleCharts++;
                chartContainers.push(parentElement);
            }
        }
    }
    
    // Remove existing dynamic classes from all chart containers
    for (var i = 1; i <= 4; i++) {
        var parentElement = document.getElementById("parent" + i);
        if (parentElement) {
            parentElement.classList.remove('chart-3-columns', 'chart-4-columns');
        }
    }
    
    // Apply appropriate classes based on visible chart count
    if (visibleCharts === 4) {
        chartContainers.forEach(function(container) {
            container.classList.add('chart-4-columns');
        });
        console.log('Applied 4-column layout to', visibleCharts, 'charts');
    } else if (visibleCharts > 0) {
        // For 3 or fewer charts, use the 3-column layout
        chartContainers.forEach(function(container) {
            container.classList.add('chart-3-columns');
        });
        console.log('Applied 3-column layout to', visibleCharts, 'charts');
    }
}

function enableGaugeChart(result, groupName, environment, placeholderNum) 
{
    // Show the parent container for this gauge chart
    var parentElement = document.getElementById("parent" + placeholderNum);
    if (parentElement) {
        parentElement.style.display = "block";
    }
    
    // Adjust chart sizes after showing this chart
    setTimeout(adjustChartSizes, 100);

    document.getElementById("gauge"+placeholderNum).innerHTML = groupName.charAt(0).toUpperCase() + groupName.slice(1)+" Pass Percentage";
    //document.getElementById("gauge"+placeholderNum).innerHTML = groupName.charAt(0).toUpperCase() + groupName.slice(1)+" Pass Percentage ["+environment+"]";
    var totalCases = 0;
    var passedCases = 0;
    var percentage = 0;

    for (i = 0; i < result.length; i++) {
        $.each(result[i], function (key, value) {
            if (key === "totalCases")
                totalCases = value;
            if (key === "passedCases")
                passedCases = value;
            if (key === "percentage")
                percentage = value;
        });
    }

    var chartProperties = {
        "baseFont": "Nunito Sans",
        "setAdaptiveMin": "1",
        "baseFontColor": "#ffffff",
        "chartTopMargin": "0",
        "canvasTopMargin": "0",
        "chartBottomMargin": is4ColumnLayout ? "5" : "10",
        "chartLeftMargin": is4ColumnLayout ? "10" : "20",
        "chartRightMargin": is4ColumnLayout ? "10" : "20",
        "showTickMarks": "0",
        "showTickValues": "0",
        "showLimits": "0",
        "majorTMAlpha": "0",
        "minorTMAlpha": "0",
        "pivotFillAlpha": "0",
        "showPivotBorder": "0",
        "gaugeouterradius": "100",
        "gaugeInnerradius": "80",
        "showGaugeBorder": "0",
        "gaugeFillMix": "{light+0}",
        "showBorder": "0",
        "theme": theme
    };

    // Check if this is a 4-column layout to adjust chart sizing
    var parentElement = document.getElementById("parent" + placeholderNum);
    var is4ColumnLayout = parentElement && parentElement.classList.contains('chart-4-columns');
    
    apiChart = new FusionCharts({
        type: 'angulargauge',
        renderAt: 'gauge-chart-container'+placeholderNum,
        width: is4ColumnLayout ? '100%' : '92%',
        height: is4ColumnLayout ? '120' : '130',
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "annotations": {
                "groups": [{
                    "items": [
                    {
                        "id": "2",
                        "type": "text",
                        "text": percentage+'%',
                        "align": "center",
                        "font": "arial black",
                        "bold": "1",
                        "fontSize": "26",
                        "color": fontColor,
                        "x": "$chartcenterX",
                        "y": "$chartCenterY"
                    },
                    {
                        "id": "3",
                        "type": "text",
                        "text": passedCases+" / "+totalCases,
                        "align": "center",
                        "font": "arial black",
                        "bold": "0",
                        "fontSize": "14",
                        "color": fontColor,
                        "x": "$chartcenterX",
                        "y": "$chartCenterY + 45"
                    }]
                }]
            },
            "colorRange": {
                "color": [{
                    "minValue": "0",
                    "maxValue": percentage,
                    "code": "#58E2C2"
                },
                {
                    "minValue": percentage,
                    "maxValue": "100",
                    "code": "#e6e6e6"
                }
                ]
            },
            "dials": {
                "dial": [{
                    "value": percentage,
                    "alpha": "0",
                    "borderAlpha": "0",
                    "radius": "0",
                    "baseRadius": "0",
                    "rearExtension": "0",
                    "baseWidth": "0",
                    "showValue": "0"               
                }]
            }
        }
    });
    apiChart.render();
}
