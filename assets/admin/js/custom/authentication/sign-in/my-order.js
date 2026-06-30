    $(document).ready(function(){

    $(document).on('click', '.toggle-expand', function () {
        const icon = $(this).find('.toggle-icon');
        const orderId = $(this).data('order-id');
        const contentDiv = $('#expand-' + orderId);

        contentDiv.toggleClass('d-none');

        icon.toggleClass('fa-plus fa-minus');
    });


    var KTDatatablesExample = function () {

        var table;
        var datatable;

        var initDatatable = function () {
            table = document.querySelector('#kt_table_widget_order_table');
            if (!table) return;

            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url + "user/my-order-transaction-list",
                    type: "GET",
                    data: function(d) {
                        d.from_date = $('#cl_from_date').val(); 
                        d.to_date = $('#cl_to_date').val(); 
                        d.client_filter = agent_id; 
                        d.call_status = $('#call_status').val(); 
                    }
                },
                columns: [
                   { data: 'RecordID' },
                    { data: 'OrderID' },
                    { data: 'Created' },
                    { data: 'Total' },
                    { data: 'Profit' },
                    { data: 'Status' },
                    { data: 'Action' }
                ]
            });
        }
        
        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter, #call_status').on('change', function () {
                datatable.ajax.reload(null, false); 
                loadData();
            });
        }

        return {
            init: function () {
                table = document.querySelector('#kt_table_widget_order_table');
    
                if ($.fn.DataTable.isDataTable(table)) {
                    $(table).DataTable().clear().destroy();
                }
                
                if (!table) {
                    return;
                }
                initDatatable();
                handleFilterChange(); 
            }
        };
    }();

    KTDatatablesExample.init();
    
     function loadData(){

        var from_date = $('#cl_from_date').val(); 
        var to_date = $('#cl_to_date').val(); 
        var client_filter = $('#client_filter').val(); 
        var call_status = $('#call_status').val(); 

        $.ajax({
            url: base_url + 'all-transaction-get', 
            type: 'POST',
            data: {
                from_date: from_date,
                to_date: to_date,
                client_filter :client_filter,
                call_status :call_status,
            },
            success: function(response) {
                var data = JSON.parse(response);
                let totalAmount = typeof data.total_amount === "string" 
                ? data.total_amount.replace(/,/g, '') 
                : data.total_amount;

                let totalTokenAmount = typeof data.total_token_amount === "string" 
                ? data.total_token_amount.replace(/,/g, '') 
                : data.total_token_amount;

                const count1 = new countUp.CountUp("icd", parseFloat(totalAmount) || 0);
                const count2 = new countUp.CountUp("tcd", parseFloat(totalTokenAmount) || 0);

                count1.start();
                count2.start();
            },
            error: function(xhr, status, error) {
                console.error('Error fetching chart data:', error);
            }
        });
    };


});
