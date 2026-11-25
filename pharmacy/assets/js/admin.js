/**
 * admin.js
 *
 * Scripts used only on admin pages. Loaded after main.js.
 */

$(function () {

    // Turn every table marked with class "js-datatable" into a DataTable.
    // SRS NFR Usability: "Data tables with more than 10 rows shall include
    // column sorting, pagination, and search via DataTables.js."
    $('table.js-datatable').each(function () {
        const $table = $(this);

        $table.DataTable({
            // Column sorting and paging need to know which columns hold
            // buttons rather than data; those carry data-orderable="false".
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [],           // keep the order the SQL query gave
            language: {
                search: '',
                searchPlaceholder: 'Search this table',
                lengthMenu: 'Show _MENU_ rows',
                info: 'Showing _START_ to _END_ of _TOTAL_',
                infoEmpty: 'Nothing to show',
                emptyTable: 'Nothing to show yet',
                zeroRecords: 'No rows match your search',
                paginate: { previous: 'Previous', next: 'Next' }
            }
        });
    });

});