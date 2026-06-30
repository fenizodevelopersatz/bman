"use strict";

$(document).ready(function(){

    axios.get(base_url+'agentReport/'+agent_id)
    .then(function(response) {
        const data = response.data.data;
        const data_result = response.data.result;

        if(data_result){

        $('#agent_name').html(data.name);
        $('#agent_roll').html(data.roll);
        $('#agent_location').html(data.location);
        $('#agent_mail').html(data.email);
        $('#agent_img').attr('src',data.profile)
        $('#call_status').addClass(data.call);
        $('#report_by').html(data.report_by);
        $('#join_date').html(data.join_date)

        $('#call_status').addClass(data.call);
        $('#report_by').html(data.report_by);
        $('#join_date').html(data.join_date);

        $('#recent_number').html(data.recent_number);
        $('#recent_call_date').html(data.recent_call_date);
        $('#recent_call_time').html(data.recent_call_time);


        $('#ini-loder').css('display','none');
        $('#profile_ini').fadeIn(500);

        const count1 = new countUp.CountUp("kt_countup_1");
        const count2 = new countUp.CountUp("kt_countup_2");
        const count3 = new countUp.CountUp("kt_countup_3");

        count1.update(data.total_contact);
        count2.update(data.today_call_count);
        count3.update(data.total_call_count);


        
        KTDatatablesExample.init();
        KTDatatablesCallRecord.init();
        
        } 

    })
    .catch(function(error) {
        console.error('Error fetching data:', error);
    });

    
    var ctx = document.getElementById('contact_chart').getContext('2d');
    var chart = null; 
    var currentChartType = 'bar'; 
    var chartData;
    var from_date = $('#from_date').val();
    var to_date = $('#to_date').val();
    var type  = 'daily';
    /*********** FILTER DATE  *******************/


    $('#from_date').on('change',function(){
        from_date = $(this).val();
        fetchData(type,from_date,to_date);
    });

    $('#to_date').on('change',function(){
        to_date = $(this).val();
        fetchData(type,from_date,to_date);
    });

    $('#weekly').on('click',function(e){
        e.preventDefault();
        type = 'weekly';
        fetchData(type,from_date,to_date);
    });

    $('#monthly').on('click',function(e){
        e.preventDefault();
        type = 'monthly';
        fetchData(type,from_date,to_date);
    });

    $('#daily').on('click',function(e){
        e.preventDefault();
        type = 'daily';
        fetchData(type,from_date,to_date);
    });

    $('#yearly').on('click',function(e){
        e.preventDefault();
        type = 'yearly';
        fetchData(type,from_date,to_date);
    });

    $('#toggleChartType').click(function(e) {
    e.preventDefault();
    currentChartType = currentChartType === 'line' ? 'bar' : 'line';
    updateChart(chartData); 
    });


       fetchData('daily');

        function fetchData(type, from_date, to_date) {

            $.ajax({
                    url: base_url+'agentChartReport',
                    type: 'POST',
                    data: { type: type, from_date: from_date, to_date: to_date , agent_id:agent_id},
                    dataType: 'json',
                    success: function(response) {
                        updateChart(response.datasets);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });
        }

    
    function updateChart(data) {
        if (chart) {
            chart.destroy();
        }

        var chartType = currentChartType === 'line' ? 'line' : 'bar';

        chart = new Chart(ctx, {
            type: chartType,
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label,
                    backgroundColor: chartType === 'bar' ? 'lightblue' : 'transparent',
                    borderColor: 'royalblue',
                    borderWidth: chartType === 'bar' ? 1 : 2,
                    data: data.values,
                }]
            },
            options: {
                responsive: true,
                layout: {
                    padding: 10,
                },
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: data.title,
                },
                scales: {
                    yAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: data.yAxisLabel,
                        }
                    }],
                    xAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: data.xAxisLabel,
                        }
                    }]
                }
            }
        });

        $('#chart-loader').css('display','none');
        $('#chart-loader-ini').fadeIn(500);
    }
    
    

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
                    url: base_url+"agentClientReport/"+agent_id,
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'Profile' },
                    { data: 'Status' },
                    { data: 'Rfollowdate' },
                    { data: 'Rcallduration' },
                    { data: 'Nextdate' },
                    { data: null },
                ],
                columnDefs: [
                    {
                        targets: -1,
                        data: null,
                        orderable: false,
                        className: 'text-end',
                        render: function (data, type, row) {
                            var encodedAgentId = encodeURIComponent(row.ClientId);
                            var action_url =  base_url+'ViewClient/'+encodedAgentId;
                            return `
                                <a href="`+action_url+`" class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle_s" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                                    View
                                    <span class="svg-icon fs-5 m-0">
                                    </span>
                                </a>
                            `;
                        },
                    },
                ],
                createdRow: function (row, data, dataIndex) {
                    $(row).find('td:eq(4)').attr('data-filter', data.CreditCardType);
                }
                });
                
                    $(document).on('click', '.dropdown-toggle_s', function (e) {
                        e.stopPropagation();
                        $(this).next('.dropdowns-menu').toggle();
                    });

                    $(document).click(function (e) {
                        var target = e.target;
                        if (!$(target).is('.dropdown-toggle_s') && !$(target).parents().is('.dropdowns-menu')) {
                            $('.dropdowns-menu').hide();
                        }
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

                    if ( !table ) {
                        return;
                    }

                    initDatatable();
                    exportButtons();
                    handleSearchDatatable();
                }
            };
        }();



        var KTDatatablesCallRecord = function () {
            var cl_table;
            var cl_datatable;
    
            var initDatatable = function () {
                const cl_tableRows = cl_table.querySelectorAll('tbody tr');
                cl_datatable = $(cl_table).DataTable({
                    searchDelay: 500,
                processing: true,
                serverSide: true,
                order: [[5, 'desc']],
                stateSave: true,
                ajax: {
                    url: base_url+"ClientCallReportAll/"+agent_id,
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'Profile' },
                    { data: 'CallTime' },
                    { data: 'CallReport' },
                    { data: 'CallAudio' },
                ],
                });
                
            }
    
        var exportButtons = () => {
            const documentTitle = 'Customer Follow - up  Report';
            var buttons = new $.fn.cl_datatable.Buttons(cl_table, {
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
            }).container().appendTo($('#kt_datatable_client_buttons'));
    
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
                cl_datatable.search(e.target.value).draw();
            });
            }
    
    
            return {
                init: function () {
                    cl_table = document.querySelector('#kt-calrecord-follow-table');
    
                    if ( !cl_table ) {
                        return;
                    }
    
                    initDatatable();
                    exportButtons();
                    handleSearchDatatable();
                }
            };
        }();
    


        
var kt_chartjs_1Chart = function() {
	var e = {
		self: null,
		rendered: false
	};

	
    var updateChart_center = function(data) {
        var t = document.getElementById("kt_chartjs_1");
        if (t) {
            var a = parseInt(KTUtil.css(t, "height"));
            var l = KTUtil.getCssVariableValue("--bs-gray-500");  
            var r = KTUtil.getCssVariableValue("--bs-border-dashed-color"); 
            var o = KTUtil.getCssVariableValue("--bs-primary"); 
            var s = KTUtil.getCssVariableValue("--bs-success"); 
            var w = KTUtil.getCssVariableValue("--bs-warning"); 
            var dangerColor = KTUtil.getCssVariableValue("--bs-danger");  


            
            const count1 = new countUp.CountUp("tcc");
            const count2 = new countUp.CountUp("icc");
            const count3 = new countUp.CountUp("fcc");
            const count4 = new countUp.CountUp("ncc");
            

            count1.update(data.inbound_calls_count);
            count2.update(data.interested_calls_count);
            count3.update(data.follow_up_calls_count);
            count4.update(data.not_interested_calls_count);

            $('#tcd').text(data.tot_duration);
            $('#icd').text(data.interested_calls_duration);
            $('#fcd').text(data.follow_up_calls_duration);

          
            var n = {
                series: [{
                    name: "Total Call Count",
                    data: data.inbound_calls.map(Number) 
                }, {
                    name: "Interested Call Count",
                    data: data.interested_calls.map(Number) 
                }, {
                    name: "Follow Up Call Count",
                    data: data.follow_up_calls.map(Number)
                }, {
                    name: "Not Interested Call Count",
                    data: data.not_interested_calls.map(Number) 
                }],
                chart: {
                    fontFamily: "inherit",
                    type: "bar",
                    height: a,
                    toolbar: {
                        show: false
                    }
                },
                title: {
                    text: "Overall Call Report", 
                    align: "center",
                    style: {
                        fontSize: "16px",
                        fontWeight: "bold",
                        color: l
                    }
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'center',
                    labels: {
                        colors: [o, s, w, dangerColor], 
                        useSeriesColors: true
                    },
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 12,
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: "45%",
                        endingShape: "rounded"
                    }
                },
                dataLabels: {
                    enabled: false
                },
                fill: {
                    opacity: 1
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: [o, s, w, dangerColor] 
                },
                xaxis: {
                    categories: data.duration,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        rotate: 0,
                        style: {
                            colors: l,
                            fontSize: "12px"
                        }
                    }
                },
                yaxis: {
                    max: Math.max(
                        ...data.inbound_calls.map(Number),
                        ...data.interested_calls.map(Number),
                        ...data.follow_up_calls.map(Number),
                        ...data.not_interested_calls.map(Number)
                    ) + 4,
                    labels: {
                        style: {
                            colors: l,
                            fontSize: "12px"
                        }
                    }
                },
                grid: {
                    borderColor: r,
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                colors: [o, s, w, dangerColor], 
                tooltip: {
                    style: {
                        fontSize: "12px"
                    }
                }
            };
    
            if (e.self) {
                e.self.destroy();
            }
            e.self = new ApexCharts(t, n);
            e.self.render();
            e.rendered = true;
        }
    };
    

	var loadData_center = function(mode_d, from_date_d, to_date_d) {

		$.ajax({
			url: base_url + 'ClientCallChartReport', 
			type: 'POST',
			data: {
				type: mode_d,
				from_date: from_date_d,
				to_date: to_date_d,
                agent_id:agent_id
			},
			success: function(response) {
				var data = JSON.parse(response);
				updateChart_center(data); 
			},
			error: function(xhr, status, error) {
				console.error('Error fetching chart data:', error);
			}
		});

	};

	return {

		init: function() {

			var from_date_d = $('#from_date_d').val();
			var to_date_d = $('#to_date_d').val();
			var mode_d = 'daily';

			loadData_center(mode_d, from_date_d, to_date_d); 

			KTThemeMode.on("kt.thememode.change", function() {
				if (e.rendered) {
					e.self.destroy();
					loadData_center(mode_d, from_date_d, to_date_d);
				}
			});

			$('#from_date_d').on('change', function() {
				from_date_d = $(this).val();
				loadData_center(mode_d, from_date_d, to_date_d);
			});

			$('#to_date_d').on('change', function() {
				to_date_d = $(this).val();
				loadData_center(mode_d, from_date_d, to_date_d);
			});

			$('#weekly_d').on('click', function(e) {
                e.preventDefault();
				mode_d = 'weekly';
				loadData_center(mode_d, from_date_d, to_date_d);
			});

			$('#monthly_d').on('click', function(e) {
                e.preventDefault();
				mode_d = 'monthly';
				loadData_center(mode_d, from_date_d, to_date_d);
			});

			$('#daily_d').on('click', function(e) {
                e.preventDefault();
				mode_d = 'daily';
				loadData_center(mode_d, from_date_d, to_date_d);
			});

			$('#yearly_d').on('click', function(e) {
                e.preventDefault();
				mode_d = 'yearly';
				loadData_center(mode_d, from_date_d, to_date_d);
			});
		}
	};
}();

kt_chartjs_1Chart.init();


});