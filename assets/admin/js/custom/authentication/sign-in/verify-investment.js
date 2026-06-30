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
                    url: base_url + "verify-investment-list",
                    type: "GET",
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'UserInfo' },
                    { data: 'InvestInfo' },
                    { data: 'DateInfo' },
                    { data: 'EndDate' },
                    { data: 'temp_content' },
                ],
            });


            $('#kt-client-follow-table').on('click', '.view-summary', function() {
                var aiSummary = $(this).data('summary');

                $.ajax({
                    url: base_url + "view-announceemnt-section-cms/"+aiSummary,
                    type: 'GET',
                    success: function(data) {
                    $('#ai-summary-content').html(data);
                    },
                    error: function() {
                        console.log('error')
                    }
                });

            });
            
            
            $(document).on("click", ".btn-delete", function (e) {
                e.preventDefault();
                var deleteUrl = $(this).data("reject-url"); 
                var row = $(this).closest("tr");
            
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Reject it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: "POST",
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    Swal.fire({
                                        text: response.message,
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(() => {
                                        row.remove(); 
                                    });
                                } else {
                                    Swal.fire({
                                        text: response.message || "Something went wrong!",
                                        icon: "error",
                                        confirmButtonText: "Ok, got it!"
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    text: "Failed to delete the record!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!"
                                });
                            }
                        });
                    }
                });
            });

            $(document).on("click", ".btn-approve", function (e) {
                e.preventDefault();
                var deleteUrl = $(this).data("approve-url"); 
                var row = $(this).closest("tr");
            
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Approve it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: "POST",
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    Swal.fire({
                                        text: response.message,
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(() => {
                                       // row.remove(); 
                                    });
                                } else {
                                    Swal.fire({
                                        text: response.message || "Something went wrong!",
                                        icon: "error",
                                        confirmButtonText: "Ok, got it!"
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    text: "Failed to delete the record!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!"
                                });
                            }
                        });
                    }
                });
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

            }
        };
    }();

    KTDatatablesExample.init();
    

});