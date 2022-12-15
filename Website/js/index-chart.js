var defaultFilter = "7";
var verticalName = "";
var projectName = "";
var isPodDataActive = "";
var fontColor = "#ffffff";
var theme = "candy";
var tableName = "";
$(function () {
    verticalName = $("#verticalName").html().trim();
    if (verticalName != "" && verticalName != null) {
        setDataIntoStorage("entity", verticalName);
    } else {
        verticalName = getDataFromStorage("entity");
        if (verticalName != "" && verticalName != null) {
            $("#verticalName").html(verticalName);
        } else {
            redirectToHomePage();
        }
    }
    setStartEndDates();
    isPodDataActive = getDataFromStorage("podview");
    tableName = getDataFromVerticalTable(verticalName, "tableNamePrefix");
    fetchActiveTabs(verticalName);
    activateFilter(verticalName);
});

$(document).ready(function () {
    $("#projectName").click(function () {
        window.location.href = pageName;
    });

   $("#qaDashboard").click(function () {
        setDataIntoStorage("entity", "");
    });

    $("#verticalName").click(function () {
        resetData();
    });

    $("#dash").click(function () {
        resetData();
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

function resetData()
{
    setDataIntoStorage("entity", "");
    setDataIntoStorage("startDate", "");
    setDataIntoStorage("endDate", "");
    setDataIntoStorage("podview", '0');
    setDataIntoStorage("darkmode", '1');
    setDataIntoStorage("country","");
    setDataIntoStorage("platform","");
    setDataIntoStorage("environment","");
    saveFilter("7");
    document.getElementById("dash").innerHTML = "";
    document.getElementById("verticalName").style.textTransform = "lowercase";
    $("#verticalName").html("<b><font color='yellow'>Redirecting back to select your entity / vertical first...</font></b>");
    window.setTimeout(redirectToHomePage, 1000);
}

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

function saveFilter(value) {
    setDataIntoStorage("filter", value);
}

function getFilter() {
    return getDataFromStorage("filter");
}

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
    var i = 0;
    var x = document.querySelectorAll("span.fusioncharts-container svg:nth-child(1) g:nth-child(3) > text:nth-child(1)");
    while(i < x.length)
    {
        if(x[i].textContent === 'No data to display.')
        {
            x[i].parentNode.parentNode.parentNode.parentNode.classList.add("hide"); 
            console.log("One of the chart is hidden");
        }
        i++;
    }
    var i = 0;
    var y = document.querySelectorAll("span.fusioncharts-container svg:nth-child(1) > g:nth-child(3) > g:nth-child(3) > g:nth-child(5) > g:nth-child(1) > text:nth-child(1)");
    while(i < y.length)
    {
        if(y[i].textContent === 'No data to display.')
        {
            y[i].parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.classList.add("hide");
            console.log("One of the chart is hidden"+y[i].textContent);
        }
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
    fetchProjectNames(tableName, startDate, endDate, isPodDataActive);

    projectName = $("#projectName").html().trim();
    if (projectName.length != 0) {
        showProjectCharts(verticalName, tableName, projectName, timeFilter, startDate, endDate);
    } else {
        showDefaultCharts(verticalName, tableName, timeFilter, startDate, endDate);
    }
}

function fetchProjectNames(tableName, startDate, endDate, isPodDataActive) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProjectNames',
            arguments: [tableName, startDate, endDate, isPodDataActive]
        },
        success: function (result) {
            $.each(result, function (key, value) {
                $("#projectNamesDropdown").append($("<option />").val(value).text(value));
            });
            $(".chosen-select").trigger("chosen:updated");
        }
    });
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