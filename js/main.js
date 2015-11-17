var alertDismissButton = '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';

var currentRemovalFunction = function(){};
var currentRemovalId = -1;

var currentAdditionFunction = function(){};
var currentAdditionType = '';
var currentAdditionId = -1;


$(function() {
    $('[data-toggle="tooltip"]').tooltip({
        animate: true,
        container: 'body'
    });
    
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){    
        var newTab = $(e.target).attr('href');
        
        $(newTab).css({ transform: 'scale(0.95)',
                       transformOrigin: '50% 250px'
                      }).transition({ scale: 1.0 }, 500, 'snap');
    });
    
    
    $('#modal-removeConfirmation').on('show.bs.modal', function(e){
        var button = $(e.relatedTarget);
        var id = button.parent().data('id');
        
        currentRemovalId = id;
    });
});


function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}


function renderAlerts(alerts) {
    alerts.forEach(function(alertObj){
        renderAlert(alertObj)
    });
}

function renderAlert(alertObj) {
    var alertContainer = $('#alertContainer');
    
    var alertHtml = '<div class="alert alert-' + alertObj['type'] + ' alert-dismissible" role="alert">';
    alertHtml += alertDismissButton;
    alertHtml += '<strong>' + alertObj['type_legible'] + '!</strong> ' + alertObj['text'];
    alertHtml += '</div>';
    
    alertContainer.append(alertHtml);
}


function prepareRemoval(removalFunction){
    currentRemovalFunction = removalFunction;
}

function performRemoval(){
    currentRemovalFunction(currentRemovalId);
    currentRemovalFunction = function(){};
    currentRemovalId = -1;
    
    $('#modal-removeConfirmation').modal('hide');
}


function prepareAddition(additionFunction, additionType){
    type = '#' + additionType.split('_')[0];
    
    currentAdditionFunction = additionFunction;
    currentAdditionType = additionType;
    currentAdditionId = $(type + ' #personContainer #id').val();
}

function performAddition(){
    var text = $('#modal-add-text').val();
    $('#modal-add-text').val('');
    
    currentAdditionFunction(currentAdditionType, currentAdditionId, text);
    currentAdditionFunction = function(){};
    currentAdditionType = '';
    currentAdditionId = -1;
    
    $('#modal-add').modal('hide');
}
