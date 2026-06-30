

$(document).ready(function(){

    axios.get(base_url+'view-user-info/'+agent_id)
    .then(function(response) {
        const data = response.data.data;
        const data_result = response.data.result;

        if(data_result){

        $('#username').html(data.name);
        $('#useremail').html(data.email);
        $('#registerdate').html(data.register_date);
        $('#agent_mail').html(data.email);
        $('#sponser').html(data.sponser);

        $('#my_investment').html(data.my_investment);
        $('#left_leg_investment').html(data.left_leg_investment);
        $('#right_leg_investment').html(data.right_leg_investment);

        $('#my_investment_token').html(data.my_investment_token);
        $('#left_leg_investment_token').html(data.left_leg_investment_token);
        $('#right_leg_investment_token').html(data.right_leg_investment_token);

        const count2 = new countUp.CountUp("left_leg_count");
        const count3 = new countUp.CountUp("right_leg_count");

        count2.update(data.left_leg_count);
        count3.update(data.right_leg_count);
        
        } 

    })
    .catch(function(error) {
        console.error('Error fetching data:', error);
    });


    });


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
                    url: base_url + "transaction-list-profit",
                    type: "GET",
                    data: function(d) {
                        d.from_date = $('#cl_from_date').val(); 
                        d.to_date = $('#cl_to_date').val(); 
                        d.client_filter = agent_id; 
                        d.call_status = $('#call_status').val(); 
                        d.invest_id = invest_id;
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'UserInfo' },
                    { data : 'TransactionInfo'},
                    { data: 'CurrencyInfo' },
                    { data: 'Status' },
                ],
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
    loadData();


    function loadData(){

        var from_date = $('#cl_from_date').val(); 
        var to_date = $('#cl_to_date').val(); 
        var client_filter =agent_id; 
        var call_status = $('#call_status').val(); 

        $.ajax({
            url: base_url + 'list-profit-amount', 
            type: 'POST',
            data: {
                from_date: from_date,
                to_date: to_date,
                client_filter :client_filter,
                call_status :call_status,
                invest_id:invest_id
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