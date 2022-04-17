var x = getCookie('selectedYear');
if (x)
{
   document.getElementById("selectedYear").value = x;
}
var y = getCookie('selectedVertical');
if (y)
{
   document.getElementById("verticalName").value = y;
}
  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}