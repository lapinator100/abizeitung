var removeMythButton = '<button class="btn btn-danger btn-xs" style="margin-left: 5px;" data-toggle="modal" data-target="#modal-removeConfirmation" onclick="prepareRemoval(removeMyth);">Löschen</button>';


$(function(){
    $("#form-myth").submit(function(e){
        e.preventDefault();

        var myth = $(this).find('#myth').val();
        $(this).find('#myth').val('');

        if (myth.trim() != '') {
            submitMyth(myth);
        }
    });
});

function addMyth(mythsContainer, myth) {
    mythObj = '<li data-id="' + myth.id + '">';
    mythObj += myth.text;
    if (myth.own) {
        mythObj += removeMythButton;
    }
    mythObj += '</li>';

    mythsContainer.find('ul').append(mythObj);
}

function submitMyth(myth) {
    $.post('/', {
        action: 'submitMyth',
        myth: myth
    }, function(data){
        var result = $.parseJSON(data);
        var myth = result.myth;
        var mythsContainer = $('#mythsContainer');

        if (myth != false) {
            myth.text = htmlEntities(myth.text);

            mythsContainer.find('#mythsPlaceholder').hide();
            addMyth(mythsContainer, myth);
        }
        renderAlerts(result.alerts);
    });
}

function removeMyth(myth_id) {
    $.post('/', {
        action: 'removeMyth',
        myth_id: myth_id
    }, function(data){
        var result = $.parseJSON(data);
        var success = result.success;
        var myth = $('#mythsContainer li[data-id="' + myth_id + '"]');

        if (success) {
            myth.slideUp(500);

            if (myth.parent().children().length <= 1) {
                myth.parent().parent().find('#mythsPlaceholder').fadeIn(500);
            }
        }
        renderAlerts(result.alerts);
    });
}
