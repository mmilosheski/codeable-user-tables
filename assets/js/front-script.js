$ = jQuery;
$(document).ready(function() {
    var table = $('#codeable_users_table').DataTable(
        {
            "processing": true,
            "serverSide": true,
            "ordering": true,
            "ajax": ajax_url,
            "initComplete": function () {
                this.api().columns(2).every( function () {
                    var table = $('#codeable_users_table select');
                    var column = this;
                    var select = $('<select><option value="">-- Select role ---</option></select>')
                        .appendTo( $(column.header()).empty() )
                        .on( 'change', function () {
                            var val = $.fn.dataTable.util.escapeRegex(
                                $(this).val()
                            );
                            column
                                .search( val ? val : '', true, false )
                                .draw();
                        } );
                    column.data().unique().sort().each( function ( d, j ) {
                        for (let [key, value] of Object.entries(roles_data)) {
                            select.append( '<option value="'+key+'">'+value+'</option>' )
                        }
                    } );
                } );
            },
            "columnDefs": [ {
                "targets": 'no-sort',
                "orderable": false,
            } ]
        }
    );
    $('#codeable_users_table').on('change', function(e){
        e.preventDefault();
        table
            .order( [ 0, 'asc' ])
            .draw();    })
} );