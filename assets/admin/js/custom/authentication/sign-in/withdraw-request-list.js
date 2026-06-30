$(document).ready(function () {

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
                    url: base_url + "withdraw-request-list",
                    type: "GET",
                    data: function (d) {
                        d.from_date = $('#cl_from_date').val();
                        d.to_date = $('#cl_to_date').val();
                        d.sponsor_id = $('#client_filter').val();
                    }
                },
                columns: [
                    { data: 'RecordID' },
                    { data: 'UserInfo' },
                    { data: 'Bank Details' },
                    { data: 'Amount' },
                    { data: 'Status' },
                    { data: 'Approved At' },
                    { data: 'Action' },
                ]
            });


            $(document).on("change", ".template_status", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked");
                var changestatusUrl = checkbox.data("template_status-url");

                // Revert checkbox state temporarily
                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to change the user status to this?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Change it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {

                        // Swal.fire({
                        //     icon: 'info',
                        //     title: 'Demo Version',
                        //     text: 'You Can not change status.',
                        //     confirmButtonText: 'Ok, got it!',
                        //     customClass: {
                        //         confirmButton: 'btn btn-primary'
                        //     },
                        //     buttonsStyling: false
                        // });

                        $.ajax({
                            url: changestatusUrl,
                            type: "POST",
                            data: { template_status: isChecked ? 1 : 0 },
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    checkbox.prop("checked", false);
                                    checkbox.prop("checked", isChecked);

                                    Swal.fire({
                                        text: "user status updated successfully!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
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
                                    text: "Failed to change the record!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!"
                                });
                            }
                        });

                    }
                });
            });

            $(document).on("click", ".delete_user", function (e) {
                e.preventDefault();

                var checkbox = $(this);
                var isChecked = checkbox.prop("checked");
                var changestatusUrl = checkbox.data("delete_user-url");

                checkbox.prop("checked", !isChecked);

                Swal.fire({
                    title: "Are you sure?",
                    text: "You want to delete this user?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Change it!",
                    cancelButtonText: "No, cancel!",
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {

                        //  Swal.fire({
                        //     icon: 'info',
                        //     title: 'Demo Version',
                        //     text: 'You Can not delete user.',
                        //     confirmButtonText: 'Ok, got it!',
                        //     customClass: {
                        //         confirmButton: 'btn btn-primary'
                        //     },
                        //     buttonsStyling: false
                        // });

                        $.ajax({
                            url: changestatusUrl,
                            type: "POST",
                            data: { template_status: isChecked ? 1 : 0 },
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    // Uncheck all other checkboxes except the current one
                                    checkbox.prop("checked", false);
                                    checkbox.prop("checked", isChecked); // Keep the selected one checked

                                    Swal.fire({
                                        text: response.message || "Something went wrong!",
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    });
                                    datatable.ajax.reload(null, false);
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
                                    text: "Failed to change the record!",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!"
                                });
                            }
                        });

                    }
                });
            });

        }

        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter').on('change', function () {
                datatable.ajax.reload(null, false);
                // loadData();
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

});