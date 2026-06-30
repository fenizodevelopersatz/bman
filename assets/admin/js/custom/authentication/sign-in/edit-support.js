

$(document).ready(function() {

    $('#uploadBtn').on('click', function() {
        $('#fileInput').click();
    });

    $('#fileInput').on('change', function() {
        if (this.files.length > 0) {
            console.log("File selected:", this.files[0].name);
        }
    });


    let previousStatus = $('#ticket_updated_status').val();

    $('#ticket_updated_status').on('focus', function () {
        previousStatus = $(this).val();
    });

    
    $('#ticket_updated_status').on('change', function () {
        let selectedStatus = $(this).val();
        
        Swal.fire({
            title: "Are you sure?",
            text: "You want to change the status of this ticket?",
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
                $.ajax({
                    url: base_url+'update-support-status/'+ticket_id,
                    type: "POST",
                    data: { ticket_updated_status: selectedStatus }, 
                    dataType: "json",
                    success: function (response) {
                        if (response.status) {
                            Swal.fire({
                                text: "Ticket status updated successfully!",
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            }).then(() => {
                                window.location.reload();
                            });;

                            previousStatus = selectedStatus;

                        } else {
                            Swal.fire("Error", response.message || "Something went wrong!", "error");
                            $('#ticket_updated_status').val(previousStatus).trigger('change.select2'); 
                        }
                    },
                    error: function () {
                          Swal.fire({
                                text:"Something went wrong!",
                                icon: "error",
                                confirmButtonText: "Ok, got it!"
                            });
                        $('#ticket_updated_status').val(previousStatus).trigger('change.select2'); 
                    }
                });

            } else {
                $('#ticket_updated_status').val(previousStatus).trigger('change.select2');
            }
        });
    });
    



    $('#uploadticketimage').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var fileInput = $('#fileInput')[0];
        var ticketImage = fileInput.files.length > 0 ? fileInput.files[0] : null;
        var message = $('textarea[name="ticket_message"]').val().trim();

        console.log("File input exists:", fileInput);
        console.log("Selected File:", ticketImage);
        console.log("Message:", message);

        if (!ticketImage && !message) {
            Swal.fire({
                text: "Please enter a message or upload an image.",
                icon: "error",
                confirmButtonText: "Ok, got it!"
            });
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    text: "Message and/or image uploaded successfully!",
                    icon: "success",
                    confirmButtonText: "Ok, got it!"
                }).then(function(e) {
                    if (e.isConfirmed) {
                        var redirectUrl = $('#uploadticketimage').attr("data-kt-redirect-url");
                        if (redirectUrl) {
                            location.href = redirectUrl;
                        }
                    }
                });
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    text: "Error: " + error,
                    icon: "error",
                    confirmButtonText: "Ok, got it!"
                });
            }
        });
    });
});
