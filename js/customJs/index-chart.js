var defaultFilter = "7";
var verticalName = "";
var projectName = "";

$(function () {
    verticalName = $("#verticalName").html().trim();
    if (verticalName != "" && verticalName != null) {
        saveVertical(verticalName);
    } else {
        verticalName = getVertical();
        if (verticalName != "" && verticalName != null) {
            $("#verticalName").html(verticalName);
        } else {
            redirectToHomePage();
        }
    }

    selectedYear = $("#selectedYear").html().trim();
    if (selectedYear != "" && selectedYear != null) {
        saveYear(selectedYear);
    } else {
        selectedYear = getYear();
        $("#selectedYear").html(selectedYear);
    }

    fetchActiveTabs(verticalName);
    fetchProjectNames(verticalName);
    activateFilter(verticalName);
    window.setTimeout(hideBlankCharts, 2500);
});

$(document).ready(function () {
    $("#projectName").click(function () {
        window.location.href = pageName;
    });

    $("#verticalName").click(function () {
        saveVertical("");
        document.getElementById("verticalName").style.textTransform = "lowercase";
        $("#verticalName").html("<b><font color='yellow'>Redirecting back to select a vertical first...</font></b>");
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

    $("#last180days").click(function () {
        saveFilter("180");
        validateAndExecute(verticalName, getFilter());
    });

    $("#last365days").click(function () {
        saveFilter("365");
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
        case '180':
            document.getElementById("last180days").classList.add("active");
            $("#filters").animate({scrollLeft: $('#last180days').position().left}, 500);
            break;
        case '365':
            document.getElementById("last365days").classList.add("active");
            $("#filters").animate({scrollLeft: $('#last365days').position().left}, 500);
            break;
    }
    validateAndExecute(verticalName, currentFilter);
}

function saveVertical(value) {
    localStorage.setItem("selectedVertical", value);
    setCookie("selectedVertical", value);
}

function setCookie(cname, cvalue) {
    var d = new Date();
    d.setTime(d.getTime() + (180 * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getVertical() {
    return localStorage.getItem("selectedVertical");
}

function saveYear(value) {
    localStorage.setItem("selectedYear", value);
    setCookie("selectedYear", value);
}

function getYear() {
    return localStorage.getItem("selectedYear");
}

function saveFilter(value) {
    localStorage.setItem("selectedFilter", value);
}

function getFilter() {
    return localStorage.getItem("selectedFilter");
}

function hideProjectCharts() {
    $(".defaultChart").show();
    $("#projectName").hide();
    $(".projectChart").hide();
    $("#warning").show();
    $("#selectProject").show();
    $("#projectName").html("");
    $(".gauge").html("Project not selected.<br>No data to display!");
    $(".gauge").removeClass("bigFont");
    $(".gauge").addClass("custom-text-2");
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
    projectName = $("#projectName").html().trim();
    if (projectName.length != 0) {
        showProjectCharts(verticalName, projectName, timeFilter);
    } else {
        showDefaultCharts(verticalName, timeFilter);
    }
}

function fetchProjectNames(verticalName) {
    $.ajax({
        url: backend,
        type: 'GET',
        data: {
            functionname: 'getProjectNames',
            arguments: [verticalName]
        },
        success: function (result) {

            $.each(result, function (key, value) {
                $("#projectNames").append($("<option />").val(value).text(value));
            });
        }
    });
};

function fetchActiveTabs(verticalName) {
    $.ajax({
        url: "server/index-data.php",
        type: 'GET',
        data: {
            functionname: 'getActiveTabs',
            arguments: [verticalName]
        },
        success: function (result) {
            for (i = 0; i < result.length; i++) {
                $.each(result[i], function (key, value) {
                    if (key === "isResultsActive" && value === "1")
                        $("#results").show();
                    if (key === "isTestrailActive" && value === "1")
                        $("#testrail").show();
                    if (key === "isJiraActive" && value === "1")
                        $("#jira").show();
                });
            }
        }
    });
};
