
/*
 * Jquery functions for front-end forms
 *
 */

!function( exports, $, undefined) {

    var H4ACommonLoaderPlugin = function() {
        var h4aCommon = new H4ACommonPlugin;
        
        H4ACommonLoaderPlugin.makeSpinner = function( tag = "div" ){
            var attrs = [ { 'classes' : [ 'h4a-loader' ] } ];
            return h4aCommon.makeHTMLItem( tag, attrs );
        };
        
        return H4ACommonLoaderPlugin;
    };

    exports.H4ACommonLoaderPlugin = H4ACommonLoaderPlugin;

}(this, jQuery);



 

