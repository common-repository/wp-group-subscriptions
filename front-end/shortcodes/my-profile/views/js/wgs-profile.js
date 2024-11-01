jQuery( function($) {
    $(document.body).on( "click", "#wgs_change-password", function( event ) {
        var h4aCommon = new H4ACommonPlugin;
        if( $(this).val() === "change" ){
            $(this).parent().css( { 'flex-direction' : "row", 'justify-content' : "space-between" } ) ;
            var args_inp_pass = [
                { 'type'         : "password" },
                { 'required'     : "required" },
                { 'id'           : "wgs_f_password" },
                { 'name'         : "wgs_f_password" },
                { 'value'        : "" },
                { 'placeholder'  : wgsFormTranslation.password_placeholder },
                { 'autocomplete' : "off" }
            ];
            var inp_password = h4aCommon.makeHTMLItem( "input", args_inp_pass );
            $(this).before( inp_password );
            var args_inp_pass_r = [
                { 'type'         : "password" },
                { 'required'     : "required" },
                { 'id'           : "wgs_f_password_r" },
                { 'name'         : "wgs_f_password_r" },
                { 'value'        : "" },
                { 'placeholder'  : wgsFormTranslation.password_placeholder_r },
                { 'autocomplete' : "off" }
            ];
            var inp_password_r = h4aCommon.makeHTMLItem( "input", args_inp_pass_r );
            $(this).before( inp_password_r );
            $(this).text( wgsFormTranslation.button_cancel );
            $(this).attr( "value", "reset" );
        }else if( $(this).val() === "reset"  ){
            $(this).parent().css( { 'flex-direction' : "column" } ) ;
            $("#wgs_f_password").remove();
            $("#wgs_f_password_r").remove();;
            $(this).text( wgsFormTranslation.button_change_password );
            $(this).attr( "value", "change" );
        }
    });
});