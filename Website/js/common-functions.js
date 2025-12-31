function fetchActiveTabs(entityName) {
    if (getDataFromEntityTable(entityName, "showAutomationStability") == 1)
        $("#results").show();
    if (getDataFromEntityTable(entityName, "showFctTests") == 1)
        $("#fct-tests").show();
    if (getDataFromEntityTable(entityName, "showFctTestsAuto") == 1)
        $("#fct-tests-auto").show();
    if (getDataFromEntityTable(entityName, "showAllTests") == 1)
        $("#all-tests").show();
    if (getDataFromEntityTable(entityName, "showAllTestsAuto") == 1)
        $("#all-tests-auto").show();
    if (getDataFromEntityTable(entityName, "showTotalBugs") == 1)
        $("#total-bugs").show();
    if (getDataFromEntityTable(entityName, "showProdBugs") == 1)
        $("#prod-bugs").show();
    if (getDataFromEntityTable(entityName, "showFctBugs") == 1)
        $("#fct-bugs").show();
    if (getDataFromEntityTable(entityName, "showStagingBugs") == 1)
        $("#staging-bugs").show();
};

function getDataFromEntityTable(entityName, columnName) {
    var columnValue="";
    for (i = 0; i < entityTableData.length; i++) {
        $.each(entityTableData[i], function (key, value) {
            if(key === "entityName" && value === entityName) {
                $.each(entityTableData[i], function (key, value) {
                if (key === columnName)
                    columnValue = value;
                });
            }
        });
    }
    return columnValue;
};

function setDataIntoStorage(key, value) {
    if (/^([a-zA-Z0-9& :+()_-]+,)*[a-zA-Z0-9& :+()_-]+$/.test(value))
        setCookie(key, value);
    else
        setCookie(key, "");
}

function getDataFromStorage(key) {
    var localValue = getKeyValueFromUrl(key, "");
    if (localValue != "" && localValue != null)
    {
        if (/^([a-zA-Z0-9& :+()_-]+,)*[a-zA-Z0-9& :+()_-]+$/.test(localValue))
        {
            setDataIntoStorage(key,localValue);
            return localValue;
        }
        else
        {
            return "";
        }
    }
    else
    {
        localValue = getCookie(key);
        if (localValue != "" && localValue != null)
        {
            if (/^([a-zA-Z0-9& :+()_-]+,)*[a-zA-Z0-9& :+()_-]+$/.test(localValue))
            {
                return localValue;
            }
            else
            {
                deleteCookie(key);
                return "";
            }
        }    
        else
            return "";
    }
}

function getKeyValueFromUrl(key, defaultValue) {
    var url_string = window.location.href;
    var url = new URL(url_string);
    var value = url.searchParams.get(key);
    if (value != null)
        return value;
    else
        return defaultValue;
};

function setCookie(key, value) {
    sessionStorage.setItem(key, value);
    var d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    var expires = "expires=" + d.toUTCString();
    document.cookie = key + "=" + value + ";" + expires + ";path=/";
}


function deleteCookie(key) {
    sessionStorage.setItem(key, "");
    document.cookie = key +'=; path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function getCookie(key) {
    var sessionValue = sessionStorage.getItem(key);
    if (sessionValue != "" && sessionValue != null)
    {
        if (/^([a-zA-Z0-9& :+()_-]+,)*[a-zA-Z0-9& :+()_-]+$/.test(sessionValue))
        {
            return sessionValue;
        }
        else
        {
            deleteCookie(key);
            return "";
        }
    }
    else
    {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${key}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
}

// To convert "&amp;" to "&"
function decodeHtmlEntities(encodedString) {
    let parser = new DOMParser();
    let decodedString = parser.parseFromString(encodedString, "text/html").body.textContent;
    return decodedString;
}

function redirectToHomePage() {
    window.location.href = 'index.php';
};

function enableAllVerticalsDropdown(dropdownSelector) {
    // Add 'All Verticals' at the top if not present
    if ($(dropdownSelector + ' option[value="ALL_VERTICALS"]').length === 0) {
        $(dropdownSelector).prepend($('<option />').val('ALL_VERTICALS').text('All Verticals'));
    }
    // Remove previous handler to avoid duplicates
    $(dropdownSelector).off('change.allVerticals').on('change.allVerticals', function() {
        var selected = $(this).val();
        if (selected && selected.includes('ALL_VERTICALS')) {
            var allVerticals = [];
            $(dropdownSelector + ' option').each(function() {
                var val = $(this).val();
                if (val.startsWith('Vertical')) {
                    allVerticals.push(val);
                }
            });
            $(this).val(allVerticals).trigger('chosen:updated');
        }
    });
}

/**
 * Returns a label for the selected projects for chart titles.
 * If all verticals are selected, returns ['All Projects'], else returns the project names as [A, B, ...].
 * @param {string} projectName - Comma-separated project names (may be quoted)
 * @param {string} dropdownSelector - jQuery selector for the project dropdown
 * @returns {string}
 * Usage: getAllProjectsLabel(projectName, '#projectNamesDropdown')
 */
function getAllProjectsLabel(projectName, dropdownSelector) {
    var projectNamesArr = projectName.replace(/'/g, "").split(",").map(function(x){return x.trim();});
    var allVerticalsArr = [];
    $(dropdownSelector + ' option').each(function(){
        var val = $(this).val();
        if(val && val.startsWith('Vertical')) allVerticalsArr.push(val);
    });
    var allVerticalsSelected = projectNamesArr.length === allVerticalsArr.length && projectNamesArr.every(function(x){return allVerticalsArr.includes(x);});
    return allVerticalsSelected ? "All Verticals" : projectNamesArr.join(", ");
}

/**
 * ============================================================================
 * Common Chart Functions and Properties
 * ============================================================================
 * This section contains reusable chart rendering functions and common chart 
 * properties to reduce code duplication across bug chart files.
 */

/**
 * Common tooltip properties used across all charts
 */
var COMMON_TOOLTIP_PROPERTIES = {
    "toolTipBgcolor": "#484E69",
    "toolTipPadding": "7",
    "toolTipBorderRadius": "3",
    "toolTipBorderAlpha": "30",
    "tooltipBorderThickness": "0.7",
    "toolTipColor": "#FDFDFD"
};

/**
 * Common trendline properties used across charts with trendlines
 */
var COMMON_TRENDLINE_PROPERTIES = {
    "trendValueFont": "Arial",
    "trendValueFontSize": "13",
    "trendValueFontBold": "1",
    "trendValueFontItalic": "1",
    "trendValueAlpha": "70",
    "trendValueBorderColor": "ff0000",
    "trendValueBorderAlpha": "80",
    "trendValueBorderPadding": "2",
    "trendValueBorderRadius": "3",
    "trendValueBorderThickness": "2",
    "trendValueBorderDashed": "0",
    "trendValueBorderDashLen": "#123456",
    "trendValueBorderDashGap": "1"
};

/**
 * Common scroll chart properties for trend charts
 */
var COMMON_SCROLL_CHART_PROPERTIES = {
    "scrollheight": "12",
    "flatScrollBars": "1",
    "scrollShowButtons": "1",
    "numvisibleplot": "50"
};

/**
 * Extract chart data from AJAX result
 * @param {Object} result - AJAX response object
 * @returns {Object} Object with categories, dataset, and optional trendLine
 */
function extractChartData(result) {
    var chartData = {
        categories: null,
        dataset: null,
        trendLine: null
    };
    
    $.each(result, function (key, value) {
        if (key === "categories") chartData.categories = value;
        if (key === "dataset") chartData.dataset = value;
        if (key === "trendLineQualityScoreAvg" || key === "trendLineCountAvg") {
            chartData.trendLine = value;
        }
    });
    
    return chartData;
}

/**
 * Create base chart properties with common settings
 * @param {string} caption - Chart caption
 * @param {string} subcaption - Chart subcaption
 * @param {string} yAxisName - Y-axis label
 * @param {Object} additionalProperties - Additional properties to merge
 * @returns {Object} Chart properties object
 */
function createBaseChartProperties(caption, subcaption, yAxisName, additionalProperties) {
    var baseProperties = {
        "caption": caption,
        "subcaption": subcaption,
        "yAxisName": yAxisName,
        "drawCrossLine": "1",
        "theme": theme,
        "showValues": "1",
        "rotatevalues": "0"
    };
    
    // Merge common tooltip properties
    Object.assign(baseProperties, COMMON_TOOLTIP_PROPERTIES);
    
    // Merge additional properties if provided
    if (additionalProperties) {
        Object.assign(baseProperties, additionalProperties);
    }
    
    return baseProperties;
}

/**
 * Render a FusionCharts chart
 * @param {string} chartType - FusionCharts chart type (e.g., 'scrollcolumn2d', 'stackedcolumn2d')
 * @param {string} containerId - Container element ID where chart will be rendered
 * @param {Object} chartProperties - Chart properties object
 * @param {Array} dataset - Chart dataset array
 * @param {Array} categories - Chart categories array
 * @param {Object} trendlines - Optional trendlines object
 * @param {number} height - Optional chart height (default: 400)
 * @returns {Object} FusionCharts instance
 */
function renderChart(chartType, containerId, chartProperties, dataset, categories, trendlines, height) {
    height = height || 400;
    var dataSource = {
        "chart": chartProperties,
        "dataset": dataset,
        "categories": categories
    };
    
    // Add trendlines if provided
    if (trendlines) {
        dataSource.trendlines = trendlines;
    }
    
    var chartInstance = new FusionCharts({
        type: chartType,
        renderAt: containerId,
        width: '96%',
        height: height.toString(),
        dataFormat: 'json',
        dataSource: dataSource
    });
    
    chartInstance.render();
    return chartInstance;
}

/**
 * Bug Count Chart - Renders a scrollable column chart for bug counts
 * Used in: prod-bugs-chart.js, fct-bugs-chart.js, staging-bugs-chart.js
 * @param {string} timeFilter - Time filter description
 * @param {Array} datasetValue - Chart dataset
 * @param {Array} categoriesValue - Chart categories
 * @param {string} message - Chart caption
 * @param {number} chartNum - Chart container number
 * @param {string} startDate - Start date
 * @param {string} endDate - End date
 */
function bugCountChart(timeFilter, datasetValue, categoriesValue, message, chartNum, startDate, endDate) {
    var chartProperties = createBaseChartProperties(
        message,
        "Data shown for period (" + startDate + " → " + endDate + ")",
        "Number of Bugs",
        {
            "showsum": "1"
        }
    );
    
    // Add trendline properties
    Object.assign(chartProperties, COMMON_TRENDLINE_PROPERTIES);
    
    renderChart(
        'scrollcolumn2d',
        'column-chart-container' + chartNum,
        chartProperties,
        datasetValue,
        categoriesValue
    );
}

/**
 * Bug SLA Chart - Renders a scrollable column chart for bug SLA percentages
 * Used in: prod-bugs-chart.js, fct-bugs-chart.js, staging-bugs-chart.js
 * @param {string} timeFilter - Time filter description
 * @param {Array} datasetValue - Chart dataset
 * @param {Array} categoriesValue - Chart categories
 * @param {string} message - Chart caption
 * @param {number} chartNum - Chart container number
 * @param {string} startDate - Start date
 * @param {string} endDate - End date
 */
function bugSlaChart(timeFilter, datasetValue, categoriesValue, message, chartNum, startDate, endDate) {
    var chartProperties = createBaseChartProperties(
        message,
        "Data shown for period (" + startDate + " → " + endDate + ")",
        "% of Bugs resolved within SLA",
        {
            "showsum": "0",
            "numbersuffix": "%"
        }
    );
    
    // Add trendline properties
    Object.assign(chartProperties, COMMON_TRENDLINE_PROPERTIES);
    
    renderChart(
        'scrollcolumn2d',
        'column-chart-container' + chartNum,
        chartProperties,
        datasetValue,
        categoriesValue
    );
}

/**
 * Bugs Found Chart - Renders a stacked column chart for bugs found by category
 * Used in: total-bugs-chart.js
 * @param {string} timeFilter - Time filter description
 * @param {Array} datasetValue - Chart dataset
 * @param {Array} categoriesValue - Chart categories
 * @param {string} message - Chart caption
 * @param {number} chartNum - Chart container number
 * @param {string} startDate - Start date
 * @param {string} endDate - End date
 */
function bugsFoundChart(timeFilter, datasetValue, categoriesValue, message, chartNum, startDate, endDate) {
    var chartProperties = createBaseChartProperties(
        message,
        "Data shown for period (" + startDate + " → " + endDate + ")",
        "Number of Bugs",
        {
            "plottooltext": "$seriesName: $dataValue",
            "showsum": "1"
        }
    );
    
    // Add trendline properties
    Object.assign(chartProperties, COMMON_TRENDLINE_PROPERTIES);
    
    renderChart(
        'stackedcolumn2d',
        'column-chart-container' + chartNum,
        chartProperties,
        datasetValue,
        categoriesValue
    );
}

/**
 * Create chart properties for trend charts (with scroll functionality)
 * @param {string} caption - Chart caption
 * @param {string} subcaption - Chart subcaption
 * @param {string} yAxisName - Y-axis label
 * @param {Object} additionalProperties - Additional properties to merge
 * @returns {Object} Chart properties object
 */
function createTrendChartProperties(caption, subcaption, yAxisName, additionalProperties) {
    var baseProperties = createBaseChartProperties(caption, subcaption, yAxisName, additionalProperties);
    
    // Add scroll properties
    Object.assign(baseProperties, COMMON_SCROLL_CHART_PROPERTIES);
    
    // Add plottooltext if not provided
    if (!baseProperties.plottooltext) {
        baseProperties.plottooltext = "$seriesName: $dataValue";
    }
    
    return baseProperties;
}

/**
 * Render a trend chart with scroll functionality
 * @param {string} chartType - FusionCharts chart type
 * @param {string} containerId - Container element ID
 * @param {string} caption - Chart caption
 * @param {string} subcaption - Chart subcaption
 * @param {string} yAxisName - Y-axis label
 * @param {Array} dataset - Chart dataset
 * @param {Array} categories - Chart categories
 * @param {Object} additionalProperties - Additional chart properties
 * @param {number} height - Chart height (default: 350)
 */
function renderTrendChart(chartType, containerId, caption, subcaption, yAxisName, dataset, categories, additionalProperties, height) {
    height = height || 350;
    var chartProperties = createTrendChartProperties(caption, subcaption, yAxisName, additionalProperties);
    
    var chartInstance = new FusionCharts({
        type: chartType,
        renderAt: containerId,
        width: '96%',
        height: height.toString(),
        dataFormat: 'json',
        dataSource: {
            "chart": chartProperties,
            "dataset": dataset,
            "categories": categories
        }
    });
    
    chartInstance.render();
    return chartInstance;
}
