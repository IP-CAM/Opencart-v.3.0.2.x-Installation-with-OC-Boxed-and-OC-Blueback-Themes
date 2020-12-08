$(window).resize(function () {
    body_width = $('body').width();
    body_height = $('body').height();
    $('#velsof-popup-dialog-loading').css('width', body_width + 'px');
    $('#velsof-popup-dialog-loading').css('height', body_height + 'px');
});
jQuery.browser = {};
(function () {
    jQuery.browser.msie = false;
    jQuery.browser.version = 0;
    if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
        jQuery.browser.msie = true;
        jQuery.browser.version = RegExp.$1;
    }
});
jQuery.curCSS = function(element, prop, val) {
    return jQuery(element).css(prop, val);
};
function displaycartpopup() {
    // alert(stepcheckout_enable);

//    alert($('body').width());
    if (stepcheckout_enable == "1") {
        body_width = $('body').width();
        body_height = $('body').height();
        $("#velsof-popup-dialog-loading").show();
        $('#velsof-popup-dialog-loading').css('width', body_width + 'px');
        $('#velsof-popup-dialog-loading').css('height', body_height + 'px');
        $.ajax({
            url: "index.php?route=supercheckout/stepcheckout",
            success: function (response) {

                if (response != "Success") {
                    $("#velsof-popup-dialog").html(response);
                    $("#velsof-popup-dialog").dialog({
                        height: 'auto',
                        width: '500',
                        modal: true,
                        title: "Your Cart",
                        position: [850,0],
                        resizable: false,
                        show: {
                            effect: "slide",
                            duration: 1500
                          },
                        hide: {
                            effect: "fade",
                            duration: 1000
                          },
                        open: function (event, ui) {
                            $(".ui-widget-overlay").bind("click", function () {
                                $('#velsof-popup-dialog').dialog("close");
                                $("#velsof-popup-dialog-loading").hide();
                            });
                        },
                        draggable: false,
                        zIndex: 9999,                        
                    });
//                    rename_dialog_title();
                }
                $("#velsof-popup-dialog-loading").hide();
            }
        });
    }
}
