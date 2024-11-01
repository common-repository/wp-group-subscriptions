jQuery( function($) {

    $(document).ready( function() {

        var h4aCommon = new H4ACommonPlugin;
        var h4aAdmin = new H4AAdminPlugin;
        var h4aAdminModal = new H4AAdminModalPlugin;
        var location_href = "/wp-admin/options-general.php?page=settings-wp-group-subscriptions&tab=premium";
        $("#btn_activate").on("click", function(e){
            e.preventDefault();
            $("form").attr('action', location_href + "&action=activate" );
            $("#submit").click();
        });

        $("#btn_deactivate").on("click", function(e){
            e.preventDefault();
            $("form").attr('action', location_href + "&action=deactivate" );
            $("#submit").click();
        });

    });

});