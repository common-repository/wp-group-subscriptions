jQuery( function($) {

    $(document).ready( function() {
        $("a.wgs-options-advanced-link").on( "click", function() {
            console.log( $(this).hasClass( "closed" ) );
            if( $(this).hasClass( "closed" ) ){
                $(this).removeClass( "closed" );
            }else{
                $(this).addClass( "closed" );
            }
        });
    });
});