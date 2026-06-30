$(document).ready(function() {


    // Initially hide the 2FA section
    $('[data-kt-element="apps"]').hide();

    // Enable 2FA button click
    $('[data-bs-target="#kt_modal_two_factor_authentication"]').on('click', function(e) {
        e.preventDefault();

        // Slide down the static 2FA content
        $('[data-kt-element="apps"]').slideDown();

        // Optional: Scroll to it
        $('html, body').animate({
            scrollTop: $('[data-kt-element="apps"]').offset().top - 100
        }, 500);
    });

    // Handle the form submit with demo alert
    $('[data-kt-element="apps-form"]').on('submit', function(e) {
        e.preventDefault();

        // Show alert for demo
        Swal.fire({
            icon: 'info',
            title: 'Demo Version',
            text: 'This is a demo, no real 2FA action performed.',
            confirmButtonText: 'Ok, got it!',
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });
    });

    // Cancel button hides the 2FA content
    $('[data-kt-element="apps-cancel"]').on('click', function(e) {
        e.preventDefault();
        $('[data-kt-element="apps"]').slideUp();
    });
    
    
    // Email edit
    $('#kt_signin_email_button button').on('click', function() {
        $('#kt_signin_email').hide();
        $('#kt_signin_email_edit').removeClass('d-none');
        $('#kt_signin_email_button').hide();
    });

    $('#kt_signin_cancel').on('click', function() {
        $('#kt_signin_email_edit').addClass('d-none');
        $('#kt_signin_email').show();
        $('#kt_signin_email_button').show();
    });

    // Password edit
    $('#kt_signin_password_button button').on('click', function() {
        $('#kt_signin_password').hide();
        $('#kt_signin_password_edit').removeClass('d-none');
        $('#kt_signin_password_button').hide();
    });

    $('#kt_password_cancel').on('click', function() {
        $('#kt_signin_password_edit').addClass('d-none');
        $('#kt_signin_password').show();
        $('#kt_signin_password_button').show();
    });
});


(function(){
  const csrfName = document.querySelector('meta[name="csrf-name"]').content;
  let csrfHash   = document.querySelector('meta[name="csrf-hash"]').content;
  const setCsrf  = (h)=>{ csrfHash=h; };

  const emailForm   = document.querySelector('#kt_signin_change_email');
  const emailInput  = document.querySelector('#emailaddress');
  const passConfirm = document.querySelector('#confirmemailpassword');
  const otpInput    = document.querySelector('#emailotp');
  const actionsBox  = document.getElementById('otp_actions'); 
  const otpBlock    = document.getElementById('otp_block');
  const sendBtn     = document.getElementById('send_otp_btn');
  const confirmBtn  = document.getElementById('confirm_otp_btn');
  const resendBtn   = document.getElementById('resend_otp_btn');
  const okBadge     = document.getElementById('otp_ok_badge');
  const refEl       = document.getElementById('otp_ref');
  const verifiedEl  = document.getElementById('otp_verified');
  const submitBtn   = document.getElementById('kt_signin_submit');

    function enableUpdate(on){ submitBtn.disabled = !on; }
  // validation (keep yours)
  const emailValidation = FormValidation.formValidation(emailForm, {
    fields: {
      emailaddress: { validators: { notEmpty:{message:'Email is required'}, emailAddress:{message:'Enter a valid email'} } },
      confirmemailpassword: { validators: { notEmpty:{message:'Password is required'} } },
      emailotp: { validators: { regexp:{regexp:/^\d{6}$/, message:'Enter 6-digit OTP'} } }
    },
    plugins: { trigger:new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({rowSelector:'.fv-row'}) }
  });

  function lockEmailFields(lock=true){
    emailInput.readOnly = lock; passConfirm.readOnly = lock;
  }
  function enableUpdateBtn(on){
    submitBtn.disabled = !on;
  }
  let timerId=null;
  function startCountdown(s=60){
    resendBtn.disabled=true;
    resendBtn.textContent=`Resend (${s}s)`;
    if(timerId) clearInterval(timerId);
    timerId = setInterval(()=>{
      s--; resendBtn.textContent=`Resend (${s}s)`;
      if(s<=0){ clearInterval(timerId); resendBtn.disabled=false; resendBtn.textContent='Resend'; }
    },1000);
  }

  // STEP 1: Send OTP
  // grab the correct elements once at the top
const otpInputCol = document.getElementById('otp_input_col'); // the left column with the input
const otpActions  = document.getElementById('otp_actions');   // the right box with Confirm/Resend
    // STEP 1: Send OTP
    sendBtn.addEventListener('click', async () => {
    const status = await emailValidation.validateField('emailaddress')
                        .then(() => emailValidation.validateField('confirmemailpassword'));
    if (status !== 'Valid') return;

    try {
        const params = new URLSearchParams({
        email: emailInput.value.trim(),
        password: passConfirm.value.trim()
        });
        params.append(csrfName, csrfHash);

        const res = await axios.post(base_url + 'user/send_email_otp', params);

        if (res.data.status === 'success') {
        refEl.value = res.data.ref;

        // ✅ use the new IDs
        otpInputCol.classList.remove('d-none');
        otpActions.classList.remove('d-none');
        sendBtn.classList.add('d-none');

        lockEmailFields(true);
        startCountdown(60);
        otpInput.focus();
        Swal.fire('OTP sent', 'Check your new email for the code.', 'success');
        } else {
        Swal.fire('Error', res.data.message || 'Failed to send OTP', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', e?.response?.data?.message || 'Could not send OTP', 'error');
    }
    });


  // STEP 2: Confirm OTP (verify only)
  confirmBtn?.addEventListener('click', async ()=>{
    if(!/^\d{6}$/.test(otpInput.value.trim())) return Swal.fire('Enter the 6-digit code','','warning');
    try{
      const params = new URLSearchParams({ email: emailInput.value.trim(), otp: otpInput.value.trim(), ref: refEl.value });
      params.append(csrfName, csrfHash);

      const res = await axios.post(base_url+'user/verify_email_otp', params);
      if(res.data.csrf) setCsrf(res.data.csrf);

      if(res.data.status==='success'){
        verifiedEl.value = '1';
        okBadge.classList.remove('d-none');
        enableUpdateBtn(true);
        Swal.fire('Verified','OTP confirmed. Click Update Email to finish.','success');
      }else{
        enableUpdateBtn(false);
        okBadge.classList.add('d-none');
        Swal.fire('Error', res.data.message || 'Wrong or expired OTP', 'error');
      }
    }catch(e){ Swal.fire('Error','Could not verify OTP','error'); }
  });

  // STEP 3: Resend
  resendBtn?.addEventListener('click', async ()=>{
    resendBtn.disabled=true;
    try{
      const params = new URLSearchParams({ email: emailInput.value.trim(), password: passConfirm.value.trim() });
      params.append(csrfName, csrfHash);
      const res = await axios.post(base_url+'user/send_email_otp', params);
      if(res.data.csrf) setCsrf(res.data.csrf);

      if(res.data.status==='success'){
        refEl.value = res.data.ref;
        otpInput.value='';
        okBadge.classList.add('d-none');
        verifiedEl.value='0';
        enableUpdateBtn(false);
        startCountdown(60);
      }else{
        Swal.fire('Error', res.data.message || 'Failed to resend OTP', 'error');
        resendBtn.disabled=false;
      }
    }catch(e){ Swal.fire('Error','Could not resend OTP','error'); resendBtn.disabled=false; }
  });

  // FINAL: Update Email
submitBtn.addEventListener('click', async (e)=>{
  e.preventDefault();
  if (verifiedEl.value !== '1') return Swal.fire('Verify OTP first','','info');
      submitBtn.setAttribute('data-kt-indicator','on'); submitBtn.disabled = true;
  try{
    const fd = new FormData(emailForm);
    fd.append(csrfName, csrfHash);
    const { data } = await axios.post(base_url + 'user/email_update', fd);
    if (data.csrf) csrfHash = data.csrf;
    if (data.status === 'success') {
      Swal.fire('Updated', data.message, 'success');
      document.querySelector('#kt_signin_email .fw-semibold').textContent = emailInput.value.trim();
      enableUpdate(false);
      okBadge.classList.add('d-none');
      verifiedEl.value = '0';
      emailForm.reset();
      otpInputCol.classList.add('d-none'); 
      actionsBox.classList.add('d-none');
      sendBtn.classList.remove('d-none');
      lockEmailFields(false);
    } else {
      Swal.fire('Error', data.message || 'Update failed', 'error');
    }
  } catch (err) {
    console.error('Update error:', err);
    Swal.fire('Error', err?.response?.data?.message || 'Server error', 'error');
  } finally {
    submitBtn.removeAttribute('data-kt-indicator');
    submitBtn.disabled = false;

    $('#kt_signin_email_edit').addClass('d-none');
    $('#kt_signin_email').show();
    $('#kt_signin_email_button').show();

  }
});


})();


var KTSigninPassword = function () {
    var form, submitBtn, validation;

    return {
        init: function () {
            form = document.querySelector("#kt_signin_change_password");
            submitBtn = document.querySelector("#kt_password_submit");

            validation = FormValidation.formValidation(form, {
                fields: {
                    currentpassword: {
                        validators: {
                            notEmpty: {
                                message: "Current password is required"
                            }
                        }
                    },
                    newpassword: {
                        validators: {
                            notEmpty: {
                                message: "New password is required"
                            },
                            stringLength: {
                                min: 8,
                                message: "Password must be at least 8 characters"
                            }
                        }
                    },
                    confirmpassword: {
                        validators: {
                            notEmpty: {
                                message: "Password confirmation is required"
                            },
                            identical: {
                                compare: function () {
                                    return form.querySelector('[name="newpassword"]').value;
                                },
                                message: "Passwords do not match"
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: ".fv-row"
                    })
                }
            });

            submitBtn.addEventListener("click", function (e) {
                e.preventDefault();

                validation.validate().then(function (status) {

                    if (status === 'Valid') {

                        submitBtn.setAttribute("data-kt-indicator", "on");
                        submitBtn.disabled = true;

                        //   Swal.fire({
                        //     icon: 'info',
                        //     title: 'Demo Version',
                        //     text: 'You Can not change record.',
                        //     confirmButtonText: 'Ok, got it!',
                        //     customClass: {
                        //         confirmButton: 'btn btn-primary'
                        //     },
                        //         buttonsStyling: false
                        //     });
    

                        axios.post(base_url+'user/password_update', new FormData(form)) 
                            .then(function (response) {
                                let res = response.data;

                                if (res.status === "success") {
                                    Swal.fire({
                                        text: res.message,
                                        icon: "success",
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        text: res.message,
                                        icon: "error",
                                        confirmButtonText: "Ok, got it!"
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    text: "Error occurred",
                                    icon: "error"
                                });
                            })
                            .finally(() => {
                                submitBtn.removeAttribute("data-kt-indicator");
                                submitBtn.disabled = false;
                            });

                    }
                });
            });
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTSigninPassword.init();
});


(function(){
  const $modal = $('#twofaModal');
  const $form  = $('#twofaForm');
  const $title = $('#twofaModalTitle');
  const $desc  = $('#twofaModalDesc');
  const $code  = $('#oneCode');
  const $status= $('#twofaStatus');
  const $submit= $('#twofaSubmit');

  // When any trigger opens the modal, set mode (enable/disable)
  $modal.on('show.bs.modal', function (ev) {
    const mode = $(ev.relatedTarget).data('mode') || 'enable';
    if (mode === 'disable') {
      $title.text('Disable Two-Factor Authentication');
      $desc.text('Enter the 6-digit code to confirm disabling two-factor on this account.');
      $submit.text('Disable').removeClass('btn-primary').addClass('btn-danger');
      $status.val('0');
    } else {
      $title.text('Enable Two-Factor Authentication');
      $desc.text('Enter the 6-digit code from your authenticator app to enable two-factor.');
      $submit.text('Enable').removeClass('btn-danger').addClass('btn-primary');
      $status.val('1');
    }
    $code.val('').trigger('focus');
  });

  // Helper: simple 6-digit validation
  function isSixDigits(v){ return /^\d{6}$/.test(v); }

  // Submit via AJAX
  $form.on('submit', function(e){
    e.preventDefault();

    const oneCode = $code.val().trim();
    if (!isSixDigits(oneCode)) {
      Swal.fire({
        text: 'Please enter a valid 6-digit code.',
        icon: 'info',
        confirmButtonText: 'Ok, got it!',
        customClass: { confirmButton: 'btn btn-primary' }
      }).then(() => $code.trigger('focus'));
      return;
    }

    const csrfName = $('#csrfName').val();
    const csrfHash = $('#csrfHash').val();
    const status   = $('#twofaStatus').val(); // "1" or "0"

    $submit.prop('disabled', true).attr('data-kt-indicator', 'on');

    $.ajax({
      url: base_url+'user/twofa/toggle',
      method: 'POST',
      dataType: 'json',
      data: (function(){
        const fd = {};
        fd['oneCode'] = oneCode;
        fd['status']  = status;
        fd[csrfName]  = csrfHash; // send CSRF
        return fd;
      })(),
      success: function(res){
        // Refresh CSRF hash if provided (optional, add from server)
        if (res.csrfName && res.csrfHash) {
          $('#csrfName').val(res.csrfName);
          $('#csrfHash').val(res.csrfHash);
        }

        if (res.status === 'success') {
          // Your requested format + reload
          Swal.fire({
            text: res.message,
            icon: 'success',
            confirmButtonText: 'Ok, got it!',
            customClass: { confirmButton: 'btn btn-primary' }
          }).then(() => {
            location.reload();
          });
          $modal.modal('hide');
        } else {
          Swal.fire({
            text: res.message || 'Invalid code. Please try again.',
            icon: 'error',
            confirmButtonText: 'Ok, got it!',
            customClass: { confirmButton: 'btn btn-primary' }
          });
        }
      },
      error: function(){
        Swal.fire({
          text: 'Network error. Please try again.',
          icon: 'error',
          confirmButtonText: 'Ok, got it!',
          customClass: { confirmButton: 'btn btn-primary' }
        });
      },
      complete: function(){
        $submit.prop('disabled', false).removeAttr('data-kt-indicator');
      }
    });
  });
})();



$('#kt_account_deactivate_account_submit').on('click', function() {
    if (!$('#deactivate').is(':checked')) {
        // Show error message near the checkbox
        $('.fv-plugins-message-container').html('<div class="text-danger">Please check the box to confirm deactivation.</div>');
        return;
    } else {
        // Clear any previous error
        $('.fv-plugins-message-container').html('');
    }

    // Proceed with action if checkbox is checked
    Swal.fire({
        icon: 'info',
        title: 'Demo Version',
        text: 'You can not change record.',
        confirmButtonText: 'Ok, got it!',
        customClass: {
            confirmButton: 'btn btn-primary'
        },
        buttonsStyling: false
    });
});



var KTEmailPreferences = (function () {
  const form = document.querySelector("#kt_email_preferences_form");
  const submitBtn = form.querySelector("button[type='submit']");

  // If you already expose these globally, keep using them
  const csrfName = window.csrfName || "<?= $this->security->get_csrf_token_name(); ?>";
  const csrfHash = window.csrfHash || "<?= $this->security->get_csrf_hash(); ?>";

  // Custom validator: ensure at least one checkbox is checked
  const atLeastOneChecked = () => {
    return form.querySelectorAll('input[type="checkbox"][name^="pref["]:checked').length > 0;
  };

  let validation;

  function enableBtn(enable) {
    if (enable) {
      submitBtn.removeAttribute("data-kt-indicator");
      submitBtn.disabled = false;
    } else {
      submitBtn.setAttribute("data-kt-indicator", "on");
      submitBtn.disabled = true;
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();

    // Trigger FormValidation (with our custom check)
    const valid = atLeastOneChecked();
    if (!valid) {
      Swal.fire({
        icon: "warning",
        text: "Select at least one notification option",
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
        buttonsStyling: false
      });
      return;
    }

    enableBtn(false);

    try {
      const fd = new FormData(form);
      fd.append(csrfName, csrfHash);

      const res = await axios.post(base_url + "user/update_email_preferences", fd, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });

      const data = res.data || {};
      if (data.status === "success") {
        // refresh csrf if server returns it
        if (data.csrf) {
          window.csrfHash = data.csrf.hash;
        }

        Swal.fire({
          text: data.message || "Preferences saved.",
          icon: "success",
          confirmButtonText: "Ok, got it!",
          customClass: { confirmButton: "btn btn-primary" },
          buttonsStyling: false
        });
      } else {
        Swal.fire({
          text: data.message || "Something went wrong. Please try again.",
          icon: "error",
          confirmButtonText: "Ok",
          customClass: { confirmButton: "btn btn-light-primary" },
          buttonsStyling: false
        });
      }
    } catch (err) {
      Swal.fire({
        text: "An unexpected error occurred.",
        icon: "error",
        confirmButtonText: "Ok",
        customClass: { confirmButton: "btn btn-light-primary" },
        buttonsStyling: false
      });
    } finally {
      enableBtn(true);
    }
  }

  return {
    init: function () {
      // Optional: keep FormValidation shell to match your stack
      validation = FormValidation.formValidation(form, {
        fields: {},
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row" })
        }
      });

      form.addEventListener("submit", handleSubmit);
    }
  };
})();

KTUtil.onDOMContentLoaded(function () {
  KTEmailPreferences.init();
});

