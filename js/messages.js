
function removeinfo() { 
    setTimeout( function () {
        $("#info").animate({
            top : "-1000px"
        }, 6000, "swing");
    }, 5000);
	setTimeout( function () {
        $("#error").animate({
            top : "-1000px"
        }, 6000, "swing");
    }, 10000);
}
$(document).ready(
    function(){
        removeinfo();
    }
);