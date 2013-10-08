# Server Side PHP AJAX

You can easily tell Footable to redraw by triggering the `footable_redraw`
event on the table:

    $('.get_data').click(function() {
        $.ajax({
            url : '/some/api',
            success : function(data) {
                $('table tbody').append(data).trigger('footable_redraw');
            }
        });
    });
