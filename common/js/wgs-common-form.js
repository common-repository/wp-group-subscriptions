
/*
 * JavaScript for Forms
 *
 */
jQuery( function($) {

    $(document).ready( function() {
  
        var h4aCommon = new H4ACommonPlugin;
        
        h4aCommon.checkScriptLoaded( 'common-form-plugin', 'js' );
        
        var h4aCommonForm = new H4ACommonFormPlugin;

        var $wgsPasswordInput = $( "#wgs_f_password" );
        var $wgsPasswordRepeatInput = $( "#wgs_f_password_r" );
        if ( $wgsPasswordInput.length && $wgsPasswordRepeatInput.length) {
            if( !isStrNullUndefined( document.getElementById('wgs_f_password') ) ){
                h4aCommonForm.inputsMatch( 'passwords', 'wgs_f_password', 'wgs_f_password_r' );
            }
            $wgsPasswordInput.focusout(function() {
                h4aCommonForm.inputsMatch( 'passwords', 'wgs_f_password', 'wgs_f_password_r' );
            });
            $wgsPasswordRepeatInput.focusout(function() {
                h4aCommonForm.inputsMatch( 'passwords', 'wgs_f_password', 'wgs_f_password_r' );
            });
        }

        var $wgsEmailInput = $( "#wgs_f_email" );
        var $wgsEmailRepeatInput = $( "#wgs_f_email_r" );
        if ( $wgsEmailInput.length && $wgsEmailRepeatInput.length) { 
            if( !isStrNullUndefined( document.getElementById('wgs_f_email') ) ){
                h4aCommonForm.inputsMatch( 'emails', 'wgs_f_email', 'wgs_f_email_r' );
            }
            $wgsEmailInput.focusout(function() {
                h4aCommonForm.inputsMatch( 'emails', 'wgs_f_email', 'wgs_f_email_r' ); 
            });
            $wgsEmailRepeatInput.focusout(function() {
                h4aCommonForm.inputsMatch( 'emails', 'wgs_f_email', 'wgs_f_email_r' );
            });
        }

        var $h4aForm = $('.h4a-form');
        $h4aForm.on( "change", "select#wgs_f_phone_code_sel", function(){
            var $newVal = "";
            if( ! isStrNullUndefinedEmpty( $( this ).val() ) ){
                $newVal = "+" + $( this ).val();
            }
            $('#wgs_f_phone_code').val( $newVal );
        });

        var url = h4aCommon.getCurrentUrl();
        var a_url = url.split( '/' );
        var selectorSubmitBtn, selectorForm;
        if( a_url[3] === 'wp-admin'){
            selectorSubmitBtn = "#publish";
            selectorForm = $('.wrap').find( 'form' ).attr('id');
        }else{
            selectorSubmitBtn = "#btn_submit";
            selectorForm = $h4aForm.attr('id');
        }
        
        $( selectorSubmitBtn ).click(function() {
            if ( $( "#wgs_f_password" ).length && $( "#wgs_f_password_r" ).length) {
                if( !isStrNullUndefined( document.getElementById('wgs_f_password') ) ){
                    h4aCommonForm.checkMatch('wgs_f_password', 'wgs_f_password_r');
                }
            }
            if ( $( "#wgs_f_email" ).length && $( "#wgs_f_email_r" ).length) {
                h4aCommonForm.checkMatch('wgs_f_email', 'wgs_f_email_r');
            }
            $( selectorForm ).checkValidity();
        });
        
        
    });

});



 

