jQuery(function($) {
    $(document).ready( function() {
        var tz = jstz.determine();
        $.cookie('h4a_timezone', tz.name(), { path: '/' });
    });
});