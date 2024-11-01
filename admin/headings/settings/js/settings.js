jQuery( function($) {

    $(document).ready( function() {

        var h4aCommon = new H4ACommonPlugin;
        var h4aAdmin = new H4AAdminPlugin;
        var h4aAdminModal = new H4AAdminModalPlugin;
        
        var tab = h4aAdmin.getUrlParameter( "tab" );
        if( !isStrNullUndefinedEmpty( tab ) ){
            if( $( "#" + wgsSettingTranslation.tabs[tab] + "-menu" ).length ){
                var inp = h4aAdmin.getUrlParameter( "inp" );
                if( !isStrNullUndefinedEmpty( inp ) ){
                    var $input = $( "#" + inp );
                    if( $input.length ){
                        $input.focus();
                    }else if( !H4ACommonPlugin.inArray( tab, [ "currency", "paypal", "profile-page" ] ) ){
                        showAddonsModal( tab );
                    }
                }
            }else if( !H4ACommonPlugin.inArray( tab, [ "currency", "paypal", "profile-page" ] ) ){
                showAddonsModal( tab );
            }
        }
        
        function showAddonsModal( tab ) {
            
            var title = wgsSettingTranslation.modal_title;
            
            var attrs = [
                { 'classes' : [ "modal-main-content" ] }
            ];
            var modalContent = h4aCommon.makeHTMLItem( "section", attrs );
 
            var addonTitle, addonSrcImage;
            
            if( tab === "plans" ){
                addonTitle = "WGS Plan Edition Plus";
                addonSrcImage = "ic_wgs_pep_256x256.jpg"
            }else if( tab === "recaptcha" ){
                addonTitle = "WGS Recaptcha";
                addonSrcImage = "ic_wgs_r_256x256.jpg"
            }else if( tab === "plan-forms" ){
                addonTitle = "WGS Custom Forms";
                addonSrcImage = "ic_wgs_cf_256x256.jpg"
            }
            
            attrs = [
                { 'text' : wgsSettingTranslation.modal_explanation_begin }
            ];
            var modalContentLeftText = h4aCommon.makeHTMLItem( "p", attrs );

            attrs = [
                { 'alt' : addonTitle + "addon icon" },
                { 'title' : addonTitle + "addon icon" },
                { 'src' : wgsSettingTranslation.url_assets + addonSrcImage }
            ];
            var modalContentLeftImage = h4aCommon.makeHTMLItem( "img", attrs );

            attrs = [
                { 'text' : addonTitle + " " + wgsSettingTranslation.modal_subtitle_addon }
            ];
            var modalContentLeftSubtitle = h4aCommon.makeHTMLItem( "h2", attrs );
            
            attrs = [
                { 'id' : "modal-content-wgs-premium-left" }
            ];
            var modalContentLeft = h4aCommon.makeHTMLItem( 'aside', attrs );
            modalContentLeft.append( modalContentLeftText );
            modalContentLeft.append( modalContentLeftImage );
            modalContentLeft.append( modalContentLeftSubtitle );

            attrs = [
                { 'text' : wgsSettingTranslation.modal_subtitle }
            ];
            var modalContentRightSubtitle = h4aCommon.makeHTMLItem( "h2", attrs );

            attrs = [
                { 'html' : '<a href="https://wp-group-subscriptions.com" class="button button-primary button-large button-premium"> ' + wgsSettingTranslation.modal_button_premium  + "</a>" }
            ];
            var modalContentRightStep1 = h4aCommon.makeHTMLItem( "li", attrs );

            attrs = [
                { 'html' : '<a href="' + h4aCommon.getBaseUrl() + '/options-general.php?page=settings-wp-group-subscriptions&tab=premium" >' + wgsSettingTranslation.modal_txt_insert_key + '</a>' }
            ];
            var modalContentRightStep2 = h4aCommon.makeHTMLItem( 'li', attrs );

            attrs = [
                { 'text' :  wgsSettingTranslation.modal_txt_enjoy_settings }
            ];
            var modalContentRightStep3 = h4aCommon.makeHTMLItem( "li", attrs );
            
            attrs = [
                { 'classes' : [ "modal-premium-steps" ] }
            ];
            var modalContentRightSteps = h4aCommon.makeHTMLItem( "ol", attrs );
            modalContentRightSteps.append( modalContentRightStep1 );
            modalContentRightSteps.append( modalContentRightStep2 );
            modalContentRightSteps.append( modalContentRightStep3 );
                        
            attrs = [
                { 'id' : "modal-content-wgs-premium-right" }
            ];
            var modalContentRight = h4aCommon.makeHTMLItem( "aside", attrs );
            modalContentRight.append( modalContentRightSubtitle );
            modalContentRight.append( modalContentRightSteps );
            
            attrs = [
                { 'id' : "modal-content-wgs-premium" }
            ];
            var modalWrapperContent = h4aCommon.makeHTMLItem( "div", attrs );
            modalWrapperContent.append( modalContentLeft );
            modalWrapperContent.append( modalContentRight );

            modalContent.append( modalWrapperContent );

            var args = {
                'title' : title,
                'margin' : "100px",
                'content' : modalContent
            };
            
            h4aAdminModal.build( "#wpbody-content", args );
        }
    });

});