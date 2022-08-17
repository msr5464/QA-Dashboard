var defaultFilter = "7";
var verticalName = "";
var projectName = "";
var isPodDataActive = "";
var fontColor = "#ffffff";
var theme = "candy";

$(function () {
    verticalName = $("#verticalName").html().trim();
    if (verticalName != "" && verticalName != null) {
        setDataIntoStorage("pdg", verticalName);
    } else {
        verticalName = getDataFromStorage("pdg");
        if (verticalName != "" && verticalName != null) {
            $("#verticalName").html(verticalName);
        } else {
            redirectToHomePage();
        }
    }
    setStartEndDates();
    isPodDataActive = getDataFromStorage("podview");
    fetchActiveTabs(verticalName);
    activateFilter(verticalName);
});

$(document).ready(function () {
    $("#projectName").click(function () {
        window.location.href = pageName;
    });

   $("#qaDashboard").click(function () {
        setDataIntoStorage("pdg", "");
    });

    $("#verticalName").click(function () {
        setDataIntoStorage("pdg", "");
        setDataIntoStorage("startDate", "");
        setDataIntoStorage("endDate", "");
        setDataIntoStorage("podview", '0');
        setDataIntoStorage("darkmode", '1');
        saveFilter("7");
        document.getElementById("dash").innerHTML = "";
        document.getElementById("verticalName").style.textTransform = "lowercase";
        $("#verticalName").html("<b><font color='yellow'>Redirecting back to select a vertical name first...</font></b>");
        window.setTimeout(redirectToHomePage, 1000);
    });

    $(".filter").click(function () {
        $(".filter").removeClass("active");
        $(this).addClass("active");
    });

    $("#last1days").click(function () {
        saveFilter("1");
        validateAndExecute(verticalName, getFilter());
    });

    $("#last7days").click(function () {
        saveFilter("7");
        validateAndExecute(verticalName, getFilter());
    });

    $("#last15days").click(function () {
        saveFilter("15");
        validateAndExecute(verticalName, getFilter());
    });

    $("#last30days").click(function () {
        saveFilter("30");
        validateAndExecute(verticalName, getFilter());
    });

    $("#last90days").click(function () {
        saveFilter("90");
        validateAndExecute(verticalName, getFilter());
    });

    $("#applyDateFilter").click(function () {
        saveFilter("N");
        validateAndExecute(verticalName, getFilter());
    });
});

function redirectToHomePage() {
    window.location.href = 'index.php';
};

function activateFilter(verticalName) {
    var currentFilter = getFilter();
    if (!currentFilter) {
        saveFilter(defaultFilter);
        currentFilter = defaultFilter;
    }
    switch (currentFilter) {
        case '1':
            document.getElementById("last1days").classList.add("active");
            break;
        case '7':
            document.getElementById("last7days").classList.add("active");
            break;
        case '15':
            document.getElementById("last15days").classList.add("active");
            break;
        case '30':
            document.getElementById("last30days").classList.add("active");
            $("#filters").animate({scrollLeft: $('#last30days').position().left}, 500);
            break;
        case '90':
            document.getElementById("last90days").classList.add("active");
            $("#filters").animate({scrollLeft: $('#last90days').position().left}, 500);
            break;
        case 'N':
            document.getElementById("lastNdays").classList.add("active");
            $("#filters").animate({scrollLeft: $('#lastNdays').position().left}, 500);
            break;
    }
    validateAndExecute(verticalName, currentFilter);
}

function setCookie(cname, cvalue) {
    var d = new Date(new Date().getFullYear(), 11, 31);
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${cname}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function saveFilter(value) {
    setDataIntoStorage("filter", value);
}

function getFilter() {
    return getDataFromStorage("filter");
}

function setDataIntoStorage(key, value) {
    setCookie(key, value);
}

function getDataFromStorage(key) {
    var localValue = getKeyValueFromUrl(key, "");
    if (localValue != "" && localValue != null)
    {
        setDataIntoStorage(key,localValue);
        return localValue;
    }
    else
    {
        localValue = getCookie(key);
        if (localValue != "" && localValue != null)
            return localValue;
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

function hideProjectCharts() {
    $(".defaultChart").show();
    $("#projectName").hide();
    $(".projectChart").hide();
    $("#warning").show();
    $("#selectProject").show();
    $("#projectName").html("");
}

function hideDefaultCharts() {
    $("#selectProject").hide();
    $("#warning").hide();
    $(".defaultChart").hide();
}

function hideBlankCharts() {
    var i =0;
    var x = document.querySelectorAll("span.fusioncharts-container svg:nth-child(1) g:nth-child(3) > text:nth-child(1)");
    while(i < x.length)
    {
        x[i].parentNode.parentNode.parentNode.parentNode.classList.add("hide"); 
        console.log("One of the chart is hidden");
        i++;
    }
}

function validateAndExecute(verticalName, timeFilter) {
    //Show Loader
    $(".gauge").html('<img src="../images/loader.gif" height="100" />');
    $(".linearChart").html("");
    
    //Toggle Dark Mode settings
    var darkMode = getDataFromStorage("darkmode");
    if (darkMode != "" && darkMode != null && darkMode === "0") {
        $(".chart-card").addClass("greyBackground");
        $(".gaugeContainer").addClass("whiteBackground");
        theme="fusion";
        fontColor = "#000000";
    }

    if(timeFilter === "N") {
        var s = new Date(document.getElementById("startDate").value);
        var e = new Date(document.getElementById("endDate").value);
        setDataIntoStorage("startDate", s);
        setDataIntoStorage("endDate", e);
    }
    else {
        var today = new Date();
        today.setDate(today.getDate() - timeFilter);
        var s = today;
        var e = new Date();;
    }
    var startDate = s.toISOString().slice(0, 10);
    var endDate = e.toISOString().slice(0, 10);
    
    $("#projectNamesDropdown").empty();
    $(".chosen-select").chosen();
    fetchProjectNames(verticalName, startDate, endDate, isPodDataActive);

    projectName = $("#projectName").html().trim();
    if (projectName.length != 0) {
        showProjectCharts(verticalName, projectName, timeFilter, startDate, endDate);
    } else {
        showDefaultCharts(verticalName, timeFilter, startDate, endDate);
    }
}

function fetchProjectNames(verticalName, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProjectNames',
            arguments: [verticalName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                $("#projectNamesDropdown").append($("<option />").val(value).text(value));
            });
            $(".chosen-select").trigger("chosen:updated");
        }
    });
};

function fetchActiveTabs(verticalName) {
    if (getDataFromVerticalTable(verticalName, "isResultsActive") == 1)
        $("#results").show();
    if (getDataFromVerticalTable(verticalName, "isTestrailActive") == 1)
        $("#testrail").show();
    if (getDataFromVerticalTable(verticalName, "isJiraActive") == 1)
        $("#jira").show();
    if (getDataFromVerticalTable(verticalName, "isUnitTestsActive") == 1)
        $("#units").show();
};

function getDataFromVerticalTable(verticalName, columnName) {
    var columnValue="";
    for (i = 0; i < verticalTableData.length; i++) {
        $.each(verticalTableData[i], function (key, value) {
            if(key === "verticalName" && value === verticalName) {
                $.each(verticalTableData[i], function (key, value) {
                if (key === columnName)
                    columnValue = value;
                });
            }
        });
    }
    return columnValue;
};

function setStartEndDates() {
    var finalStartDate = new Date();
    var finalEndDate = new Date();
    var cachedStartDate = getDataFromStorage("startDate");
    var cachedEndDate = getDataFromStorage("endDate");
    if (cachedStartDate != "" && cachedStartDate != null) {
        finalStartDate = new Date(cachedStartDate);
        if (cachedEndDate != "" && cachedEndDate != null) {
            finalEndDate = new Date(cachedEndDate);
        }
    }
    else {
        finalStartDate.setMonth(finalStartDate.getMonth() - 6);
    }

    var startDate = document.getElementById("startDate");
    startDate.setAttribute("value", finalStartDate.toISOString().slice(0, 10));
    var endDate = document.getElementById("endDate");
    endDate.setAttribute("value", finalEndDate.toISOString().slice(0, 10));
};