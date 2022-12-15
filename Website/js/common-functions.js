
function fetchActiveTabs(verticalName) {
    if (getDataFromVerticalTable(verticalName, "showAutomationStability") == 1)
        $("#results").show();
    if (getDataFromVerticalTable(verticalName, "showTestCoverage") == 1)
        $("#testrail").show();
    if (getDataFromVerticalTable(verticalName, "showBugMetrics") == 1)
        $("#jira").show();
    if (getDataFromVerticalTable(verticalName, "showCodeCoverage") == 1)
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

function setDataIntoStorage(key, value) {
    if (/^([a-zA-Z0-9 :+()_-]+)$/.test(value))
        setCookie(key, value);
    else
        setCookie(key, "");
}

function getDataFromStorage(key) {
    var localValue = getKeyValueFromUrl(key, "");
    if (localValue != "" && localValue != null)
    {
        if (/^([a-zA-Z0-9 :+()_-]+)$/.test(localValue))
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
            if (/^([a-zA-Z0-9 :+()_-]+)$/.test(localValue))
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
    var d = new Date(new Date().getFullYear(), 11, 31);
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
        if (/^([a-zA-Z0-9 :+()_-]+)$/.test(sessionValue))
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

function redirectToHomePage() {
    window.location.href = 'index.php';
};
