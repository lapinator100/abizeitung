$(function(){
    $('#modal-removeMotto').on('show.bs.modal', function(e){
        var button = $(e.relatedTarget);
        var mottoId = button.data('motto-id');
        var modal = $(this);
        
        modal.find('#mottoId').attr('value', mottoId);
    });
});