/*
 * JavaScript only for Plan page
 *
 */
jQuery( function($) {

    $(document).ready( function() {

        var h4aCommon = new H4ACommonPlugin;
        var h4aCommonLoader = new H4ACommonLoaderPlugin;
        var h4aAdmin = new H4AAdminPlugin;


        var $planTitleInput = $( '#title' );
        if ( '' === $planTitleInput.val() ) {
            $planTitleInput.focus();
        }

        $("#wgs_f_is_free").click(function() {
            var $wgsPriceInput = $("#wgs_f_price");
            if ($(this).is(':checked')) {
                $wgsPriceInput.val("0.00");
                $wgsPriceInput.attr("disabled", true);
            }else{
                $wgsPriceInput.attr("disabled", false);
            }
        });

        $('[name="wgs_f_plan_duration"]:radio').on("change", function() {
            var $wgsPlanDurationNumberInput = $("#wgs_f_plan_duration_number");
            if ($("#wgs_f_plan_duration_value").is(':checked')) {
                $wgsPlanDurationNumberInput.attr("disabled", false);
                $("#wgs_f_plan_duration_time_type").attr("disabled", false);
            }else{
                $wgsPlanDurationNumberInput.attr("disabled", true);
                $("#wgs_f_plan_duration_time_type").attr("disabled", true);
                $wgsPlanDurationNumberInput.val("");
            }
            var $wgsPlanDurationDateInput = $("#wgs_f_plan_duration_date");
            if ($("#wgs_f_plan_duration_until").is(":checked")) {
                $wgsPlanDurationDateInput.attr("disabled", false);
            } else {
                $wgsPlanDurationDateInput.val("YYYY-MM-DD");
                $wgsPlanDurationDateInput.attr("disabled", true);
            }
        });

        $("input[name=wgs_f_plan_type]").click(function() {
            var $wgsNumberMinMemberAccountsInput = $("#wgs_f_number_min_member_accounts");
            var $wgsNumberMaxMemberAccountsInput = $("#wgs_f_number_max_member_accounts");
            $wgsNumberMinMemberAccountsInput.hide();
            $wgsNumberMaxMemberAccountsInput.hide();
            var $spinner1 = h4aCommonLoader.makeSpinner();
            var $spinner2 = h4aCommonLoader.makeSpinner();
            var $spinner3 = h4aCommonLoader.makeSpinner();
            var attrs = [
                { 'classes' : [ "wgs-wrapper-spinner", "wgs-input-wrapper-spinner" ] }
            ];
            var wrapperSpinner1 = h4aCommon.makeHTMLItem( "div", attrs );
            var wrapperSpinner2 = h4aCommon.makeHTMLItem( "div", attrs );
            wrapperSpinner1.append( $spinner1 );
            wrapperSpinner2.append( $spinner2 );
            $wgsNumberMinMemberAccountsInput.after( wrapperSpinner1 );
            $wgsNumberMaxMemberAccountsInput.after( wrapperSpinner2 );
            $("input[name=wgs_f_plan_type]").attr( "disabled", "disabled" );
            var $wgsPlanForm = $('#wgs_f_plan_form');
            $wgsPlanForm.attr( "disabled", "disabled" );
            var attrs_3 = [
                { 'classes' : [ "wgs-wrapper-spinner" ] }
            ];
            var wrapperSpinner3 = h4aCommon.makeHTMLItem( "div", attrs_3 );
            wrapperSpinner3.append( $spinner3 );
            $wgsPlanForm.after( wrapperSpinner3 );
            var data = {
                'action': 'getAllPlanFormsAsOptionsByAjax',
                'plan_type': $(this).val()
            };
            $.post( ajaxurl, data, function(response) {
                var jsonObj = JSON.parse( response );
                $wgsPlanForm
                    .find('option')
                    .remove()
                    .end();
                for (var key in jsonObj) {
                    if (jsonObj.hasOwnProperty(key)) {
                        if( key === ''){
                            $('#wgs_f_plan_form').prepend('<option value="' + key + '">' + jsonObj[key] + '</option>')
                        }else{
                            $('#wgs_f_plan_form').append('<option value="' + key + '">' + jsonObj[key] + '</option>')
                        }
                    }
                }
                $wgsPlanForm.val("");
            });
            var dataInterval = {
                'action': 'getIntervalByPlanTypeByAjax',
                'plan_type': $(this).val()
            };
            $.post( ajaxurl, dataInterval, function(response) {
                var jsonObj = JSON.parse( response );
                var $wgsNumberMinMemberAccountsInput = $('#wgs_f_number_min_member_accounts');
                $wgsNumberMinMemberAccountsInput
                    .find('option')
                    .remove()
                    .end();
                for (var key in jsonObj) {
                    if (jsonObj.hasOwnProperty(key)) {
                        $wgsNumberMinMemberAccountsInput.append('<option value="' + key + '">' + jsonObj[key] + '</option>');
                    }
                }
                $('#wgs_f_number_min_member_accounts option:first').attr('selected','selected');

                dataInterval['opt_unlimited'] = true;

                $.post( ajaxurl, dataInterval, function(response) {
                    var jsonObj = JSON.parse( response );
                    var $wgsNumberMaxMemberAccountsInput = $('#wgs_f_number_max_member_accounts');
                    $wgsNumberMaxMemberAccountsInput
                        .find('option')
                        .remove()
                        .end();
                    for (var key in jsonObj) {
                        if (jsonObj.hasOwnProperty(key)) {
                            $wgsNumberMaxMemberAccountsInput.append('<option value="' + key + '">' + jsonObj[key] + '</option>');
                        }
                    }
                    $('#wgs_f_number_max_member_accounts option:first').attr('selected','selected');
                    $('.wgs-wrapper-spinner').remove();
                    var $wgsNumberMinMemberAccountsInput = $("#wgs_f_number_min_member_accounts");
                    $wgsNumberMinMemberAccountsInput.show();
                    $wgsNumberMaxMemberAccountsInput.show();
                    $("input[name=wgs_f_plan_type]").attr( "disabled", null );
                    $('#wgs_f_plan_form').attr( "disabled", null );
                    if ($("#wgs_f_plan_type_multiple").is(':checked')) {
                        $wgsNumberMinMemberAccountsInput.attr("disabled", false);
                        $wgsNumberMaxMemberAccountsInput.attr("disabled", false);
                    }else{
                        $wgsNumberMinMemberAccountsInput.attr("disabled", true);
                        $wgsNumberMaxMemberAccountsInput.attr("disabled", true);
                    }

                });

            });
        });

        $("#wgs_f_plan_is_tag").click(function() {
            var $wgsPlanTagInput = $("#wgs_f_plan_tag");
            if ($(this).is(':checked')) {
                $wgsPlanTagInput.attr("disabled", false);
                $wgsPlanTagInput.attr("required", "required");
            }else{
                $wgsPlanTagInput.attr("disabled", true);
                $wgsPlanTagInput.attr("required", false);
                $wgsPlanTagInput.val("");

            }
        });

        function testMinMaxInterval(){
            var min = parseInt( $("#wgs_f_number_min_member_accounts").val() );
            var max = parseInt( $("#wgs_f_number_max_member_accounts").val() );
            if( !isStrNullUndefinedEmpty( max ) && max <=  min ){
                document.getElementById( "wgs_f_number_max_member_accounts" ).setCustomValidity( wgsPlanTranslation.msg_must_greater + " " + min );
            }else{
                document.getElementById( "wgs_f_number_max_member_accounts" ).setCustomValidity( '' );
            }
        }

        $("#wgs_f_number_min_member_accounts").change( function(){
            testMinMaxInterval();
        });

        $("#wgs_f_number_max_member_accounts").change( function(){
            testMinMaxInterval();
        });

        var $planSelectedSelect = $('#plan-status-select');

        // Plan Status edit click.
        $planSelectedSelect.siblings('a.edit-plan-status').click( function(event ) {
            if ( $planSelectedSelect.is( ':hidden' ) ) {
                $planSelectedSelect.slideDown( 'fast', function() {
                    $planSelectedSelect.find('select').focus();
                } );
                $(this).hide();
            }
            event.preventDefault();
        });

        // Save the Plan Status changes and hide the options.
        $planSelectedSelect.find('.save-plan-status').click( function(event ) {
            $planSelectedSelect.slideUp( 'fast' ).siblings( 'a.edit-plan-status' ).show().focus();
            $('#plan-status-display').html( $('option:selected', $('#plan_status')).text());
            event.preventDefault();
        });

        // Cancel Plan Status editing and hide the options.
        $planSelectedSelect.find('.cancel-plan-status').click( function(event ) {
            $planSelectedSelect.slideUp( 'fast' ).siblings( 'a.edit-plan-status' ).show().focus();
            var $planStatusInput = $('#plan_status');
            $planStatusInput.val( $('#hidden_plan_status').val() );
            $('#plan-status-display').html( $('option:selected', $planStatusInput).text());
            event.preventDefault();
        });



        $("#wgs-admin-edit-plan").submit(function() {
            $("*:disabled").prop("disabled", false);
        });

        h4aAdmin.titleHint();
    });

});