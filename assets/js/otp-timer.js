// OTP Expiry Timer - Show countdown and manage expiry
(function() {
  const OTP_VALIDITY_MINUTES = 15; // Match your backend setting
  const OTP_VALIDITY_SECONDS = OTP_VALIDITY_MINUTES * 60;

  // Initialize timer on page load
  function initOtpTimer() {
    const otpStartedAt = localStorage.getItem('otp_started_at');

    if (!otpStartedAt) {
      // First time on this page, store current time
      localStorage.setItem('otp_started_at', Date.now());
      startTimer();
    } else {
      // Check if OTP is still valid
      const elapsedSeconds = Math.floor((Date.now() - parseInt(otpStartedAt)) / 1000);

      if (elapsedSeconds >= OTP_VALIDITY_SECONDS) {
        // OTP expired
        handleOtpExpired();
      } else {
        // Still valid, show remaining time
        const remainingSeconds = OTP_VALIDITY_SECONDS - elapsedSeconds;
        startTimer(remainingSeconds);
      }
    }
  }

  function startTimer(remainingSeconds = OTP_VALIDITY_SECONDS) {
    const timerElement = document.getElementById('otp-timer');
    const submitBtn = document.querySelector('button[type="submit"]');
    const resendBtn = document.getElementById('resend-otp-btn');

    if (!timerElement) return;

    let timeLeft = remainingSeconds;

    const interval = setInterval(() => {
      timeLeft--;

      if (timeLeft <= 0) {
        clearInterval(interval);
        handleOtpExpired();
        return;
      }

      // Display time
      const minutes = Math.floor(timeLeft / 60);
      const seconds = timeLeft % 60;
      timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

      // Change color warning at 2 minutes
      if (timeLeft <= 120) {
        timerElement.style.color = '#ff6b6b'; // Red
      }

      // Disable submit after 2 minutes, encourage resend
      if (timeLeft <= 120 && submitBtn) {
        submitBtn.style.opacity = '0.5';
      }
    }, 1000);
  }

  function handleOtpExpired() {
    const timerElement = document.getElementById('otp-timer');
    const timerContainer = document.getElementById('otp-timer-container');
    const submitBtn = document.querySelector('button[type="submit"]');
    const resendBtn = document.getElementById('resend-otp-btn');

    // Update display
    if (timerElement) {
      timerElement.textContent = 'Expired';
      timerElement.style.color = '#d9534f';
    }

    if (timerContainer) {
      timerContainer.innerHTML = `
        <div style="color: #d9534f; font-weight: bold; text-align: center; padding: 10px; background: #fee; border-radius: 6px; margin: 10px 0;">
          ⏰ OTP Expired! Click below to resend a new code.
        </div>
      `;
    }

    // Disable submit button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.5';
      submitBtn.style.cursor = 'not-allowed';
    }

    // Enable and highlight resend button
    if (resendBtn) {
      resendBtn.style.display = 'block';
      resendBtn.style.fontWeight = 'bold';
      resendBtn.style.background = '#ff6b6b';
      resendBtn.style.color = 'white';
    }

    // Clear localStorage to allow resend
    localStorage.removeItem('otp_started_at');
  }

  function resendOtp() {
    fetch(typeof base_url !== 'undefined' ? base_url + 'user/login/resend-otp' : '/user/login/resend-otp', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(res => res.json())
      .then(data => {
        const messageContainer = document.getElementById('otp-timer-container');

        if (data.status) {
          // Show success message in form
          if (messageContainer) {
            messageContainer.innerHTML = `
              <div style="background: #4CAF50; color: white; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 10px;">
                ✓ New OTP sent to your email!
              </div>
              <div style="text-align: center; font-size: 14px; color: rgba(255,255,255,.65);">
                OTP expires in: <span id="otp-timer" style="font-weight: bold; color: #4CAF50; font-size: 16px;">15:00</span>
              </div>
            `;
          }

          // Clear and restart timer
          localStorage.removeItem('otp_started_at');
          localStorage.setItem('otp_started_at', Date.now());

          // Re-enable and reset submit button
          const submitBtn = document.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
          }

          // Clear all OTP inputs
          document.querySelectorAll('input[maxlength="1"]').forEach(inp => inp.value = '');

          // Restart timer
          initOtpTimer();

          // Hide success message after 3 seconds
          setTimeout(() => {
            if (messageContainer) {
              messageContainer.innerHTML = `
                <div style="text-align: center; font-size: 14px; color: rgba(255,255,255,.65);">
                  OTP expires in: <span id="otp-timer" style="font-weight: bold; color: #4CAF50; font-size: 16px;">15:00</span>
                </div>
              `;
              initOtpTimer();
            }
          }, 3000);
        } else {
          // Show error message in form
          if (messageContainer) {
            messageContainer.innerHTML = `
              <div style="background: #d9534f; color: white; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 10px;">
                ✗ ${data.message || 'Failed to resend OTP'}
              </div>
              <div style="text-align: center; font-size: 14px; color: rgba(255,255,255,.65);">
                OTP expires in: <span id="otp-timer" style="font-weight: bold; color: #4CAF50; font-size: 16px;">15:00</span>
              </div>
            `;
          }

          // Hide error message after 3 seconds
          setTimeout(() => {
            if (messageContainer) {
              messageContainer.innerHTML = `
                <div style="text-align: center; font-size: 14px; color: rgba(255,255,255,.65);">
                  OTP expires in: <span id="otp-timer" style="font-weight: bold; color: #4CAF50; font-size: 16px;">15:00</span>
                </div>
              `;
              initOtpTimer();
            }
          }, 3000);
        }
      })
      .catch(err => {
        const messageContainer = document.getElementById('otp-timer-container');
        if (messageContainer) {
          messageContainer.innerHTML = `
            <div style="background: #d9534f; color: white; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 10px;">
              ✗ Error: ${err.message}
            </div>
            <div style="text-align: center; font-size: 14px; color: rgba(255,255,255,.65);">
              OTP expires in: <span id="otp-timer" style="font-weight: bold; color: #4CAF50; font-size: 16px;">15:00</span>
            </div>
          `;
        }
      });
  }

  // Expose to global scope
  window.initOtpTimer = initOtpTimer;
  window.resendOtp = resendOtp;

  // Auto-initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOtpTimer);
  } else {
    initOtpTimer();
  }
})();
