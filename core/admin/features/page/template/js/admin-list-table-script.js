jQuery( function($) {
    $(document).ready(function() {
        $("form").submit(function() {
            var action_btn = $("input[type=submit][clicked=true]").attr("id");
            var $typeSubmitInput = $('<input />').attr('type', 'hidden')
                .attr('name', "submit_type");

            if( action_btn === "search-submit" ){
                $typeSubmitInput.attr('value', "search");
            }else if( action_btn === "doaction" || action_btn === "doaction2" ){
                $typeSubmitInput.attr('value', "bulk_actions");
            }
            $typeSubmitInput.appendTo('form');
            return true;
        });
        $("form input[type=submit]").click( function() {
            $("input[type=submit]", $(this).parents("form")).removeAttr("clicked");
            $(this).attr("clicked", "true");
        });
    });
});