
/*
 * Jquery functions for front-end forms
 *
 */

!function( exports, $ ) {
    
    var WGSFrontEndEmailsPlugin = function() {
       
        WGSFrontEndEmailsPlugin.insertEmailsHTML = function ( previous_emails_count, emails_count, wrapperTargetId ){
            if( previous_emails_count < emails_count ){
                for( var c = previous_emails_count; c < emails_count; c++ ){

                    var nbr = parseInt(c) + 1;
                    var emailName = "wgs_f_email" + nbr;
                    var repeatEmailName = "formRepeatEmail" + nbr;

                    var label = $( document.createElement('label') );
                    label.addClass("h4a-form-control-label");
                    label.attr("for", emailName );
                    label.html( wgsEmailTranslation.msg_email + ' ' + nbr + "<sup class=\"h4a-star-required\">*</sup>");

                    var emailInput = $( document.createElement('input') );
                    emailInput.addClass("h4a-form-control ");
                    emailInput.attr( "id", emailName );
                    emailInput.attr( "name", emailName );
                    emailInput.attr( "type", "email" );
                    emailInput.attr( "required", true );
                    emailInput.attr( "placeholder", wgsEmailTranslation.msg_email_placeholder );

                    var repeatEmailInput = $( document.createElement('input') );
                    repeatEmailInput.addClass("h4a-form-control ");
                    repeatEmailInput.attr( "id", repeatEmailName );
                    repeatEmailInput.attr( "name", repeatEmailName );
                    repeatEmailInput.attr( "type", "email" );
                    repeatEmailInput.attr( "required", true );
                    repeatEmailInput.attr( "placeholder", wgsEmailTranslation.msg_repeat_email_placeholder );


                    var emailWrapper = $( document.createElement('div') );
                    emailWrapper.addClass("h4a-form-group h4a-col-6");

                    var emailRepeatWrapper = $( document.createElement('div') );
                    emailRepeatWrapper.addClass("h4a-form-group h4a-col-6");

                    var wrapper = $( document.createElement('div') );
                    wrapper.addClass("h4a-form-group h4a-form-inline h4a-col-12");

                    emailWrapper.append( label );
                    emailWrapper.append( emailInput );
                    emailRepeatWrapper.append( repeatEmailInput );
                    wrapper.append( emailWrapper );
                    wrapper.append( emailRepeatWrapper );
                    $( "#" + wrapperTargetId ).append( wrapper );
                }
            }else{
                for( var d = previous_emails_count; d > emails_count; d-- ){
                    $("#" + wrapperTargetId + " > div").last().remove();
                }
            }

        };
        
        WGSFrontEndEmailsPlugin.disableRemoveButton = function ( emails_count, min_members, buttonId ){
            if( emails_count === min_members ){
                $("#" + buttonId ).attr( "disabled", true );
            }else{
                $("#" + buttonId ).attr( "disabled", false );
            }
        };
        
        WGSFrontEndEmailsPlugin.checkIsEmailExists = function ( e, emails_count ){
            var currentInput = document.getElementById( 'wgs_f_email' + e );
            var f = e + 1;
            while( f <= emails_count ){
                var inputToCompare = document.getElementById( 'wgs_f_email' + f );
                if( inputToCompare.value !== '' ){
                    if( currentInput.value === inputToCompare.value ){
                        inputToCompare.setCustomValidity( wgsEmailTranslation.msg_must_email_unique );
                    }else{
                        inputToCompare.setCustomValidity( '' );
                    }
                }
                f++;
            }
        };
        
        WGSFrontEndEmailsPlugin.checkEmailIsUnique = function ( e ){
            var currentInput = document.getElementById( 'wgs_f_email' + e );
            var f = 1;
            while( f < e ){
                var inputToCompare = document.getElementById( 'wgs_f_email' + f );
                
                if( inputToCompare.value !== '' ){
                    if( currentInput.value === inputToCompare.value ){
                        currentInput.setCustomValidity( wgsEmailTranslation.msg_must_email_unique );
                    }else{
                        currentInput.setCustomValidity( '' );
                    }
                }
                f++;
            }
        };


        return WGSFrontEndEmailsPlugin;
    };

    exports.WGSFrontEndEmailsPlugin = WGSFrontEndEmailsPlugin;
    
}(this, jQuery);



 

