$(function() {
    $('#form-login #loginCode').bind("change paste keyup", function(){
        if ($(this).val().length == 8) {
            $('#form-login').submit();
        }
    });
    
    $('#whatsAppLogin-input').keyup(function(e){
        if(e.keyCode == 13) {
            requestWhatsAppLoginCode();
        }
    });
    $('#whatsAppLogin-code-input').bind("change paste keyup", function(){
        if ($('#whatsAppLogin-code-input').val().length == 4) {
            whatsAppLogin();
        }
    });
});


var codeRequested = false;


function requestWhatsAppLoginCode() {
    var input = $('#whatsAppLogin-input').val();
    
    $.post('/', {
        action: 'requestWhatsAppLoginCode',
        input: input
    }, function(data){
        var result = $.parseJSON(data);
        var success = result.success;
        
        if (success) {
            codeRequested = true;
            
            $('#whatsAppLoginUI-1').slideUp();
            $('#whatsAppLoginUI-2').slideDown();
        }
        
        renderAlerts(result.alerts);
    });
}

function whatsAppLogin() {
    var code = $('#whatsAppLogin-code-input').val();
    
    $.post('/', {
        action: 'whatsAppLogin',
        code: code
    }, function(data){
        var result = $.parseJSON(data);
        var success = result.success;
        
        if (success) {
            location.reload();
        }
        
        renderAlerts(result.alerts);
    });
}

function nextWhatsAppLoginStep() {
    if (!codeRequested) {
        requestWhatsAppLoginCode();
    } else {
        whatsAppLogin();
    }
}
