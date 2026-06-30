$(document).ready(function(){
    
    axios.get(base_url+'dashboardReport')
    .then(function(response) {
    const data = response.data.data;
    const data_result = response.data.result;
    if(data_result){
    $('#agent_group_profile').html(data.agent_profile);
    $('#ini-agent').css('display','none');
    $('#agent_ini').fadeIn(500);
    const count1 = new countUp.CountUp("agents_count");
    const count2 = new countUp.CountUp("active_call");
    const count3 = new countUp.CountUp("pending_call");
    const count4 = new countUp.CountUp("today_call_count");
    const count5 = new countUp.CountUp("total_lead");
    const count6 = new countUp.CountUp("today_call_count_1");
    const count7 = new countUp.CountUp("online_agent");
    const count8 = new countUp.CountUp("offline_agent");
    count1.update(data.agent_count);
    count2.update(data.active_call);
    count3.update(data.pending_call);
    count4.update(data.today_call_count);
    count5.update(data.total_lead);
    count6.update(data.agent_count);
    count7.update(data.agent_login);
    count8.update(data.offline_agent);

    
    "undefined" != typeof module && (module.exports = KTChartsWidget36), KTUtil.onDOMContentLoaded((function() {
    KTChartsWidget36.init()
    }));
    KTDatatablesExample.init();
    }
    })
    .catch(function(error) {
        console.error('Error fetching data:', error);
    });

    var KTChartsWidget36 = function() {
        var e = {
            self: null,
            rendered: false
        };
    
        var updateChart = function(data) {
            var t = document.getElementById("kt_charts_widget_36");
            if (t) {
                var a = parseInt(KTUtil.css(t, "height"));
                var l = KTUtil.getCssVariableValue("--bs-gray-500");
                var r = KTUtil.getCssVariableValue("--bs-border-dashed-color");
                var o = KTUtil.getCssVariableValue("--bs-primary");
                var i = KTUtil.getCssVariableValue("--bs-primary");
                var s = KTUtil.getCssVariableValue("--bs-success");
                var w = KTUtil.getCssVariableValue("--bs-warning"); 
        
                
                var n = {
                    series: [{
                        name: "Contact Count",
                        data: data.outbound_calls
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
                        text: "Agent Contact Summary", 
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
                            colors: [o, s, w], 
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
                        colors: [o, s, w]  
                    },
                    xaxis: {
                        categories: data.categories,
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        tickAmount: 6,
                        labels: {
                            rotate: 0,
                            rotateAlways: true,
                            style: {
                                colors: l,
                                fontSize: "12px"
                            }
                        },
                        crosshairs: {
                            position: "front",
                            stroke: {
                                color: [o, s, w],  
                                width: 1,
                                dashArray: 3
                            }
                        },
                        tooltip: {
                            enabled: true,
                            style: {
                                fontSize: "12px"
                            }
                        }
                    },
                    yaxis: {
                        max: Math.max(...data.inbound_calls.concat(data.outbound_calls).concat(data.interested_clients)) + 4, 
                        labels: {
                            style: {
                                colors: l,
                                fontSize: "12px"
                            }
                        }
                    },
                    states: {
                        normal: {
                            filter: {
                                type: "none",
                                value: 0
                            }
                        },
                        hover: {
                            filter: {
                                type: "none",
                                value: 0
                            }
                        },
                        active: {
                            allowMultipleDataPointsSelection: false,
                            filter: {
                                type: "none",
                                value: 0
                            }
                        }
                    },
                    tooltip: {
                        style: {
                            fontSize: "12px"
                        }
                    },
                    colors: [o, s, w], 
                    grid: {
                        borderColor: r,
                        strokeDashArray: 4,
                        yaxis: {
                            lines: {
                                show: true
                            }
                        }
                    },
                    markers: {
                        strokeColor: [o, s, w], 
                        strokeWidth: 3
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
    
        var loadData = function(mode, from_date, to_date) {
            $.ajax({
                url: base_url + 'dashboardChart', 
                type: 'POST',
                data: {
                    type: mode,
                    from_date: from_date,
                    to_date: to_date
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    updateChart(data);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching chart data:', error);
                }
            });
        };
    
        return {
            init: function() {
                var from_date = $('#from_date').val();
                var to_date = $('#to_date').val();
                var mode = 'daily';
    
                loadData(mode, from_date, to_date);
    
                KTThemeMode.on("kt.thememode.change", function() {
                    if (e.rendered) {
                        e.self.destroy();
                        loadData(mode, from_date, to_date);
                    }
                });
    
                $('#from_date').on('change', function() {
                    from_date = $(this).val();
                    loadData(mode, from_date, to_date);
                });
    
                $('#to_date').on('change', function() {
                    to_date = $(this).val();
                    loadData(mode, from_date, to_date);
                });
    
                $('#weekly').on('click', function(e) {
                    e.preventDefault();
                    mode = 'weekly';
                    loadData(mode, from_date, to_date);
                });
    
                $('#monthly').on('click', function(e) {
                    e.preventDefault();
                    mode = 'monthly';
                    loadData(mode, from_date, to_date);
                });
    
                $('#daily').on('click', function(e) {
                    e.preventDefault();
                    mode = 'daily';
                    loadData(mode, from_date, to_date);
                });
    
                $('#yearly').on('click', function(e) {
                    e.preventDefault();
                    mode = 'yearly';
                    loadData(mode, from_date, to_date);
                });
            }
        };
    }();
    
   
    KTChartsWidget36.init();
 
    
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
                    url: base_url + "ClientAllCallReport",
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
                    { data: 'Profile' },
                    { data: 'CProfile' },
                    { data: 'CallTime' },
                    { data: 'CallReport' },
                    { data: 'CallAudio' },
                ]
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

    
    var icc_list = new countUp.CountUp("icd_count");
    var fcc_list = new countUp.CountUp("fcd_count");
    var tcc_list = new countUp.CountUp("tcd_count");


    $('#cl_from_date, #cl_to_date, #client_filter, #agent_filter, #call_status').on('change', function () {
        
        fcc_list.update(0);
        icc_list.update(0);
        tcc_list.update(0);
        
        var datas = '00:00:00';
        $("#fcc_list").text(datas);
        $("#icc_list").text(datas);
        $("#tcc_list").text(datas);

        initDatatableCall();
    });
    
     initDatatableCall();


     function initDatatableCall() {

        $.ajax({
            url: base_url + "administrator/getDUration",
            type: "GET",
            data: {
                from_date: $('#cl_from_date').val(), 
                to_date: $('#cl_to_date').val(), 
                client_filter: $('#client_filter').val(), 
                agent_filter: $('#agent_filter').val(), 
                call_status: $('#call_status').val()
            },
            success: function(response) {
                const data = JSON.parse(response);

                $("#fcc_list").text(data.follow_up_duration_seconds);
                $("#icc_list").text(data.interested_duration_seconds);
                $("#tcc_list").text(data.total_call_duration_seconds);

                fcc_list.update(data.follow_up_call);
                icc_list.update(data.interest_call);
                tcc_list.update(data.total_up_call);
            }
        });

    }

  
    var KTGeneralFullCalendarSelectDemos = function () {

        var exampleSelect = function () {
            var calendarEl = document.getElementById('kt_docs_fullcalendar_selectable');
    
            var calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                initialDate: new Date(),
                navLinks: true,
                selectable: true,
                selectMirror: true,
                editable: true,
                dayMaxEvents: true,
    
                events: function(fetchInfo, successCallback, failureCallback) {
                    $.ajax({
                        url: base_url + "fetchEvents",
                        type: 'GET',
                        success: function(data) {
                            var events = JSON.parse(data);
    
                            var today = new Date();
                            today.setHours(0, 0, 0, 0);
    
                            var tomorrow = new Date();
                            tomorrow.setDate(today.getDate() + 1);
                            tomorrow.setHours(0, 0, 0, 0);
    
                            events.forEach(function(event) {
                                var eventDate = new Date(event.start);
                                eventDate.setHours(0, 0, 0, 0);

                                        if (eventDate.getTime() == today.getTime()) {
                                        event.backgroundColor = 'red'; 
                                        event.borderColor = 'red';
                                        } else if (eventDate.getTime() == tomorrow.getTime()) {
                                        event.backgroundColor = 'orange';  
                                        event.borderColor = 'orange';
                                        } else {
                                        event.backgroundColor = 'green'; 
                                        event.borderColor = 'green';
                                        }
    
                            });
    
                            successCallback(events);
                        },
                        error: function() {
                            failureCallback();
                        }
                    });
                },
    
                eventClick: function(info) {
                    // Popup showing event details
                    Swal.fire({
                        title: info.event.title,
                        html: `<div><strong>Client Name:</strong> ${info.event.extendedProps.client_name}</div>
                               <div><strong>Client Number:</strong> ${info.event.extendedProps.client_number}</div>
                               <div><strong>Agent Name:</strong> ${info.event.extendedProps.agent_name}</div>
                               <div><strong>Description:</strong> ${info.event.extendedProps.description}</div>`,
                        icon: 'info',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                },
    
                eventContent: function(arg) {
                    let titleArr = arg.event.title.split('\n'); 
                    let titleHtml = titleArr.map(line => {
                        return `<div>${line}</div>`;
                    }).join('');
                    return { html: titleHtml };
                },
    
            });
    
            calendar.render();
        }
    
        return {
            init: function () {
                exampleSelect();
            }
        };
    }();
    
    KTUtil.onDOMContentLoaded(function () {
        KTGeneralFullCalendarSelectDemos.init();
    });


    

function checkReload() {
	axios.get(base_url + 'CheckReload')
		.then(function (response) {
			const data_result = response.data;
			if (data_result) {
				console.log("Reloading data...");
				reloadDashboard();
			}
		})
		.catch(function (error) {
			console.error('Error fetching CheckReload data:', error);
		});

}
setInterval(checkReload, 1000); 


function reloadDashboard() {



	axios.get(base_url + 'dashboardReport')
		.then(function (response) {
			const data = response.data.data;
			const data_result = response.data.result;
			if (data_result) {
				$('#agent_group_profile').html(data.agent_profile);
				$('#ini-agent').css('display', 'none');
				$('#agent_ini').fadeIn(500);

				const count1 = new countUp.CountUp("agents_count");
				const count2 = new countUp.CountUp("active_call");
				const count3 = new countUp.CountUp("pending_call");
				const count4 = new countUp.CountUp("today_call_count");
				const count5 = new countUp.CountUp("total_lead");
				const count6 = new countUp.CountUp("today_call_count_1");
				const count7 = new countUp.CountUp("online_agent");
				const count8 = new countUp.CountUp("offline_agent");

				count1.update(data.agent_count);
				count2.update(data.active_call);
				count3.update(data.pending_call);
				count4.update(data.today_call_count);
				count5.update(data.total_lead);
				count6.update(data.agent_count);
				count7.update(data.agent_login);
				count8.update(data.offline_agent);

				kt_chartjs_1Chart.init();
				KTChartsWidget36.init();
				KTDatatablesExample.init();
          
                
			}
		})
		.catch(function (error) {
			console.error('Error fetching dashboardReport data:', error);
		});
}



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
			url: base_url + 'getCallCenterData', 
			type: 'POST',
			data: {
				type: mode,
				from_date: from_date,
				to_date: to_date
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

});