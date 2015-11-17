var removeCommentButton = '<button class="btn btn-danger btn-xs" style="margin-left: 5px;" data-toggle="modal" data-target="#modal-removeConfirmation" onclick="prepareRemoval(removeComment);">Löschen</button>';


$(function(){
    $('.people-list a').on('click', function(e){
        var type = $(this).parent().data('type');
        var id = $(this).data('id');
        var oldId = $('#' + type + ' a.active').data('id');

        if (id == oldId) {
            return;
        }

        var personContainer = $('#' + type + ' #personContainer');

        personContainer.fadeOut(100);
        $('#' + type + ' #personPlaceholder').fadeOut(100);
        $('#' + type + ' .people-list a').removeClass('active');
        $(this).addClass('active');

        setTimeout(function(){
            getPerson(type, id, function(person){
                renderPerson(personContainer, person);
            });
        }, 100);
    });

    $("#form-comment").submit(function(e){
        e.preventDefault();

        var comment = $(this).find('#comment').val();
        var type = $(this).find('#type').val();
        var id = $(this).find('#id').val();

        $(this).find('#comment').val('');

        if (comment.trim() != '') {
            submitComment(type, id, comment);
        }
    });
});


function getPerson(type, id, callback) {
    $.post('/', {
        action: 'getPerson',
        type: type,
        id: id
    }, function(data){
        var result = $.parseJSON(data);
        var person = result.person;

        renderAlerts(result.alerts);
        callback(person);
    });
}

function renderPerson(personContainer, person) {
    var comments = person.comments;
    var commentsContainer = personContainer.find('#commentsContainer');

    if (person.statistics != '') {
        personContainer.find('.statisticsPanel').show();
        personContainer.find('.statisticsPanel p').html(person.statistics);
    } else {
        personContainer.find('.statisticsPanel').hide();
    }

    personContainer.find('h2').html(person.name);
    personContainer.find('#commentsPlaceholder span').html(person.name);

    $('#nicknamesContainer p span').html(person.nicknames);
    if (person.type == 'teachers') {
        personContainer.find('h2').append('<small>' + person.class + '</small>');
    } else if (person.type == 'pupils') {
        $('#futureProfessionsContainer p span').html(person.futureProfessions);
    }

    personContainer.find('#form-comment #type').val(person.type);
    personContainer.find('#form-comment #id').val(person.id);

    renderComments(commentsContainer, comments);

    personContainer.fadeIn(100);
}

function renderComments(commentsContainer, comments) {
    commentsContainer.find('#commentsPlaceholder').show();
    commentsContainer.find('ul').empty();

    if (comments.length != 0) {
        commentsContainer.find('#commentsPlaceholder').hide();
    }

    comments.forEach(function(comment){
        addComment(commentsContainer, comment);
    });
}

function addComment(commentsContainer, comment) {
    commentObj = '<li data-id="' + comment.id + '">';
    commentObj += comment.text;
    if (comment.own) {
        commentObj += removeCommentButton;
    }
    commentObj += '</li>';

    commentsContainer.find('ul').append(commentObj);
}

function submitComment(type, id, comment) {
    $.post('/', {
        action: 'submitComment',
        type: type,
        id: id,
        comment: comment
    }, function(data){
        var result = $.parseJSON(data);
        var comment = result.comment;
        var commentsContainer = $('#' + type + ' #commentsContainer');

        if (comment != false) {
            comment.text = htmlEntities(comment.text);
            type = '#' + type.split('_')[0];

            if (comment.type == 'pupils' || comment.type == 'teachers') {
                commentsContainer.find('#commentsPlaceholder').hide();
                addComment(commentsContainer, comment);
            } else if (comment.type == 'pupils_nicknames' || comment.type == 'teachers_nicknames') {
                var container = $(type + ' #nicknamesContainer p span');
                var nicknames = container.html() + ', ' + comment.text;

                container.html(nicknames.replace(/^,/, ''));
            } else if (comment.type == 'pupils_futureProfessions') {
                var container = $(type + ' #futureProfessionsContainer p span');
                var futureProfessions = container.html() + ', ' + comment.text;

                container.html(futureProfessions.replace(/^,/, ''));
            }
        }
        renderAlerts(result.alerts);
    });
}

function removeComment(comment_id) {
    $.post('/', {
        action: 'removeComment',
        comment_id: comment_id
    }, function(data){
        var result = $.parseJSON(data);
        var success = result.success;
        var comment = $('#commentsContainer li[data-id="' + comment_id + '"]');

        if (success) {
            comment.slideUp(500);

            if (comment.parent().children().length <= 1) {
                comment.parent().parent().find('#commentsPlaceholder').fadeIn(500);
            }
        }
        renderAlerts(result.alerts);
    });
}
