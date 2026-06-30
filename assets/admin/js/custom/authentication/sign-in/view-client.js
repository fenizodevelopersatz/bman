$(document).ready(function(){




    axios.get(base_url+'ClientViewReport/'+client_id)
    .then(function(response) {
        const data = response.data.data;
        const data_result = response.data.result;

        if(data_result){

        $('#client_name').html(data.name);
        $('#client_phone').html(data.phone);
        $('#client_location').html(data.location);
        $('#client_email').html(data.email);
        $('#agent_img').attr('src',data.profile)
        $('#call_status').addClass(data.call);
        $('#report_by').html(data.report_by);
        $('#join_date').html(data.join_date)
        $('#call_status').addClass(data.call);
        $('#report_by').html(data.report_by);
        $('#join_date').html(data.join_date);
        $('#agent_name').html(data.agent_name);
        $('#recent_call_date').html(data.recent_call_date);
        $('#recent_call_time').html(data.recent_call_time);
        $('#next_call_time').html(data.next_call_time);
        $('#report_by').html(data.assign_agent)

        $('#ini-loder').css('display','none');
        $('#profile_ini').fadeIn(500);

        const count1 = new countUp.CountUp("kt_countup_1");
        const count2 = new countUp.CountUp("kt_countup_2");
        count1.update(data.total_call_count);
        count2.update(data.today_call_count);

        KTDatatablesExample.init();

        } 

    })
    .catch(function(error) {
        console.error('Error fetching data:', error);
    });


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
                url: base_url+"ClientCallReport/"+client_number,
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
    

	var loadData_center = function(mode, from_date, to_date) {

		$.ajax({
			url: base_url + 'ClientChartReport', 
			type: 'POST',
			data: {
				type: mode,
				from_date: from_date,
				to_date: to_date,
                client_id:client_id
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

			var from_date = $('#from_date_d').val();
			var to_date = $('#to_date_d').val();
			var mode = 'daily';

			loadData_center(mode, from_date, to_date); 

			KTThemeMode.on("kt.thememode.change", function() {
				if (e.rendered) {
					e.self.destroy();
					loadData_center(mode, from_date, to_date);
				}
			});

			$('#from_date_d').on('change', function(e) {
				from_date = $(this).val();
				loadData_center(mode, from_date, to_date);
			});

			$('#to_date_d').on('change', function(e) {
				to_date = $(this).val();
				loadData_center(mode, from_date, to_date);
			});

			$('#weekly_d').on('click', function(e) {
                e.preventDefault();
				mode = 'weekly';
				loadData_center(mode, from_date, to_date);
			});

			$('#monthly_d').on('click', function(e) {
                e.preventDefault();
				mode = 'monthly';
				loadData_center(mode, from_date, to_date);
			});

			$('#daily_d').on('click', function(e) {
                e.preventDefault();
				mode = 'daily';
				loadData_center(mode, from_date, to_date);
			});

			$('#yearly_d').on('click', function(e) {
                e.preventDefault();
				mode = 'yearly';
				loadData_center(mode, from_date, to_date);
			});
		}
	};
}();

kt_chartjs_1Chart.init();


    
    // var ctx = document.getElementById('contact_chart').getContext('2d');
    // var chart = null; 
    // var currentChartType = 'bar'; 
    // var chartData;
    // var from_date = $('#from_date').val();
    // var to_date = $('#to_date').val();
    // var type  = 'daily';
    // /*********** FILTER DATE  *******************/


    // $('#from_date').on('change',function(){
    //     from_date = $(this).val();
    //     fetchData(type,from_date,to_date);
    // });

    // $('#to_date').on('change',function(){
    //     to_date = $(this).val();
    //     fetchData(type,from_date,to_date);
    // });

    // $('#weekly').on('click',function(){
    //     type = 'weekly';
    //     fetchData(type,from_date,to_date);
    // });

    // $('#monthly').on('click',function(){
    //     type = 'monthly';
    //     fetchData(type,from_date,to_date);
    // });

    // $('#daily').on('click',function(){
    //     type = 'daily';
    //     fetchData(type,from_date,to_date);
    // });

    // $('#yearly').on('click',function(){
    //     type = 'yearly';
    //     fetchData(type,from_date,to_date);
    // });

    // $('#toggleChartType').click(function() {
    // currentChartType = currentChartType === 'line' ? 'bar' : 'line';
    // updateChart(chartData); 
    // });


    //    fetchData('daily');

    //     function fetchData(type, from_date, to_date) {

    //         $.ajax({
    //                 url: base_url+'ClientChartReport',
    //                 type: 'POST',
    //                 data: { type: type, from_date: from_date, to_date: to_date , client_id:client_id},
    //                 dataType: 'json',
    //                 success: function(response) {
    //                     updateChart(response.datasets);
    //                 },
    //                 error: function(xhr, status, error) {
    //                     console.error('Error fetching data:', error);
    //                 }
    //             });
    //     }

    
    // function updateChart(data) {
    //     if (chart) {
    //         chart.destroy();
    //     }

    //     var chartType = currentChartType === 'line' ? 'line' : 'bar';

    //     chart = new Chart(ctx, {
    //         type: chartType,
    //         data: {
    //             labels: data.labels,
    //             datasets: [{
    //                 label: data.label,
    //                 backgroundColor: chartType === 'bar' ? 'lightblue' : 'transparent',
    //                 borderColor: 'royalblue',
    //                 borderWidth: chartType === 'bar' ? 1 : 2,
    //                 data: data.values,
    //             }]
    //         },
    //         options: {
    //             responsive: true,
    //             layout: {
    //                 padding: 10,
    //             },
    //             legend: {
    //                 position: 'bottom',
    //             },
    //             title: {
    //                 display: true,
    //                 text: data.title,
    //             },
    //             scales: {
    //                 yAxes: [{
    //                     scaleLabel: {
    //                         display: true,
    //                         labelString: data.yAxisLabel,
    //                     }
    //                 }],
    //                 xAxes: [{
    //                     scaleLabel: {
    //                         display: true,
    //                         labelString: data.xAxisLabel,
    //                     }
    //                 }]
    //             }
    //         }
    //     });

    //     $('#chart-loader').css('display','none');
    //     $('#chart-loader-ini').fadeIn(500);

    // }
    

});