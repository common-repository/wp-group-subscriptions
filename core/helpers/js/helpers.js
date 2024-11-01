function firstLetterCapitalize( string ) { 
    return string.charAt(0).toUpperCase() + string.slice(1); 
}

function isStrNullUndefinedEmpty( string ){
    return ( string === null || string === "" || string === undefined )
}

function isStrNullUndefined( string ){
    return ( string === null || string === undefined )
}

function isObjectEmpty(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return false;
    }
    return true;
}

function isArrayNullUndefinedEmpty( array ){
    return ( array === null || array === undefined || array.length === 0 )
}

function format_str_to_kebabcase( string ) {
    var f_str = replaceAll( string, ' ', '-');
    return f_str.toLowerCase();
}

function format_str_from_kebabcase( string ){
    var f_str = replaceAll( string, '-', ' ');
    return firstLetterCapitalize( f_str );
}

function format_str_to_underscorecase( string ) {
    var f_str = replaceAll( string, ' ', '_');
    return f_str.toLowerCase();
}

function replaceAll(str, find, replace) {
    return str.replace(new RegExp(find, 'g'), replace);
}