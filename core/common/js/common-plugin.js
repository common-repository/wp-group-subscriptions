!function( exports, $, undefined) {

    var H4ACommonPlugin = function() {

        H4ACommonPlugin.inArray = function( needle, haystack ) {
            var length = haystack.length;
            for(var i = 0; i < length; i++) {
                if( haystack[i] === needle ) return true;
            }
            return false;
        };

        H4ACommonPlugin.getCurrentUrl = function(){
            return window.location.href;
        };

        H4ACommonPlugin.getBaseUrl = function(){
            var getUrl = window.location;
            return getUrl .protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
        };

        H4ACommonPlugin.checkScriptLoaded = function ( scriptName, ext ){
            var len = $('script[src*="' + scriptName + '.' + ext + '"]').length;

            if (len === 0) {
                alert('script ' + scriptName + '.' + ext + ' not loaded');
            }
        };

        H4ACommonPlugin.checkScriptLoaded( 'helpers', 'js' );

        H4ACommonPlugin.generateUniqueId = function() {
            return "_" + new Date().valueOf() + Math.random().toFixed(16).substring(2);
        };
        
        H4ACommonPlugin.makeHTMLItem = function ( tagName, attrs ){
            var $htmlItem = $('<' + tagName + '>');
            if( !isArrayNullUndefinedEmpty( attrs ) ){
                $.each(attrs, function( key, value ){
                    $.each( value, function( key, value){
                        if( key === 'classes' ){
                            for( var c = 0; c < value.length; c++ ){
                                $htmlItem.addClass( value[c] );
                            }
                        }else if( key === 'text' ){
                            $htmlItem.text( value );
                        }else if( key === 'html' ){
                            $htmlItem.html( value );
                        }else if( key === 'options' ){
                            if( tagName === 'select' ){
                                for( var o = 0; o < value.length; o++ ){
                                    var attrs_opt = value[o];
                                    var option = H4ACommonPlugin.makeHTMLItem( 'option', attrs_opt );
                                    $htmlItem.append( option );
                                } 
                            }
                        }else{
                            $htmlItem.attr( key, value );
                        }
                    });
                });
            }
            return $htmlItem;
        };
        
        return H4ACommonPlugin;
    };

    exports.H4ACommonPlugin = H4ACommonPlugin;

}(this, jQuery);



 

