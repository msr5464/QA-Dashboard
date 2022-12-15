$(function () {
    verticalName = getDataFromStorage("entity");
    if (verticalName != "" && verticalName != null) {
        $("#verticalName").html(verticalName);
    } else {
        redirectToHomePage();
    }
    isPodDataActive = getDataFromStorage("podview");
    fetchActiveTabs(verticalName);
});

$(document).ready(function () {

   $("#qaDashboard").click(function () {
        setDataIntoStorage("entity", "");
    });
});