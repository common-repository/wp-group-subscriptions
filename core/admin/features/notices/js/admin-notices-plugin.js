
/*
 * Jquery functions for front-end forms
 *
 */

!function( exports ) {

    var H4AAdminNoticesPlugin = function() {

        var h4aCommon = new H4ACommonPlugin;
        

        H4AAdminNoticesPlugin.makeNotice = function ( text, type ) {
            var $message = h4aCommon.makeHTMLItem( 'p', [ { 'text' : text } ] );
            if( isStrNullUndefinedEmpty( type ) )
                type = 'error';
            var $error = h4aCommon.makeHTMLItem( 'div', [ { 'classes' : [ 'notice', 'notice-' + type, 'notice-' + type + '-form' ] }]);
            $error.append( $message );
            return $error;
        };

        return H4AAdminNoticesPlugin;
    };

    exports.H4AAdminNoticesPlugin = H4AAdminNoticesPlugin;

}(this, jQuery);



 

