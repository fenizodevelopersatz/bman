$(function () {
    var form = $('#contact-form');
    var formMessages = $('.ajax-response');

    $(form).submit(function (e) {
        e.preventDefault();

        var formData = $(form).serialize();

        $.ajax({
            type: 'POST',
            url: $(form).attr('action'),
            data: formData
        })
            .done(function (response) {
                $(formMessages).removeClass('error').addClass('success').text(response);
                $('#contact-form input,#contact-form textarea,#contact-form select').val('');
            })
            .fail(function (data) {
                $(formMessages).removeClass('success').addClass('error');
                if (data.responseText !== '') {
                    $(formMessages).text(data.responseText);
                } else {
                    $(formMessages).text('Oops! An error occurred and your message could not be sent.');
                }
            });
    });
});
