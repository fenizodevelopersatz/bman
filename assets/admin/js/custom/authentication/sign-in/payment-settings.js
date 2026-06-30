$(document).ready(function(){
    

    var KTDatatablesExample = function () {
        var table;
        var datatable;
    
        var initDatatable = function () {
         
            const tableRows = table.querySelectorAll('tbody tr');
            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url + "payment-list",
                    type: "GET",
                    data: function(d) {
                        d.from_date = $('#cl_from_date').val(); 
                        d.to_date = $('#cl_to_date').val(); 
                        d.client_filter = $('#client_filter').val(); 
                        d.agent_filter = $('#agent_filter').val(); 
                        d.call_status = $('#call_status').val(); 
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'paymentImg' },
                    { data: 'paymentStatus' },
                    { data: null },
                ],
                columnDefs: [
                    {
                        targets: -1,
                        data: null,
                        orderable: false,
                        className: 'text-end',
                        render: function (data, type, row) {
                            var encodedAgentId = encodeURIComponent(row.paymentid);
                            var action_url = "#";
                            //base_url+'payment-edit/'+encodedAgentId;
                            return `
                                <a href="`+action_url+`" class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle_s demo-block" 
                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                                    Edit
                                    <span class="svg-icon fs-5 m-0">
                                    </span>
                                </a>
                            `;
                        },
                    },
                ],
            });


              document.querySelectorAll('.demo-block').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        icon: 'info',
                        title: 'Demo Version',
                        text: 'This action is disabled in the demo version for security reasons.',
                        confirmButtonText: 'Ok, got it!',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                });
            });

        }
        
   
    
        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter, #agent_filter, #call_status').on('change', function () {
                datatable.draw();
            });
        }
    
        var exportButtons = () => {
            const documentTitle = 'Customer Orders Report';
            var buttons = new $.fn.dataTable.Buttons(table, {
                buttons: [
                    {
                        extend: 'copyHtml5',
                        title: documentTitle
                    },
                    {
                        extend: 'excelHtml5',
                        title: documentTitle
                    },
                    {
                        extend: 'csvHtml5',
                        title: documentTitle
                    },
                    {
                        extend: 'pdfHtml5',
                        title: documentTitle
                    }
                ]
            }).container().appendTo($('#kt_datatable_example_buttons'));
    
            const exportButtons = document.querySelectorAll('#kt_datatable_example_export_menu [data-kt-export]');
            exportButtons.forEach(exportButton => {
                exportButton.addEventListener('click', e => {
                    e.preventDefault();
    
                    const exportValue = e.target.getAttribute('data-kt-export');
                    const target = document.querySelector('.dt-buttons .buttons-' + exportValue);
    
                    target.click();
                });
            });
        }
    
        var handleSearchDatatable = function () {
            const filterSearch = document.querySelector('[data-kt-docs-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                datatable.search(e.target.value).draw();
            });
        }
    
        return {
            init: function () {
                table = document.querySelector('#kt-client-follow-table');
    
                 if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().clear().destroy();
                }
                
                if (!table) {
                    return;
                }
                
                
    
                initDatatable();
                exportButtons();
                handleFilterChange(); 
                handleSearchDatatable();

            }
        };
    }();

    KTDatatablesExample.init();

});