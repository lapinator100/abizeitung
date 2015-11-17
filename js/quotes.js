var removeQuoteButton = '<button class="btn btn-danger btn-xs" style="margin-left: 5px;" data-toggle="modal" data-target="#modal-removeConfirmation" onclick="prepareRemoval(removeQuote);">Löschen</button>';


$(function(){
    $("#form-quote").submit(function(e){
        e.preventDefault();
        
        var class_ = $(this).find('#class').val();
        var quote = $(this).find('#quote').val();
        
        $(this).find('#class').val('');
        $(this).find('#quote').val('');
        
        if (quote.trim() != '') {
            submitQuote(quote, class_);
        }
    });
});

function addQuote(quotesContainer, quote) {
    quoteObj = '<li data-id="' + quote.id + '">';
    quoteObj += '<b>' + quote.class_ + ':</b> ';
    quoteObj += quote.text;
    if (quote.own) {
        quoteObj += removeQuoteButton;
    }
    quoteObj += '</li>';
    
    quotesContainer.find('ul').append(quoteObj);
}

function submitQuote(quote, class_) {
    $.post('/', {
        action: 'submitQuote',
        class_: class_,
        quote: quote
    }, function(data){
        var result = $.parseJSON(data);
        var quote = result.quote;
        var quotesContainer = $('#quotesContainer');
        
        if (quote != false) {
            quote.text = htmlEntities(quote.text);
            
            quotesContainer.find('#quotesPlaceholder').hide();
            addQuote(quotesContainer, quote);
        }
        renderAlerts(result.alerts);
    });
}

function removeQuote(quote_id) {
    $.post('/', {
        action: 'removeQuote',
        quote_id: quote_id
    }, function(data){
        var result = $.parseJSON(data);
        var success = result.success;
        var quote = $('#quotesContainer li[data-id="' + quote_id + '"]');
        
        if (success) {
            quote.slideUp(500);
            
            if (quote.parent().children().length <= 1) {
                quote.parent().parent().find('#quotesPlaceholder').fadeIn(500);
            }
        }
        renderAlerts(result.alerts);
    });
}