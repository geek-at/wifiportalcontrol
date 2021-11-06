$(document).on("click",".disableinternet",function(e) {
    var kl = $(this).attr("klasse");

    $.get( "/backend.php?a=disableaccess&kl="+kl, function( data ) {
        location.reload();
    },'json');

    e.preventDefault();
});


$(document).on("click",".addminutes",function(e) {
    var kl = $(this).attr("klasse");
    var min = $(this).attr("minutes");

    $.get( "/backend.php?a=addminutes&kl="+kl+"&minutes="+min, function( data ) {
        location.reload();
    },'json');

    e.preventDefault();
});

function renderCountdown(target,timeRemainingInMS) {
    if (timeRemainingInMS <= 0) location.reload();
    var endTime = new Date(new Date().getTime() + timeRemainingInMS);
    moment.locale('de');
    $(target).text("Endet "+moment().add(timeRemainingInMS / 1000, 'seconds').fromNow())

    setInterval(function () {
        var countdown = (endTime.getTime() - new Date().getTime()) / 1000;
        if (countdown <= 0) location.reload();
        $(target).text("Endet "+moment().add(countdown, 'seconds').fromNow())
    }, 1000);

}