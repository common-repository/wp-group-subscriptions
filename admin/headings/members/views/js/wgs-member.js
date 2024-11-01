/*
 * JavaScript only for Subscriber edition page
 *
 */
/* global ajaxurl, pwsL10n, wgsFormTranslation */
jQuery( function($) {

    var updateLock = false,

        $pass1Row,
        $pass1Wrap,
        $pass1,
        $pass1Text,
        $pass2,
        $weakRow,
        $weakCheckbox,
        $toggleButton,
        $submitButtons,
        $submitButton,
        currentPass,
        inputEvent,
        member_id;

    /*
     * Use feature detection to determine whether password inputs should use
     * the `keyup` or `input` event. Input is preferred but lacks support
     * in legacy browsers.
     */
    if ( 'oninput' in document.createElement( 'input' ) ) {
        inputEvent = 'input';
    } else {
        inputEvent = 'keyup';
    }

    function generatePassword() {
        if ( typeof zxcvbn !== 'function' ) {
            setTimeout( generatePassword, 50 );
            return;
        } else if ( ! $pass1.val() ) {
            if( member_id === null ){
                wp.ajax.post('generate-password')
                    .done(function (data) {
                        $pass1.data('pw', data);
                    });
            }
            // zxcvbn loaded before user entered password.
            $pass1.val( $pass1.data( 'pw' ) );
            $pass1.trigger( 'pwupdate' );
            showOrHideWeakPasswordCheckbox();
        }else {
            // zxcvbn loaded after the user entered password, check strength.
            check_pass_strength();
            showOrHideWeakPasswordCheckbox();
        }
        if ( 1 !== parseInt( $toggleButton.data( 'start-masked' ), 10 ) ) {
            $pass1Wrap.addClass( 'show-password' );
        } else {
            $toggleButton.trigger( 'click' );
        }

        // Once zxcvbn loads, passwords strength is known.
        $( '#pw-weak-text-label' ).html( wgsFormTranslation.warnWeak );
    }

    function bindPass1() {
        var currentPass = $pass1.val();

        $pass1Wrap = $pass1.parent();

        $pass1Text = $( '<input type="text"/>' )
            .attr( {
                'id':           'pass1-text',
                'name':         'pass1-text',
                'autocomplete': 'off'
            } )
            .addClass( $pass1[0].className )
            .data( 'pw', $pass1.data( 'pw' ) )
            .val( $pass1.val() )
            .on( inputEvent, function () {
                if ( $pass1Text.val() === currentPass ) {
                    return;
                }
                $pass1.val( $pass1Text.val() ).trigger( 'pwupdate' );
                currentPass = $pass1Text.val();
            } );

        $pass1.after( $pass1Text );

        if ( 1 === parseInt( $pass1.data( 'reveal' ), 10 ) ) {
            generatePassword();
        }

        $pass1.on( inputEvent + ' pwupdate', function () {
            if ( member_id !== null && $pass1.val() === currentPass ) {
                return;
            }

            currentPass = $pass1.val();
            if ( $pass1Text.val() !== currentPass ) {
                $pass1Text.val( currentPass );
            }
            $pass1.add( $pass1Text ).removeClass( 'short bad good strong' );
            showOrHideWeakPasswordCheckbox();
        } );
    }

    function check_pass_strength() {
        var pass1 = $('#wgs_f_password').val(), strength;

        var $passStrengthResult = $('#pass-strength-result');
        $passStrengthResult.removeClass('short bad good strong');
        if ( ! pass1 ) {
            $passStrengthResult.html( '&nbsp;' );
            return;
        }

        strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass1 );

        switch ( strength ) {
            case -1:
                $passStrengthResult.addClass( 'bad' ).html( pwsL10n.unknown );
                break;
            case 2:
                $passStrengthResult.addClass('bad').html( pwsL10n.bad );
                break;
            case 3:
                $passStrengthResult.addClass('good').html( pwsL10n.good );
                break;
            case 4:
                $passStrengthResult.addClass('strong').html( pwsL10n.strong );
                break;
            case 5:
                $passStrengthResult.addClass('short').html( pwsL10n.mismatch );
                break;
            default:
                $passStrengthResult.addClass('short').html( pwsL10n['short'] );
        }
    }

    function showOrHideWeakPasswordCheckbox() {
        var passStrength = $("#pass-strength-result")[0];
        if ( passStrength.className ) {
            $pass1.add( $pass1Text ).addClass( passStrength.className );
            /*if ( $( passStrength ).is( '.short, .bad' ) ) {
                if ( ! $weakCheckbox.prop( 'checked' ) ) {
                    $submitButtons.prop( 'disabled', true );
                }
                $weakRow.show();
            } else {
                $submitButtons.prop( 'disabled', false );
                $weakRow.hide();
            }*/
        }
    }

    $(document).ready( function() {
        var h4aCommon = new H4ACommonPlugin;
        var h4aAdmin = new H4AAdminPlugin;
        member_id = ( h4aAdmin.getUrlParameter( "mbr" ) !== null ) ? h4aAdmin.getUrlParameter( "mbr" ) : null ;

        var dataPwd = {
            'action': "getNewPasswordByAjax"
        };
        $.post( ajaxurl, dataPwd, function( pwd_generated ) {
            //Password Input
            var pwd_inp_attrs = [
                { 'type' : "password" },
                { 'id' : "wgs_f_password" }, //pass1
                { 'name' : "wgs_f_password" }, //pass1
                { 'classes' : [ "regular-text" ] },
                { 'value' : "" },
                { 'autocomplete' : "off" },
                { 'data-pw' : pwd_generated },
                { 'aria-describedby' : "pass-strength-result" }
            ];
            var $pwdInput = h4aCommon.makeHTMLItem( "input", pwd_inp_attrs );
            $pwdInput.attr( "required", "required" );
            var span_pwd_inp_attrs = [
                { 'classes' : [ "password-input-wrapper" ] }
            ];
            var $spanPwdInput = h4aCommon.makeHTMLItem( "span", span_pwd_inp_attrs );
            $spanPwdInput.append( $pwdInput );
            //Button Hide
            var span_hide_icon_attrs = [
                { 'classes' : [ "dashicons", "dashicons-hidden" ] }
            ];
            var $btnHideIcon = h4aCommon.makeHTMLItem( "span", span_hide_icon_attrs );
            var span_hide_label_attrs = [
                { 'text' : wgsFormTranslation.button_hide_text },
                { 'classes' : [ "text" ] }
            ];
            var $btnHideLabel = h4aCommon.makeHTMLItem( "span", span_hide_label_attrs );
            var btn_hide_attrs = [
                { 'type' : "button" },
                { 'classes' : [ "button", "wp-hide-pw", "hide-if-no-js" ] },
                { 'data-toggle' : 0 },
                { 'aria-label' : wgsFormTranslation.button_hide_label }
            ];
            var $btnHide = h4aCommon.makeHTMLItem( "button", btn_hide_attrs );
            $btnHide.append( $btnHideIcon );
            $btnHide.append( $btnHideLabel );

            if( member_id !== null ){
                //Button Cancel
                var span_cancel_label_attrs = [
                    { 'text' : wgsFormTranslation.button_cancel_text },
                    { 'classes' : [ "text" ] }
                ];
                var $btnCancelLabel = h4aCommon.makeHTMLItem( "span", span_cancel_label_attrs );
                var btn_cancel_attrs = [
                    { 'type' : "button" },
                    { 'classes' : [ "button", "wp-cancel-pw", "hide-if-no-js" ] },
                    { 'data-toggle' : 0 },
                    { 'aria-label' : wgsFormTranslation.button_cancel_label }
                ];
                var $btnCancel = h4aCommon.makeHTMLItem( "button", btn_cancel_attrs );
                $btnCancel.append( $btnCancelLabel );
            }
            //Pass Strenght Result
            var pass_strenght_result_attrs = [
                { 'style' : "..." },
                { 'id' : "pass-strength-result" },
                { 'aria-live' : "polite" }
            ];
            var $passStrengthResult = h4aCommon.makeHTMLItem( "div", pass_strenght_result_attrs );
            //Block wrapper
            var block_attrs = [
                { 'classes' : [ "wp-pwd", "hide-if-js" ] }
            ];
            var $block_pwd = h4aCommon.makeHTMLItem( "div", block_attrs );
            $block_pwd.append( $spanPwdInput );
            $block_pwd.append( $btnHide );
            $block_pwd.append( $btnCancel );
            $block_pwd.append( $passStrengthResult );
            $("#btn_gen_password").after( $block_pwd );
            $('#wgs_f_password').val('').on( inputEvent + ' pwupdate', check_pass_strength );
            $('#pass-strength-result').show();
            bindPasswordForm();
        });
    });

    function bindPasswordForm() {
        var $passwordWrapper,
            $generateButton,
            $cancelButton;

        $pass1Row = $('#btn_gen_password').parent().parent();
        $pass1 = $('#wgs_f_password');
        if ( $pass1.length ) {
            bindPass1();
        }

        // Disable hidden inputs to prevent autofill and submission.
        if ( $pass1.is( ':hidden' ) ) {
            $pass1.attr( "disabled", "1" );
            $pass1Text.attr( "disabled", "1" );
        }

        $passwordWrapper = $pass1Row.find( '.wp-pwd' );
        $generateButton  = $pass1Row.find( 'button.wp-generate-pw' );

        bindToggleButton();

        if ( $generateButton.length ) {
            $passwordWrapper.hide();
        }

        $generateButton.show();
        $generateButton.on( 'click', function () {
            updateLock = true;

            if( member_id !== null ){
                $generateButton.hide();
            }else{
                $pass1.val( "" );
                $pass1Text.val( "" );
            }
            $passwordWrapper.show();

            // Enable the inputs when showing.
            $pass1.attr( 'disabled', null );
            $pass1Text.attr( 'disabled', null );
            $pass1Text.attr( 'required', "required" );

            if ( $pass1Text.val().length === 0 ) {
                generatePassword();
            }

            /*_.defer( function() {
                $pass1Text.focus();
                if ( ! _.isUndefined( $pass1Text[0].setSelectionRange ) ) {
                    $pass1Text[0].setSelectionRange( 0, 100 );
                }
            }, 0 );*/
        } );

        if( member_id !== null ) {
            $cancelButton = $pass1Row.find('button.wp-cancel-pw');
            $cancelButton.on('click', function () {
                updateLock = false;

                // Clear any entered password.
                $pass1Text.val('');

                // Generate a new password.
                wp.ajax.post('generate-password')
                    .done(function (data) {
                        $pass1.data('pw', data);
                    });

                $generateButton.show();
                $passwordWrapper.hide();

                // Disable the inputs when hiding to prevent autofill and submission.
                $pass1.prop('disabled', true);
                $pass1Text.prop('disabled', true);

                resetToggle();

                // Clear password field to prevent update
                $pass1.val('').trigger('pwupdate');

            });

        }else{
            $generateButton.trigger('click');
        }

        $pass1Row.closest( 'form' ).on( 'submit', function () {
            updateLock = false;
            //$pass1.prop( 'disabled', false );
            //$pass1Wrap.removeClass( 'show-password' );
        });


    }

    function resetToggle() {
        $toggleButton
            .data( 'toggle', 0 )
            .attr({
                'aria-label': wgsFormTranslation.ariaHide
            })
            .find( '.text' )
            .text( wgsFormTranslation.hide )
            .end()
            .find( '.dashicons' )
            .removeClass( 'dashicons-visibility' )
            .addClass( 'dashicons-hidden' );

        $pass1Text.focus();

    }

    function bindToggleButton() {
        $toggleButton = $pass1Row.find('.wp-hide-pw');
        $toggleButton.show().on( 'click', function () {
            if ( 1 === parseInt( $toggleButton.data( 'toggle' ), 10 ) ) {
                $pass1Wrap.addClass( 'show-password' );

                resetToggle();

                /*if ( ! _.isUndefined( $pass1Text[0].setSelectionRange ) ) {
                    $pass1Text[0].setSelectionRange( 0, 100 );
                }*/
            } else {
                $pass1Wrap.removeClass( 'show-password' );
                $toggleButton
                    .data( 'toggle', 1 )
                    .attr({
                        'aria-label': wgsFormTranslation.ariaShow
                    })
                    .find( '.text' )
                    .text( wgsFormTranslation.show )
                    .end()
                    .find( '.dashicons' )
                    .removeClass('dashicons-hidden')
                    .addClass('dashicons-visibility');

                $pass1.focus();

                /*if ( ! _.isUndefined( $pass1[0].setSelectionRange ) ) {
                    $pass1[0].setSelectionRange( 0, 100 );
                }*/
            }
        });
    }
});