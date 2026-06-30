$(document).ready(function(){
    var KTDatatablesExample = function () {
        var table;
        var datatable;

        var initDatatable = function () {
            table = document.querySelector('#kt-client-follow-table');
            if (!table) return;

            datatable = $(table).DataTable({
                searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url + "transaction-list",
                    type: "GET",
                    data: function(d) {
                        d.from_date = $('#cl_from_date').val(); 
                        d.to_date = $('#cl_to_date').val(); 
                        d.client_filter = $('#client_filter').val(); 
                        d.call_status = $('#call_status').val(); 
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'UserInfo' },
                    { data : 'TransactionInfo'},
                    { data: 'CurrencyInfo' },
                    { data: 'Status' },
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



$(document).ready(function(){

    axios.get(base_url+'balance-info-admin')
    .then(function(response) {
        const data = response.data.data;
        const data_result = response.data.result;


      

        if(data_result){

        const options = {
        decimalPlaces: 2, 
        separator: ',',   
        decimal: '.',   
        };

       

        const count2 = new countUp.CountUp("bsc_lending_token", 0, options);
        const count3 = new countUp.CountUp("bsc_lending_currency", 0, options);
        const count4 = new countUp.CountUp("wallet_lending_token", 0, options);
        const count5 = new countUp.CountUp("wallet_lending_currency", 0, options);
        const count6 = new countUp.CountUp("admin_lending_token", 0, options);
        const count7 = new countUp.CountUp("admin_lending_currency", 0, options);
        const count8 = new countUp.CountUp("lending_token", 0, options);
        const count9 = new countUp.CountUp("lending_currency", 0, options);
        const count10 = new countUp.CountUp("active_lending");
        const count11 = new countUp.CountUp("inactive_lending");
        const count12 = new countUp.CountUp("reinvest_lending");
        
        const count13 = new countUp.CountUp("level_2_token", 0, options);
        const count14 = new countUp.CountUp("level_2_currency", 0, options);

        const count15 = new countUp.CountUp("level_3_token", 0, options);
        const count16 = new countUp.CountUp("level_3_currency", 0, options);

        const count17 = new countUp.CountUp("level_4_token", 0, options);
        const count18 = new countUp.CountUp("level_4_currency", 0, options);
        
        const count19 = new countUp.CountUp("level_5_token", 0, options);
        const count20 = new countUp.CountUp("level_5_currency", 0, options);

        const count21 = new countUp.CountUp("direct_currency", 0, options);
        const count22 = new countUp.CountUp("direct_token", 0, options);

        const count23 = new countUp.CountUp("commission_token", 0, options);
        const count24 = new countUp.CountUp("commission_currency", 0, options);
        const count25 = new countUp.CountUp("rank_token", 0, options);
        const count26 = new countUp.CountUp("rank_currency", 0, options);
        const count27 = new countUp.CountUp("rank_tokens", 0, options);
        const count28 = new countUp.CountUp("rank_currencys", 0, options);
        const count29 = new countUp.CountUp("profit_token", 0, options);
        const count30 = new countUp.CountUp("profit_currency", 0, options);
        const count33 = new countUp.CountUp("binary_token", 0, options);
        const count34 = new countUp.CountUp("binary_currency", 0, options);
        const count35 = new countUp.CountUp("daily_token", 0, options);
        const count36 = new countUp.CountUp("daily_currency", 0, options);

        const count37 = new countUp.CountUp("rank_achived");

        count2.update(data_result.bsc_lending_token);
        count3.update(data_result.bsc_lending_currency);
        count4.update(data_result.wallet_lending_token);
        count5.update(data_result.wallet_lending_currency);
        count6.update(data_result.admin_lending_token);
        count7.update(data_result.admin_lending_currency);
        count8.update(data_result.lending_token);
        count9.update(data_result.lending_currency);
        count10.update(data_result.active_lending);
        count11.update(data_result.inactive_lending);
        count12.update(data_result.reinvest_lending);

        count13.update(data_result.level_2_token);
        count14.update(data_result.level_2_currency);

        count15.update(data_result.level_3_token);
        count16.update(data_result.level_3_currency);
        count17.update(data_result.level_4_token);
        count18.update(data_result.level_4_currency);
        count19.update(data_result.level_5_token);
        count20.update(data_result.level_5_currency);

        count21.update(data_result.direct_currency);
        count22.update(data_result.direct_token);

        count23.update(data_result.commission_token);
        count24.update(data_result.commission_currency);
        count25.update(data_result.rank_token);
        count26.update(data_result.rank_currency);
        count27.update(data_result.rank_tokens);
        count28.update(data_result.rank_currencys);
        count29.update(data_result.profit_token);
        count30.update(data_result.profit_currency);
        count33.update(data_result.binary_token);
        count34.update(data_result.binary_currency);
        count35.update(data_result.daily_token);
        count36.update(data_result.daily_currency);
        count37.update(data_result.rank_achived);

        




        } 

    })
    .catch(function(error) {
        console.error('Error fetching data:', error);
    });



    var mlmCommissionChart = (function(){
    const ENDPOINT = base_url + 'getMlmCommissionData';
    let chart, mode = 'daily', from_date = '', to_date = '';

    function fetchData(){
        return $.ajax({
        url: ENDPOINT,
        type: 'POST',
        data: { type: mode, from_date, to_date }
        });
    }

    function render(resp){
    if (!resp || resp.status !== true) return;

    const root = document.getElementById('kt_chartjs_1');
    if (!root) return;

    // If an old Chart.js instance exists, destroy it
    if (chart) { chart.destroy(); chart = null; }

    // Ensure we have a <canvas> inside the DIV
    let canvas = root.tagName.toLowerCase() === 'canvas' ? root : root.querySelector('canvas');
    if (!canvas) {
        canvas = document.createElement('canvas');
        canvas.style.width  = '100%';
        canvas.style.height = '100%';
        root.innerHTML = '';
        root.appendChild(canvas);

        // make sure the wrapper has a fixed height so the canvas doesn't blow up
        root.style.position = 'relative';
        if (!root.style.height) root.style.height = '580px';
    }

    const L  = resp.data.labels || [];
    const ds = [
        { label:'Binary Commission (legacy)', data: resp.data.binary_legacy || [], borderWidth:0, backgroundColor:'rgba(99,102,241,0.85)' },
        { label:'Pair Commission',            data: resp.data.pair_commission || [], borderWidth:0, backgroundColor:'rgba(16,185,129,0.85)' },
        { label:'PV Points',                  data: resp.data.pv || [],             borderWidth:0, backgroundColor:'rgba(234,179,8,0.85)'  },
        { label:'BV Points',                  data: resp.data.bv || [],             borderWidth:0, backgroundColor:'rgba(239,68,68,0.85)'  }
    ];

    chart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: { labels: L, datasets: ds },
        options: {
        responsive: true,
        maintainAspectRatio: false, // use wrapper's height
        plugins: { legend: { position: 'bottom' }, tooltip: { mode: 'index', intersect: false } },
        scales: { x: { stacked:false }, y: { beginAtZero:true, ticks:{ callback:v=>Number(v).toLocaleString() } } }
        }
    });
    }


    function reload(){
        fetchData().done(function(r){ render(r); })
                .fail(function(){ /* optionally toast */ });
    }

    function bindUI(){
        $('#from_date_d').on('change', function(){
        from_date = $(this).val();
        reload();
        });
        $('#to_date_d').on('change', function(){
        to_date = $(this).val();
        reload();
        });
        $('#daily_d').on('click',   function(e){ e.preventDefault(); mode='daily';   reload(); });
        $('#weekly_d').on('click',  function(e){ e.preventDefault(); mode='weekly';  reload(); });
        $('#monthly_d').on('click', function(e){ e.preventDefault(); mode='monthly'; reload(); });
        $('#yearly_d').on('click',  function(e){ e.preventDefault(); mode='yearly';  reload(); });
    }

    return {
        init: function(){
        from_date = $('#from_date_d').val() || '';
        to_date   = $('#to_date_d').val()   || '';
        bindUI();
        reload();
        }
    };
    })();
    mlmCommissionChart.init();



    });