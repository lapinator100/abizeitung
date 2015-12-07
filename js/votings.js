$(function(){
  $('.votings-list a').on('click', function(e){
      var id = $(this).data('id');
      var oldId = $('#votings-list a.active').data('id');

      if (id == oldId) {
          return;
      }

      var votingContainer = $('#votingContainer');

      votingContainer.fadeOut(100);
      $('#votingPlaceholder').fadeOut(100);
      $('.votings-list a').removeClass('active');
      $(this).addClass('active');

      setTimeout(function(){
          getVoting(id, function(voting){
              renderVoting(votingContainer, voting);
          });
      }, 100);
  });

  $("#form-suggestion").submit(function(e){
      e.preventDefault();

      var id = $(this).find('#id').val();
      var user_id = $(this).find('#user_id').val();
      var text = $(this).find('#text').val();

      $(this).find('#text').val('');
      $(this).find('#user_id').val('');

      if (text.trim() !== '' || (user_id !== null && user_id.trim() !== '')) {
          submitSuggestion(id, user_id, text);
      }
  });
});


function getVoting(id, callback) {
    $.post('/', {
        action: 'getVoting',
        id: id
    }, function(data){
        var result = $.parseJSON(data);
        var voting = result.voting;

        renderAlerts(result.alerts);
        callback(voting);
    });
}

function renderVoting(votingContainer, voting) {
    var suggestionsContainer = votingContainer.find('#suggestionsContainer');

    votingContainer.find('h2').html(voting.title);
    votingContainer.find('#suggestionsPlaceholder span').html(voting.title);
    votingContainer.find('#form-suggestion #id').val(voting.id);

    if (voting.type === 'text') {
        $(votingContainer).find('#user_id').hide();
        $(votingContainer).find('#text').show();
    } else {
        $(votingContainer).find('#user_id').show();
        $(votingContainer).find('#text').hide();
    }

    renderSuggestions(suggestionsContainer, voting.suggestions);

    votingContainer.fadeIn(100);
}

function renderSuggestions(suggestionsContainer, suggestions) {
    suggestionsContainer.find('#suggestionsPlaceholder').show();
    suggestionsContainer.find('div').empty();

    if (suggestions.length !== 0) {
        suggestionsContainer.find('#suggestionsPlaceholder').hide();
    }

    suggestions.forEach(function(suggestion){
        addSuggestion(suggestionsContainer, suggestion);
    });
}

function bindVoteButton(suggestionObj) {
  suggestionObj.find('button').on('click', function(e){
      var button = $(this);
      var id = button.parent().data('id');
      var votingId = $('#votingContainer').find('form #id').val();
      var vote = button.hasClass('active') ? 'no' : 'up';

      $.post('/', {
          action: 'voteForSuggestion',
          id: id,
          voting_id: votingId,
          vote: vote
      }, function(data){
          var result = $.parseJSON(data);

          if (result.success) {
            if (vote === 'up') {
                button.addClass('active');
            } else {
                button.removeClass('active');
            }
          }
      });
  });
}

function addSuggestion(suggestionsContainer, suggestion) {
    suggestionObj = '<div class="voting-item" data-id="' + suggestion.id + '">';
    suggestionObj += '<button class="btn btn-default glyphicon glyphicon-menu-up';

    if (suggestion.vote === 'up') {
      suggestionObj += ' active';
    }

    suggestionObj += '"></button>';
    suggestionObj += '<span>' + (suggestion.user || suggestion.text) + '</span>';
    suggestionObj += '</div>';

    suggestionObj = $(suggestionObj);
    suggestionsContainer.find('> div').append(suggestionObj);
    bindVoteButton(suggestionObj);
}

function submitSuggestion(id, user_id, text) {
    $.post('/', {
        action: 'submitSuggestion',
        id: id,
        user_id: user_id,
        text: text
    }, function(data){
        var result = $.parseJSON(data);
        var suggestion = result.suggestion;
        var suggestionsContainer = $('#suggestionsContainer');

        if (suggestion !== false) {
            suggestion.text = htmlEntities(suggestion.text);

            suggestionsContainer.find('#suggestionsPlaceholder').hide();
            addSuggestion(suggestionsContainer, suggestion);
        }
        renderAlerts(result.alerts);
    });
}
