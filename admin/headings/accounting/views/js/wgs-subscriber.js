/*
 * JavaScript only for Subscriber edition page
 *
 */
jQuery( function($) {

    function enableStatusSytem( plan_id ) {
        var $subsStatusSelectedSelect = $("#subscriber-status-select");
        var $btnModifyStatus = $("a.edit-subscriber-status");
        var $lastPaymentDisplay = $("#last-payment-display");
        if( isStrNullUndefinedEmpty( plan_id ) ){
            $btnModifyStatus.hide();
        }else{
            var dataForm = {
                'action': "getPlanPriceByAjax",
                'plan_id' : plan_id
            };
            $.post( ajaxurl, dataForm, function( response ) {
                if( response !== "0" && ( $lastPaymentDisplay.length === 0 || $lastPaymentDisplay.text() === "None" ) ){
                    $btnModifyStatus.hide();
                }else{
                    $btnModifyStatus.show();
                    // Subscriber Status edit click.
                    $btnModifyStatus.on("click", function (event) {
                        if ($subsStatusSelectedSelect.is(":hidden")) {
                            $subsStatusSelectedSelect.slideDown("fast", function () {
                                $subsStatusSelectedSelect.find("select").focus();
                            });
                            $(this).hide();
                        }
                        event.preventDefault();
                    });

                    // Save Subscriber Status changes and hide the options.
                    $subsStatusSelectedSelect.find(".save-subscriber-status").click(function (event) {
                        $subsStatusSelectedSelect.slideUp("fast").siblings("a.edit-subscriber-status").show().focus();
                        $("#subscriber-status-display").html($("option:selected", $("#wgs_f_status")).text());
                        event.preventDefault();
                    });

                    // Cancel Subcriber Status editing and hide the options.
                    $subsStatusSelectedSelect.find(".cancel-subscriber-status").click(function (event) {
                        $subsStatusSelectedSelect.slideUp("fast").siblings("a.edit-subscriber-status").show().focus();
                        var $wgsSubscriberStatusInput = $("#wgs_f_status");
                        $wgsSubscriberStatusInput.val($("#hidden_subscriber_status").val());
                        $("#subscriber-status-display").html($("option:selected", $wgsSubscriberStatusInput).text());
                        event.preventDefault();
                    });
                }
            });
        }
    }

    $(document.body).on( "click", "#wgs_change-password", function( event ) {
        var h4aCommon = new H4ACommonPlugin;
        if( $(this).val() === "change" ){
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
            $("#wgs_f_password").remove();
            $("#wgs_f_password_r").remove();;
            $(this).text( wgsFormTranslation.button_change_password );
            $(this).attr( "value", "change" );
        }
    });

    $(document).ready( function() {

        var h4aCommon = new H4ACommonPlugin;
        var h4aCommonLoader = new H4ACommonLoaderPlugin;
        var h4aAdmin = new H4AAdminPlugin;
        var h4aAdminModal = new H4AAdminModalPlugin;
        var h4aAdminNotices = new H4AAdminNoticesPlugin;
        var $wgsPlanIdInput = $("#wgs_f_plan_id");
        enableStatusSytem( $wgsPlanIdInput.val() );

        var $planSelectedSelect = $("#plan-selected-select");
        var subscriber_id = ( h4aAdmin.getUrlParameter( "subs" ) !== null ) ? h4aAdmin.getUrlParameter( "subs" ) : null ;
        // Plan selected click.
        $planSelectedSelect.siblings("a.edit-plan-selected").click( function(event ) {
            if ( $planSelectedSelect.is( ":hidden" ) ) {
                $planSelectedSelect.slideDown( "fast", function() {
                    $planSelectedSelect.find("select").focus();
                } );
                $(this).hide();
            }
            event.preventDefault();
        });

        // Select the plan and hide the options.
        $planSelectedSelect.find(".save-plan-selected").click( function(event ) {
            $planSelectedSelect.slideUp( "fast" ).siblings( "a.edit-plan-selected" ).show().focus();
            var $wgsPlanIdInput = $("#wgs_f_plan_id");
            loadSubscriberForm( $wgsPlanIdInput.val(), "modify" );
            $("#plan-selected-display").html( $("option:selected", $wgsPlanIdInput).text());
            event.preventDefault();
        });

        // Cancel the Plan selected and hide the options.
        $planSelectedSelect.find(".cancel-plan-selected").click( function(event ) {
            $planSelectedSelect.slideUp( "fast" ).siblings( "a.edit-plan-selected" ).show().focus();
            var $hiddenPlanSelectedInput = $("#hidden_plan_selected");
            loadSubscriberForm( $hiddenPlanSelectedInput.val(), "cancel" );
            var $wgsPlanIdInput = $("#wgs_f_plan_id");
            $wgsPlanIdInput.val( $hiddenPlanSelectedInput.val() );
            $("#plan-selected-display").html( $("option:selected", $wgsPlanIdInput).text());
            event.preventDefault();
        });

        var loadSubscriberForm = function( plan_id, action ){
            var id_option = $("#wgs_f_plan_id option").filter(
                function () {
                    return $(this).html() === $("#plan-selected-display").text();
                }
            ).val();
            if( ( action === "modify" && $("#wgs_f_plan_id").val() !== id_option )
                || ( action === "cancel"  && $("#hidden_plan_selected").val() !== id_option) ){
                var $spinner = h4aCommonLoader.makeSpinner();
                var $spinner2 = h4aCommonLoader.makeSpinner();
                $planSelectedSelect.parent(".wgs-misc-postbox-row").append( $spinner );
                var $template = $("section.h4a-section-wrappers");

                var formData = $("form.h4a-form").serializeArray();
                $template.html($spinner2);
                var dataForm = {
                    'action': "getSubscriberFormContentByAjax",
                    'plan_id' : plan_id
                };
                $.post( ajaxurl, dataForm, function( response ) {
                    var jsonResp = JSON.parse( response );
                    $("form.h4a-form").attr( "id", jsonResp.html_id );
                    $template.html( jsonResp.content );
                    for( var i = 0; i < formData.length; i++ ){
                        if(  formData[i].name.startsWith( "wgs_f_" ) ){
                            $("[name='" + formData[i].name + "']").val( formData[i].value );
                        }
                    }
                    $spinner.remove();
                });
                var $lastPaymentInput = $("#wgs_f_last_payment");
                var last_payment = ( $lastPaymentInput.val() !== undefined ) ? $lastPaymentInput.val() : null ;
                actualizePlanType( plan_id );
                actualizeStatusEdition( plan_id, last_payment );
            }
        };

        var get_subscriber_status = function ( ) {
            var $statusInput = $("#wgs_f_status");
            return ( $statusInput.val() !== undefined ) ? $statusInput.val() : "disabled";
        };

        var actualizeStatusEdition = function( plan_id, last_payment ){
            var subscriber_status = get_subscriber_status();
            var $spinner3 = h4aCommonLoader.makeSpinner();
            var $divStatus = $("div.misc-pub-subscriber-status");
            $divStatus.html($spinner3);
            var dataStatus = {
                'action': "getSubscriberStatusContentByAjax",
                'subscriber_id' : subscriber_id,
                'status' : subscriber_status,
                'last_payment' : last_payment,
                'plan_id' : plan_id
            };
            $.post( ajaxurl, dataStatus, function( response ) {
                $spinner3.remove();
                $divStatus.replaceWith( response );
                enableStatusSytem( plan_id );
            });
        };

        var actualizePlanType = function( plan_id ){
            var $spinner4 = h4aCommonLoader.makeSpinner();
            var $spanPlanType = $("span#plan-type-display");
            $spanPlanType.hide();
            $spanPlanType.after( $spinner4 );
            var dataStatus = {
                'action': "getPlanTypeByAjax",
                'plan_id' : plan_id
            };
            $.post( ajaxurl, dataStatus, function( response ) {
                $spinner4.remove();
                $spanPlanType.text( response );
                if( response === wgsFormTranslation.plan_type_multiple ){
                    var args_label_as_member = [
                        { 'for' : "wgs_f_as_member" },
                        { 'text' : wgsFormTranslation.include_as_member }
                    ];
                    var $labelAsMember = h4aCommon.makeHTMLItem( "label", args_label_as_member );
                    var args_input_as_member = [
                        { 'id'   : "wgs_f_as_member" },
                        { 'name' : "wgs_f_as_member" },
                        { 'type' : "checkbox" }
                    ];
                    var $inputAsMember = h4aCommon.makeHTMLItem( "input", args_input_as_member );
                    var args_div_as_member = [
                        { 'classes' : [ "misc-pub-section", "misc-pub-as-member"] }
                    ];
                    var $divAsMember = h4aCommon.makeHTMLItem( "div", args_div_as_member );
                    $divAsMember.append( $labelAsMember );
                    $divAsMember.append( " : " );
                    $divAsMember.append( $inputAsMember );
                    $("div#planpost > div#misc-publishing-actions > div.clear").before( $divAsMember );
                }else{
                    $("div.misc-pub-as-member").remove();
                }
                $spanPlanType.show();
            });
        };

        if( h4aAdmin.getUrlParameter( "pl" ) !== null ){
            //Modify phone inputs
            var $phoneLabelWrapper = $('[for="wgs_f_phone_number"]').parent();
            $phoneLabelWrapper.removeClass();
            $phoneLabelWrapper.addClass( "h4a-form-group" );
            $phoneLabelWrapper.addClass( "h4a-col-2" );

            var $phoneComboboxWrapper = $("#wgs_f_phone_code_sel").parent();
            $phoneComboboxWrapper.removeClass();
            $phoneComboboxWrapper.addClass( "h4a-form-group" );
            $phoneComboboxWrapper.addClass( "h4a-col-10" );

            var $phoneCodeWrapper = $("#wgs_f_phone_code").parent();
            $phoneCodeWrapper.removeClass();
            $phoneCodeWrapper.addClass( "h4a-form-group" );
            $phoneCodeWrapper.addClass( "h4a-col-2" );

            var $phoneNumberrapper = $("#wgs_f_phone_number").parent();
            $phoneNumberrapper.removeClass();
            $phoneNumberrapper.addClass( "h4a-form-group" );
            $phoneNumberrapper.addClass( "h4a-col-10" );
        }

        if( h4aAdmin.getUrlParameter( "subs" ) !== null ){
            //Insert all data 
            //TODO

            $("button#open_payments_binding").on( "click", function(){
                openPaymentsModal();
            });



            var openPaymentsModal = function(){
                var title = wgsFormTranslation.payments_modal_title;
                var label_save = wgsFormTranslation.save_payments;
                var action = "assignPaymentsByAjax";
                var callback = callBackAssignPayments;
                var attrs = [
                    { 'classes' : [ "modal-main-content" ] }
                ];
                var modalContent = h4aCommon.makeHTMLItem( "section", attrs );

                var $spinner4 = h4aCommonLoader.makeSpinner();
                modalContent.append( $spinner4 );

                var args = {
                    'title' : title,
                    'margin' : "150px",
                    'content' : modalContent,
                    'padding' : "14px",
                    'save' : {
                        'label_save' : label_save,
                        'action' : action,
                        'callback' : callback
                    }
                };
                h4aAdminModal.build( "#wpbody-content", args );

                var dataPayments = {
                    'action': "getPaymentsToAssignByAjax",
                    'subscriber_id' : subscriber_id
                };
                $.post( ajaxurl, dataPayments, function( response ) {
                    var jsonResp = JSON.parse( response );
                    var attrs_unassigned = [
                        { 'id' : "wgs-payments-unassigned-select" },
                        { 'name' : "wgs-payments-unassigned-select[]" },
                        { 'data-text' : wgsFormTranslation.payments_modal_source_title },
                        { 'data-search' : "search for options" }
                    ];
                    var a_unassigned = [];
                    $.each( jsonResp.unassigned, function( index, obj_payment ) {
                        var a_payment = [
                            { 'value' : obj_payment.value },{ 'text' : obj_payment.text }
                        ];
                        a_unassigned.push( a_payment );
                    });

                    attrs_unassigned.push( { 'options' : a_unassigned } );
                    var m_paymentsunassignedCombobox = h4aCommon.makeHTMLItem( "select", attrs_unassigned );
                    var attrs_assigned = [
                        { 'id' : "wgs-payments-assigned-select" },
                        { 'name' : "wgs-payments-assigned-select[]" },
                        { 'data-text' : wgsFormTranslation.payments_modal_destination_title },
                        { 'data-search' : "search for options" }
                    ];
                    var a_assigned = [];
                    $.each( jsonResp.assigned, function( index, obj_payment ) {
                        var a_payment = [
                            { 'value' : obj_payment.value },{ 'text' : obj_payment.text }
                        ];
                        a_assigned.push( a_payment );
                    });
                    attrs_assigned.push( { 'options' : a_assigned } );

                    var m_paymentsassignedCombobox = h4aCommon.makeHTMLItem( "select", attrs_assigned );
                    var attrs_hidden =[
                        { 'type'  : "hidden" },
                        { 'name'  : "subscriber_id" },
                        { 'value' : subscriber_id }
                    ];
                    var m_subscriberIdHidden =h4aCommon.makeHTMLItem( "input", attrs_hidden );
                    $spinner4.remove();
                    modalContent.append( m_subscriberIdHidden );
                    modalContent.append( m_paymentsunassignedCombobox );
                    modalContent.append( m_paymentsassignedCombobox );
                    $('#wgs-payments-unassigned-select, #wgs-payments-assigned-select').listswap({
                        truncate:false,
                        height:180,
                        is_scroll:true
                    });
                });


            };

            var callBackAssignPayments =  function( response ){
                $("div.notice").remove();
                $("div.notices").remove();
                if( response.success ){
                    //Set last payment date
                    $("#last-payment-display").text( response.last_payment_date );
                    var $lastPaymentInput = $("#wgs_f_last_payment");
                    $lastPaymentInput.val( response.last_payment_id );
                    //Actualize the status edition
                    var last_payment = ( $lastPaymentInput.val() !== undefined ) ? $lastPaymentInput.val() : null ;
                    var plan_id = $("#wgs_f_plan_id").val();
                    if( isStrNullUndefinedEmpty( response.status ) ){
                        var $hiddenSubscriberStatus = $("#hidden_subscriber_status");
                        $hiddenSubscriberStatus.val(response.status);
                        var $wgsSubscriberStatusInput = $("#wgs_f_status");
                        $wgsSubscriberStatusInput.val($hiddenSubscriberStatus.val());
                    }
                    actualizeStatusEdition( plan_id, last_payment );
                    //h4aAdminModal.close();
                    var $notices = h4aCommon.makeHTMLItem( "div", [ { 'classes' : [ "notices" ] } ] );
                    $notices.append( h4aAdminNotices.makeNotice( response.message, "success" ) );
                    $(".h4a-modal-content-right").children(".button").after( $notices );
                }else{
                    var $errors = h4aCommon.makeHTMLItem( "div", [ { 'classes' : [ "notices" ] } ] );
                    for( var e = 0; e < response.errors.length; e++){
                        var text = response.errors[e];
                        $errors.append( h4aAdminNotices.makeNotice( text ) )
                    }
                    response.errors = null;
                    $(".h4a-modal-content-right").children(".button").after( $errors );
                }
            };
        }
    });
});