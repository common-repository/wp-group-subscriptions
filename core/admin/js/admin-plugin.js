
/*
 * Jquery functions for front-end forms
 *
 */

!function( exports, $, undefined) {

    var H4AAdminPlugin = function() {
        H4AAdminPlugin.titleHint = function() {
            var $title = $( '#title' );
            var $titleprompt = $( '#title-prompt-text' );
            if ( '' === $title.val() ) {
                $titleprompt.removeClass( 'screen-reader-text' );
            }

            $titleprompt.click( function() {
                $( this ).addClass( 'screen-reader-text' );
                $title.focus();
            } );

            $title.blur( function() {
                if ( '' === $(this).val() ) {
                    $titleprompt.removeClass( 'screen-reader-text' );
                }
            } ).focus( function() {
                $titleprompt.addClass( 'screen-reader-text' );
            } ).keydown( function( e ) {
                $titleprompt.addClass( 'screen-reader-text' );
                $( this ).unbind( e );
            } );
        };

        H4AAdminPlugin.getUrlParameter = function ( param, url ) {
            if (!url) url = location.href;
            param = param.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
            var regexS = "[\\?&]"+ param +"=([^&#]*)";
            var regex = new RegExp( regexS );
            var results = regex.exec( url );
            return results == null ? null : results[1];
        };
        
        return H4AAdminPlugin;
    };

    exports.H4AAdminPlugin = H4AAdminPlugin;

}(this, jQuery);



 

