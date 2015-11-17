var removeRumourButton = '<button class="btn btn-danger btn-xs" style="margin-left: 5px;" data-toggle="modal" data-target="#modal-removeConfirmation" onclick="prepareRemoval(removeRumour);">Löschen</button>';


$(function(){
    $("#form-rumour").submit(function(e){
        e.preventDefault();
        
        var rumour = $(this).find('#rumour').val();
        $(this).find('#rumour').val('');
        
        if (rumour.trim() != '') {
            submitRumour(rumour);
        }
    });
});

function addRumour(rumoursContainer, rumour) {
    rumourObj = '<li data-id="' + rumour.id + '">';
    rumourObj += rumour.text;
    if (rumour.own) {
        rumourObj += removeRumourButton;
    }
    rumourObj += '</li>';
    
    rumoursContainer.find('ul').append(rumourObj);
}

function submitRumour(rumour) {
    $.post('/', {
        action: 'submitRumour',
        rumour: rumour
    }, function(data){
        var result = $.parseJSON(data);
        var rumour = result.rumour;
        var rumoursContainer = $('#rumoursContainer');
        
        if (rumour != false) {
            rumour.text = htmlEntities(rumour.text);
            
            rumoursContainer.find('#rumoursPlaceholder').hide();
            addRumour(rumoursContainer, rumour);
        }
        renderAlerts(result.alerts);
    });
}

function removeRumour(rumour_id) {
    $.post('/', {
        action: 'removeRumour',
        rumour_id: rumour_id
    }, function(data){
        var result = $.parseJSON(data);
        var success = result.success;
        var rumour = $('#rumoursContainer li[data-id="' + rumour_id + '"]');
        
        if (success) {
            rumour.slideUp(500);
            
            if (rumour.parent().children().length <= 1) {
                rumour.parent().parent().find('#rumoursPlaceholder').fadeIn(500);
            }
        }
        renderAlerts(result.alerts);
    });
}