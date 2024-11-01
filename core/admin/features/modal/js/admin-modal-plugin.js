/*
 * Jquery functions for front-end forms
 *
 */

!function( exports, $, undefined) {

    var H4AAdminModalPlugin = function() {

        var h4aCommon = new H4ACommonPlugin;
        var h4aCommonLoader = new H4ACommonLoaderPlugin;


        H4AAdminModalPlugin.build = function ( selector, args ){

            var attrs = [ { 'classes' : [ "h4a-modal-icon", "h4a-modal-close-icon" ] } ];
            var iconCloseButton = h4aCommon.makeHTMLItem( 'span', attrs);

            attrs = [ { 'type' : 'button' }, { 'classes' : [ "h4a-modal-button", "h4a-modal-close-button" ] } ];
            var closeButton = h4aCommon.makeHTMLItem( 'button', attrs);
            closeButton.append( iconCloseButton );
            closeButton.on( 'click', function (){
                H4AAdminModalPlugin.close();
            });

            var modalTitle = h4aCommon.makeHTMLItem( 'h1', [ { 'text' : args.title } ]);

            var modalHeader = h4aCommon.makeHTMLItem( 'header', [ { 'classes' : [ "h4a-modal-header" ] } ]);
            modalHeader.append( modalTitle );

            attrs = [ { 'classes' : [ "h4a-modal-content-left" ] } ];
            if( !isStrNullUndefinedEmpty( args.padding ) ){
                attrs.push( { 'style' : ' padding : ' + args.padding + ';' } );
            }
            var modalFrameContentLeft = h4aCommon.makeHTMLItem( 'div', attrs);

            if( args.content !== undefined ){
                modalFrameContentLeft.append( args.content );
            }
            var modalFrameContent;
            if( !isStrNullUndefinedEmpty( args.save ) ){
                const is_ajax = isStrNullUndefinedEmpty( args.save.ajax ) || args.save.ajax;
                attrs = [ { 'type' : ( is_ajax ) ? 'button' : 'submit' }, { 'classes' : [ 'button', 'button-primary', 'button-large' ] }, { 'value' : args.save.label_save } ];
                var saveButton = h4aCommon.makeHTMLItem( 'input', attrs);
                if( is_ajax ){
                    saveButton.on( 'click', function(e) {
                        e.preventDefault();
                        $(this).attr("disabled", "disabled");
                        var $h4aModalForm = $('#h4a-modal-form');
                        if( $h4aModalForm[0].checkValidity() ) {
                            var $spinner = h4aCommonLoader.makeSpinner();
                            $(this).after( $spinner );
                            var data = {
                                'action': args.save.action,
                                'data': $h4aModalForm.serialize()
                            };
                            $.post( ajaxurl, data, function( response ) {
                                args.save['callback']( JSON.parse( response ) );
                                $spinner.remove();
                            });
                        }else{
                            $h4aModalForm[0].reportValidity();
                        }
                        $(this).attr("disabled", false);
                    });
                }else{
                    args.url = h4aCommon.getCurrentUrl() + "&noheader=true";
                }
                var modalFrameContentRight = h4aCommon.makeHTMLItem( 'div', [ { 'classes' : [ 'h4a-modal-content-right' ] } ]);
                modalFrameContentRight.append( saveButton );

                if( !isStrNullUndefinedEmpty( args.save.message ) ){
                    var message = h4aCommon.makeHTMLItem( 'p', [ { 'text' : args.save.message } ] );
                    var messageWrapper = h4aCommon.makeHTMLItem( 'div', [ { 'classes' : [ 'notice', 'notice-warning' ] } ] );
                    messageWrapper.append( message );
                    modalFrameContentRight.append( messageWrapper );
                }
                console.dir( args );
                attrs = [
                    { 'id' : 'h4a-modal-form' },
                    { 'method' : 'post' },
                    { 'action' : args.url },
                    { 'classes' : [ 'h4a-modal-frame-content' ] }
                ];

                if ( !isStrNullUndefinedEmpty( args.enctype ) )
                    attrs.push( { 'enctype' : args.enctype } );
                modalFrameContent = h4aCommon.makeHTMLItem( 'form', attrs);
                modalFrameContent.append( modalFrameContentLeft );
                modalFrameContent.append( modalFrameContentRight );

            }else{
                modalFrameContent = h4aCommon.makeHTMLItem( 'section', [ { 'id' : 'h4a-modal-form' }, { 'classes' : [ 'h4a-modal-frame-content' ] } ]);
                modalFrameContent.append( modalFrameContentLeft );
            }

            var modalContent = h4aCommon.makeHTMLItem( 'div', [ { 'classes' : [ 'h4a-modal-content' ] } ]);
            modalContent.append( modalHeader );
            modalContent.append( modalFrameContent );

            var modal = h4aCommon.makeHTMLItem( 'div', [ { 'classes' : [ 'h4a-modal' ] } ]);
            modal.append( closeButton );
            modal.append( modalContent );
            if( args.margin !== undefined ){
                modal.css(
                    { 'top' : args.margin,
                        'bottom' : args.margin,
                        'left' : args.margin,
                        'right' : args.margin }
                );
            }


            var backDrop = h4aCommon.makeHTMLItem( 'div', [ { 'classes' : [ 'h4a-modal-backdrop' ] } ]);

            var container = h4aCommon.makeHTMLItem( 'div', [ { 'id' : 'h4a-modal-container' } ]);
            container.append( modal );
            container.append( backDrop );
            $( selector ).append( container );
            var $h4aModalContentLeft = $(".h4a-modal-content-left");
            $h4aModalContentLeft.css( "height", $h4aModalContentLeft.height() );

        };

        H4AAdminModalPlugin.close = function (){
            $( '#h4a-modal-container' ).remove();
        };

        return H4AAdminModalPlugin;
    };

    exports.H4AAdminModalPlugin = H4AAdminModalPlugin;

}(this, jQuery);