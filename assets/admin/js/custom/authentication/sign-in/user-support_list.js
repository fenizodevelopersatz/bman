$(document).ready(function(){
   
    var KTDatatablesExample = function () {
        var table;
        var datatable;
        let filter_by = 0;

        var initDatatable = function () {
            const tableRows = table.querySelectorAll('tbody tr');
            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url + "user/support-list",
                    type: "GET",
                    data: function(d) {
                        d.from_date = $('#cl_from_date').val(); 
                        d.to_date = $('#cl_to_date').val(); 
                        d.client_filter = agent_id; 
                        d.call_status = $('#call_status').val(); 
                        d.coin = $('#selected_coin_filter').val();
                        d.filter_by = filter_by
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'TicketInfo' },
                    { data: 'DateInfo' },
                    { data: 'Status' },
                    { data: 'Action' },
                ]
            });

        }
        
        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter, #call_status, #selected_coin_filter').on('change', function () {
                datatable.ajax.reload(null, false); 
                loadData();
            });
        }


         $('#new_ticket_click').on('click',function(){
            $('.menu-item .menu-link').removeClass('active');
            $(this).addClass("active");
            filter_by = "new_ticket";
            datatable.ajax.reload(null, false); 
            loadData();
         });

         $('#all_ticket_click').on('click',function(){
            $('.menu-item .menu-link').removeClass('active');
            $(this).addClass("active");
            filter_by = "all_ticket";
            datatable.ajax.reload(null, false); 
            loadData();
         });
         
         $('#pending_ticket_click').on('click',function(){
            $('.menu-item .menu-link').removeClass('active');
            $(this).addClass("active");
            filter_by = "0";
            datatable.ajax.reload(null, false); 
            loadData();
         });

         $('#open_ticket_click').on('click',function(){
            $('.menu-item .menu-link').removeClass('active');
            $(this).addClass("active");
            filter_by = "1";
            datatable.ajax.reload(null, false); 
            loadData();
         });

         $('#close_ticket_click').on('click',function(){
            $('.menu-item .menu-link').removeClass('active');
            $(this).addClass("active");
            filter_by = "2";
            datatable.ajax.reload(null, false); 
            loadData();
         });

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
                handleFilterChange();

            }
        };
    }();

    KTDatatablesExample.init();
    

});