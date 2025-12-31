$(function () {
    entityName = getDataFromStorage("entity");
    if (entityName != "" && entityName != null) {
        $("#entityName").html(entityName);
    } else {
        redirectToHomePage();
    }
    isVerticalDataActive = getDataFromStorage("verticalview");
    fetchActiveTabs(entityName);
});

$(document).ready(function () {

   $("#qaDashboard").click(function () {
        setDataIntoStorage("entity", "");
    });
});