
/*
 * JavaScript for Settings Submenu Page
 *
 */
jQuery( function($) {

    $(document).ready( function() {

        var h4aCommon = new H4ACommonPlugin;

        h4aCommon.checkScriptLoaded( 'common-form-plugin', 'js' );

        var h4aCommonForm = new H4ACommonFormPlugin;

        var wgsFrontEndEmails = new WGSFrontEndEmailsPlugin();

        var previous_emails_count = 2;

        var emails_count = 0;

        var min_members = 0;

        var $wgsInpIncludeAsMember = $("#wgs_f_as_member");
        if( $wgsInpIncludeAsMember.length ){
            $wgsInpIncludeAsMember.on('click', function () {
                var $firstEmail1 = $("#wgs_f_email1");
                var $firstEmailRepeat = $("#wgs_f_email_r1");
                if( $(this).is(':checked') ) {
                    var data = {
                        'action': "getEmailSubscriberByAjax"
                    };
                    $.post( wgs_ajax_object.ajax_url, data, function(response) {
                        var jsonObj = JSON.parse( response );
                        $firstEmail1.attr( "value", jsonObj.wgs_current_subscriber.email.toLowerCase() );
                        $firstEmail1.attr( "disabled", true );
                        $firstEmailRepeat.attr( "value", jsonObj.wgs_current_subscriber.email.toLowerCase() );
                        $firstEmailRepeat.attr( "disabled", true );
                    });
                }else{
                    $firstEmail1.attr( "value", "" );
                    $firstEmail1.attr( "disabled", false );
                    $firstEmailRepeat.attr( "value", "" );
                    $firstEmailRepeat.attr( "disabled", false );
                }
            });
        }

        var $wgsNumUserAccounts = $("#wgs_num_user_accounts");
        if( $wgsNumUserAccounts.length ){
            emails_count = $wgsNumUserAccounts.val();
        }else{
            emails_count = $("#wgs-wrapper-emails > div").length;
            min_members = emails_count;
        }

        if( $wgsNumUserAccounts.length ){
            $wgsNumUserAccounts.on('click', function () {
                // Store the current value on focus and on change
                previous_emails_count = this.value;
            }).change(function() {
                emails_count = this.value;
                wgsFrontEndEmails.insertEmailsHTML( previous_emails_count, emails_count, "wgs-wrapper-emails" );
            });
        }

        var $wgsAddUserAccount = $("#wgs_add_user_account");
        if( $wgsAddUserAccount.length ){

            $wgsAddUserAccount.on('click', function () {
                previous_emails_count = emails_count;
                emails_count++;
                wgsFrontEndEmails.insertEmailsHTML( previous_emails_count, emails_count, "wgs-wrapper-emails" );
                wgsFrontEndEmails.disableRemoveButton( emails_count, min_members, "wgs_remove_user_account");
            });

        }

        var $wgsRemoveUserAccount = $("#wgs_remove_user_account");
        if( $wgsRemoveUserAccount.length ){
            $wgsRemoveUserAccount.on('click', function () {
                previous_emails_count = emails_count;
                emails_count--;
                wgsFrontEndEmails.insertEmailsHTML( previous_emails_count, emails_count, "wgs-wrapper-emails" );
                wgsFrontEndEmails.disableRemoveButton( emails_count, min_members, "wgs_remove_user_account");
            });
        }

        $( "input[type=email].wgs-email" ).each(function( index ) {
            $(this).focusout(function() {
                var pos = index + 1;
                if( $(this).val() !== '' ){
                    wgsFrontEndEmails.checkEmailIsUnique( pos );
                }
            });
        });

        $( "input[type=email].wgs-email-repeat" ).each(function( index ) {
            $(this).focusout(function() {
                var pos = index + 1;
                if( pos < emails_count && $(this).val() !== '' ){
                    h4aCommonForm.checkMatch( 'wgs_f_email' + pos , 'wgs_f_email_r' + pos );
                }
            });
        });

        $( "#btn_submit" ).click(function() {
            for( var e = 1; e <= emails_count; e++){
                h4aCommonForm.checkMatch( 'wgs_f_email' + e , 'wgs_f_email_r' + e );
                if( e < emails_count){
                    wgsFrontEndEmails.checkIsEmailExists( e, emails_count );
                }
            }

        });

        $("#wgs-form-multiple-members").submit(function() {
            $("*:disabled").prop("disabled", false);
        });

    });

});



 

