!function( exports, $, undefined) {

    var H4ACommonFormPlugin = function() {
        
        H4ACommonFormPlugin.checkMatch = function (idInputReferring, idInputRepeat){
            var inputReferring = document.getElementById( idInputReferring );
            var inputRepeat = document.getElementById( idInputRepeat );
            if (inputReferring.value !== inputRepeat.value) {
                inputRepeat.setCustomValidity( commonFormTranslation.msg_must_match );
                inputRepeat.checkValidity();
                setTimeout(function() {
                    inputRepeat.reportValidity();
                    console.dir( inputReferring.validity.invalid );
                    console.dir( inputReferring.validationMessage );
                }, 1);

            } else {
                inputRepeat.setCustomValidity('');
                inputRepeat.checkValidity();
            }
        };

        H4ACommonFormPlugin.inputsMatch = function ( inputTypes, idInputReferring, idInputRepeat ){
            var inputReferring = document.getElementById( idInputReferring );
            var inputRepeat = document.getElementById( idInputRepeat );

            var checkValidity = function() {
                H4ACommonFormPlugin.checkMatch(idInputReferring, idInputRepeat);
            };

            inputReferring.addEventListener('change', checkValidity, false);
            inputRepeat.addEventListener('change', checkValidity, false);

        };

        return H4ACommonFormPlugin;
    };

    exports.H4ACommonFormPlugin = H4ACommonFormPlugin;

}(this, jQuery);



 

