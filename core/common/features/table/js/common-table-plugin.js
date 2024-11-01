/*
 * Jquery functions for front-end forms
 *
 */

!function( exports, $, undefined) {

    var H4ATablePlugin = function() {

        var h4aCommon = new H4ACommonPlugin;

        /*H4ATablePlugin.generateTD = function ( attrs, inputs ){
            var td = h4aCommon.makeHTMLItem( 'td', attrs );
            if( inputs.length > 0 ){
                for( var i = 0; i < inputs.length; i++ ){
                    td.append( inputs[i] );
                }
            }
            return td;
        };*/
        
        H4ATablePlugin.generateTR = function ( attrs, inputs ){
            var tr = h4aCommon.makeHTMLItem( 'tr', attrs );
            if( inputs.length > 0 ){
                for( var i = 0; i < inputs.length; i++ ){
                    var tag = 'td';
                    var attrs_td = [];
                    if( inputs[i].attrs !== undefined && inputs[i].attrs.length > 0 ){
                        $.each( inputs[i].attrs, function( iKey, value ){
                            $.each( value, function( k, v ){
                                if( k === 'wrapper' ){
                                    tag = v;
                                }else{
                                    var attr_td = {};
                                    attr_td[k] =  v;
                                    attrs_td.push( attr_td ); 
                                }
                            });
                        });
                    }
                    var td = h4aCommon.makeHTMLItem( tag, attrs_td );
                    if( inputs[i].item != undefined ){
                        td.append( inputs[i].item );
                    }else if( inputs[i].items != undefined ){
                        for( var inp = 0; inp < inputs[i].items.length; inp++ ){
                            td.append( inputs[i].items[inp] );
                            
                        }
                    }
                    tr.append( td );
                }
            }
            return tr;
        };

        

        return H4ATablePlugin;
    };

    exports.H4ATablePlugin = H4ATablePlugin;

}(this, jQuery);