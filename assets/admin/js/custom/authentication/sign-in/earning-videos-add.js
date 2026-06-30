// "use strict";

// /**
//  * assets/admin/js/custom/authentication/sign-in/earning-videos-add.js
//  * AJAX submit for Earning Videos Add/Edit form (supports URL or Upload)
//  *
//  * Form ID: #kt_earning_videos_form
//  * Submit btn: #kt_earning_videos_submit
//  * Redirect: uses data-kt-redirect-url on form
//  *
//  * Fields:
//  * - title, description, duration_seconds, reward_usd, sort_order, is_active
//  * - video_mode: url|upload
//  *   - if url => video_url required
//  *   - if upload => video_file optional on edit, required on add
//  * - thumb_mode: url|upload
//  *   - if url => thumb_url optional
//  *   - if upload => thumb_file optional
//  */

// (function () {
//     const form = document.getElementById("kt_earning_videos_form");
//     if (!form) return;

//     const submitBtn = document.getElementById("kt_earning_videos_submit");
//     const redirectUrl = form.getAttribute("data-kt-redirect-url") || "";

//     const getChecked = (name, fallback) => {
//         const el = form.querySelector(`input[name="${name}"]:checked`);
//         return el ? el.value : fallback;
//     };

//     const val = (name) => {
//         const el = form.querySelector(`[name="${name}"]`);
//         return el ? (el.value || "").trim() : "";
//     };

//     const file = (name) => {
//         const el = form.querySelector(`[name="${name}"]`);
//         return el && el.files && el.files.length ? el.files[0] : null;
//     };

//     const setBtnLoading = (loading) => {
//         if (!submitBtn) return;
//         if (loading) {
//             submitBtn.setAttribute("data-kt-indicator", "on");
//             submitBtn.disabled = true;
//         } else {
//             submitBtn.removeAttribute("data-kt-indicator");
//             submitBtn.disabled = false;
//         }
//     };

//     const toast = (icon, text) => {
//         // SweetAlert2 is already used in your list page
//         if (typeof Swal !== "undefined") {
//             Swal.fire({
//                 text: text,
//                 icon: icon,
//                 buttonsStyling: false,
//                 confirmButtonText: "Ok",
//                 customClass: { confirmButton: "btn btn-primary" },
//             });
//         } else {
//             alert(text);
//         }
//     };

//     const isNumeric = (x) => x !== "" && !isNaN(x);

//     const validate = () => {
//         const title = val("title");
//         const duration = val("duration_seconds");
//         const reward = val("reward_usd");
//         const mode = getChecked("video_mode", "url");
//         const videoUrl = val("video_url");
//         const videoFile = file("video_file");
//         const videoId = parseInt(val("video_id") || "0", 10);

//         if (!title) return "Title is required";
//         if (!duration || !isNumeric(duration) || parseInt(duration, 10) <= 0)
//             return "Duration Seconds must be a valid number";
//         if (!reward || !isNumeric(reward) || parseFloat(reward) < 0)
//             return "Reward USD must be a valid number";

//         if (mode === "url") {
//             if (!videoUrl) return "Video URL is required";
//         } else {
//             // upload mode: required only on add (video_id==0)
//             if (videoId <= 0 && !videoFile) return "Please upload a video file";
//         }

//         return null;
//     };

//     // Use fetch with FormData (supports file uploads)
//     form.addEventListener("submit", function (e) {
//         e.preventDefault();

//         const err = validate();
//         if (err) {
//             toast("error", err);
//             return;
//         }

//         const fd = new FormData(form);

//         // If in URL mode, ensure video_file not accidentally sent (some browsers still keep it)
//         const videoMode = getChecked("video_mode", "url");
//         const thumbMode = getChecked("thumb_mode", "url");

//         // Optional: clean up unused fields (not mandatory, but neat)
//         if (videoMode === "url") {
//             // don't require file; keep field but server can ignore
//         } else {
//             // upload mode: if user didn't choose file, server should keep existing on edit
//             // video_url input might still have value; server should ignore when upload selected
//         }

//         if (thumbMode === "url") {
//             // thumb_file not required
//         } else {
//             // upload mode: thumb_url might still exist, server should ignore if upload selected
//         }

//         setBtnLoading(true);

//         fetch(form.action, {
//             method: "POST",
//             body: fd,
//             credentials: "same-origin",
//         })
//             .then((r) => r.text())
//             .then((txt) => {
//                 let res = null;
//                 try {
//                     res = JSON.parse(txt);
//                 } catch (e) {
//                     // if server returns HTML error
//                     console.error("Non-JSON response:", txt);
//                 }

//                 if (!res || typeof res.status === "undefined") {
//                     toast("error", "Unexpected server response. Please check logs.");
//                     setBtnLoading(false);
//                     return;
//                 }

//                 if (res.status) {
//                     toast("success", res.message || "Saved successfully");
//                     // redirect
//                     if (redirectUrl) {
//                         setTimeout(() => {
//                             window.location.href = redirectUrl;
//                         }, 600);
//                     } else {
//                         setBtnLoading(false);
//                     }
//                 } else {
//                     toast("error", res.message || "Failed to save");
//                     setBtnLoading(false);
//                 }
//             })
//             .catch((err) => {
//                 console.error(err);
//                 toast("error", "Network error. Please try again.");
//                 setBtnLoading(false);
//             });
//     });
// })();






"use strict";

/**
 * assets/admin/js/custom/authentication/sign-in/earning-videos-add.js
 * AJAX submit for Earning Videos Add/Edit form (supports URL or Upload)
 *
 * Form ID: #kt_earning_videos_form
 * Submit btn: #kt_earning_videos_submit
 * Redirect: uses data-kt-redirect-url on form
 *
 * Fields:
 * - title, description, duration_seconds, reward_usd, sort_order, is_active
 * - video_mode: url|upload
 *   - if url => video_url required
 *   - if upload => video_file optional on edit, required on add
 * - thumb_mode: url|upload
 *   - if url => thumb_url optional
 *   - if upload => thumb_file optional
 */

(function () {

    /* =======================
       DEMO MODE HELPERS
    ======================= */
    function isDemoMode() {
        return !!(window.APP_CONFIG && window.APP_CONFIG.DEMO === true);
    }

    function demoBlockAlert() {
        Swal.fire({
            icon: "info",
            title: "Demo Version",
            text: "You Can not change record.",
            confirmButtonText: "Ok, got it!",
            customClass: { confirmButton: "btn btn-primary" },
            buttonsStyling: false
        });
    }

    const form = document.getElementById("kt_earning_videos_form");
    if (!form) return;

    const submitBtn = document.getElementById("kt_earning_videos_submit");
    const redirectUrl = form.getAttribute("data-kt-redirect-url") || "";

    const getChecked = (name, fallback) => {
        const el = form.querySelector(`input[name="${name}"]:checked`);
        return el ? el.value : fallback;
    };

    const val = (name) => {
        const el = form.querySelector(`[name="${name}"]`);
        return el ? (el.value || "").trim() : "";
    };

    const file = (name) => {
        const el = form.querySelector(`[name="${name}"]`);
        return el && el.files && el.files.length ? el.files[0] : null;
    };

    const setBtnLoading = (loading) => {
        if (!submitBtn) return;
        if (loading) {
            submitBtn.setAttribute("data-kt-indicator", "on");
            submitBtn.disabled = true;
        } else {
            submitBtn.removeAttribute("data-kt-indicator");
            submitBtn.disabled = false;
        }
    };

    const toast = (icon, text) => {
        // SweetAlert2 is already used in your list page
        if (typeof Swal !== "undefined") {
            Swal.fire({
                text: text,
                icon: icon,
                buttonsStyling: false,
                confirmButtonText: "Ok",
                customClass: { confirmButton: "btn btn-primary" },
            });
        } else {
            alert(text);
        }
    };

    const isNumeric = (x) => x !== "" && !isNaN(x);

    const validate = () => {
        const title = val("title");
        const duration = val("duration_seconds");
        const reward = val("reward_usd");
        const mode = getChecked("video_mode", "url");
        const videoUrl = val("video_url");
        const videoFile = file("video_file");
        const videoId = parseInt(val("video_id") || "0", 10);

        if (!title) return "Title is required";
        if (!duration || !isNumeric(duration) || parseInt(duration, 10) <= 0)
            return "Duration Seconds must be a valid number";
        if (!reward || !isNumeric(reward) || parseFloat(reward) < 0)
            return "Reward USD must be a valid number";

        if (mode === "url") {
            if (!videoUrl) return "Video URL is required";
        } else {
            if (videoId <= 0 && !videoFile) return "Please upload a video file";
        }

        return null;
    };

    // Use fetch with FormData (supports file uploads)
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const err = validate();
        if (err) {
            toast("error", err);
            return;
        }

        // ✅ DEMO BLOCK (stop before fetch)
        if (isDemoMode()) {
            demoBlockAlert();
            return;
        }

        const fd = new FormData(form);

        // If in URL mode, ensure video_file not accidentally sent (some browsers still keep it)
        const videoMode = getChecked("video_mode", "url");
        const thumbMode = getChecked("thumb_mode", "url");

        // Optional: clean up unused fields (not mandatory, but neat)
        if (videoMode === "url") {
            // don't require file; keep field but server can ignore
        } else {
            // upload mode: if user didn't choose file, server should keep existing on edit
            // video_url input might still have value; server should ignore when upload selected
        }

        if (thumbMode === "url") {
            // thumb_file not required
        } else {
            // upload mode: thumb_url might still exist, server should ignore if upload selected
        }

        setBtnLoading(true);

        fetch(form.action, {
            method: "POST",
            body: fd,
            credentials: "same-origin",
        })
            .then((r) => r.text())
            .then((txt) => {
                let res = null;
                try {
                    res = JSON.parse(txt);
                } catch (e) {
                    console.error("Non-JSON response:", txt);
                }

                if (!res || typeof res.status === "undefined") {
                    toast("error", "Unexpected server response. Please check logs.");
                    setBtnLoading(false);
                    return;
                }

                if (res.status) {
                    toast("success", res.message || "Saved successfully");
                    if (redirectUrl) {
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 600);
                    } else {
                        setBtnLoading(false);
                    }
                } else {
                    toast("error", res.message || "Failed to save");
                    setBtnLoading(false);
                }
            })
            .catch((err) => {
                console.error(err);
                toast("error", "Network error. Please try again.");
                setBtnLoading(false);
            });
    });

})();
