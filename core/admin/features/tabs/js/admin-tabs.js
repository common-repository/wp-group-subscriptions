
/*
 * JavaScript for Settings Submenu Page
 *
 */
function ShowTab(TabName) {
    jQuery(".h4a-option-tab").each(function() {
        jQuery(this).addClass("h4a-hidden-tab");
        jQuery(this).removeClass("h4a-active-tab");
    });
    var $tabContent = jQuery("#"+TabName+"-content");
    $tabContent.removeClass("h4a-hidden-tab");
    $tabContent.addClass("h4a-active-tab");

    jQuery(".nav-tab").each(function() {
        jQuery(this).removeClass("nav-tab-active");
    });
    jQuery("#"+TabName+"-menu").addClass("nav-tab-active");
}

jQuery(function($) {
    $(document).ready( function() {
        var attrID = $( ".h4a-option-tab" ).first().attr("id").split("-content");
        var href = attrID[0];
        ShowTab(href);

    });
});

    
    










