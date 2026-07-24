$(document).ready(function(){
    var memberFilter = $("#client_filter");
    var defaultAvatar = base_url + "assets/default-user.png";

    function memberResultTemplate(member) {
        if (member.loading) return member.text;

        var wrapper = document.createElement("span");
        wrapper.className = "d-flex align-items-center gap-3";

        var avatar = document.createElement("img");
        avatar.src = member.avatar || defaultAvatar;
        avatar.alt = "";
        avatar.className = "rounded-circle flex-shrink-0";
        avatar.style.width = "36px";
        avatar.style.height = "36px";
        avatar.style.objectFit = "cover";
        avatar.onerror = function () {
            this.onerror = null;
            this.src = defaultAvatar;
        };

        var details = document.createElement("span");
        details.className = "d-flex flex-column";

        var name = document.createElement("strong");
        name.textContent = member.name || member.text || "";

        var meta = document.createElement("small");
        meta.className = "text-muted";
        meta.textContent = [member.email, member.referral_id].filter(Boolean).join(" · ");

        details.appendChild(name);
        details.appendChild(meta);
        wrapper.appendChild(avatar);
        wrapper.appendChild(details);
        return $(wrapper);
    }

    function memberSelectionTemplate(member) {
        if (!member.id) return member.text;

        var wrapper = document.createElement("span");
        wrapper.className = "d-inline-flex align-items-center gap-2";

        var avatar = document.createElement("img");
        avatar.src = member.avatar || defaultAvatar;
        avatar.alt = "";
        avatar.className = "rounded-circle";
        avatar.style.width = "24px";
        avatar.style.height = "24px";
        avatar.style.objectFit = "cover";
        avatar.onerror = function () {
            this.onerror = null;
            this.src = defaultAvatar;
        };

        var label = document.createElement("span");
        label.textContent = member.text || "";
        wrapper.appendChild(avatar);
        wrapper.appendChild(label);
        return $(wrapper);
    }

    memberFilter.select2({
        ajax: {
            url: memberFilter.data("search-url"),
            dataType: "json",
            delay: 250,
            data: function (params) {
                return { q: params.term || "", page: params.page || 1 };
            },
            processResults: function (data) {
                return {
                    results: data.results || [],
                    pagination: data.pagination || { more: false }
                };
            },
            cache: true
        },
        placeholder: memberFilter.data("placeholder"),
        allowClear: true,
        closeOnSelect: false,
        minimumInputLength: 0,
        templateResult: memberResultTemplate,
        templateSelection: memberSelectionTemplate,
        escapeMarkup: function (markup) { return markup; },
        width: "100%"
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
                url: base_url + "network-list",
                type: "GET",
                data: function(d) {
                    d.from_date = $('#cl_from_date').val(); 
                    d.to_date = $('#cl_to_date').val(); 
                    d.client_filter = $('#client_filter').val(); 
                }
               },
                columns: [
                    { data: 'RecordID' },
                    { data: 'SponserInfo' },
                    { data: 'UserInfo' },
                    { data : 'BinaryInfo'},
                    { data: 'DateInfo' },
                    { data: 'Status' },
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

                           Swal.fire({
                            icon: 'info',
                            title: 'Demo Version',
                            text: 'You Can not change status.',
                            confirmButtonText: 'Ok, got it!',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                                buttonsStyling: false
                            });
                            
                        // $.ajax({
                        //     url: changestatusUrl,
                        //     type: "POST",
                        //     data: { template_status: isChecked ? 1 : 0 },
                        //     dataType: "json",
                        //     success: function (response) {
                        //         if (response.status === "success") {
                        //             checkbox.prop("checked", false);
                        //             checkbox.prop("checked", isChecked);
            
                        //             Swal.fire({
                        //                 text: "user status updated successfully!",
                        //                 icon: "success",
                        //                 buttonsStyling: false,
                        //                 confirmButtonText: "Ok, got it!",
                        //                 customClass: {
                        //                     confirmButton: "btn btn-primary"
                        //                 }
                        //             });
                                    
                        //         } else {
                        //             Swal.fire({
                        //                 text: response.message || "Something went wrong!",
                        //                 icon: "error",
                        //                 confirmButtonText: "Ok, got it!"
                        //             });
                        //         }
                        //     },
                        //     error: function () {
                        //         Swal.fire({
                        //             text: "Failed to change the record!",
                        //             icon: "error",
                        //             confirmButtonText: "Ok, got it!"
                        //         });
                        //     }
                        // });

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

                         Swal.fire({
                            icon: 'info',
                            title: 'Demo Version',
                            text: 'You Can not delete user.',
                            confirmButtonText: 'Ok, got it!',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });

                        // $.ajax({
                        //     url: changestatusUrl,
                        //     type: "POST",
                        //     data: { template_status: isChecked ? 1 : 0 },
                        //     dataType: "json",
                        //     success: function (response) {
                        //         if (response.status === "success") {
                        //             // Uncheck all other checkboxes except the current one
                        //             checkbox.prop("checked", false);
                        //             checkbox.prop("checked", isChecked); // Keep the selected one checked
            
                        //             Swal.fire({
                        //                 text: response.message || "Something went wrong!",
                        //                 icon: "success",
                        //                 buttonsStyling: false,
                        //                 confirmButtonText: "Ok, got it!",
                        //                 customClass: {
                        //                     confirmButton: "btn btn-primary"
                        //                 }
                        //             });
                        //         } else {
                        //             Swal.fire({
                        //                 text: response.message || "Something went wrong!",
                        //                 icon: "error",
                        //                 confirmButtonText: "Ok, got it!"
                        //             });
                        //         }
                        //     },
                        //     error: function () {
                        //         Swal.fire({
                        //             text: "Failed to change the record!",
                        //             icon: "error",
                        //             confirmButtonText: "Ok, got it!"
                        //         });
                        //     }
                        // });

                    }
                });
            });

        }
    
        var handleFilterChange = function () {
            $('#cl_from_date, #cl_to_date, #client_filter').on('change', function () {
                datatable.ajax.reload(null, false);
            });
        }

        var handleTableRefresh = function () {
            $('#network_table_refresh').on('click', function () {
                var button = this;
                button.disabled = true;
                button.setAttribute('data-kt-indicator', 'on');

                // Refresh means "show all": clear every external filter and any
                // DataTables state that could keep an old empty result applied.
                $('#cl_from_date, #cl_to_date').val('');
                memberFilter.val(null).trigger('change.select2');

                var searchInput = document.querySelector('[data-kt-docs-table-filter="search"]');
                if (searchInput) {
                    searchInput.value = '';
                }

                datatable.search('');
                datatable.state.clear();
                datatable.page('first');
                datatable.ajax.reload(function () {
                    button.removeAttribute('data-kt-indicator');
                    button.disabled = false;
                }, true);
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
                handleTableRefresh();
            }
        };
    }();

    KTDatatablesExample.init();

});
