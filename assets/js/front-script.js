$ = jQuery;
$(document).ready(function() {
    $('#codeable_users_table').dataTable(
        {
            "processing": true,
            "serverSide": true,
            "ajax": ajax_url,
        }
    );
} );