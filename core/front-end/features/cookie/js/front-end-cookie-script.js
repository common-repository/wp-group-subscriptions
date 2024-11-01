jQuery(function($) {
    $(document).ready( function() {
        var h4aCommon = new H4ACommonPlugin;

        h4aCommon.checkScriptLoaded( 'external-cookie-plugin', 'js' );
        var cookie_name = 'h4a_key';
        if( $.cookie( cookie_name ) === undefined ){
            var value = h4aCommon.generateUniqueId();
            $.cookie( cookie_name, value, { path: '/' });
        }

    });
});