document.addEventListener("DOMContentLoaded", function () {

    function alertMsg(msg) {
        if (window.Swal) {
            Swal.fire({
                text: msg, icon: "warning", buttonsStyling: false,
                confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" }
            });
        } else { alert(msg); }
    }

    /* ---------------- type toggle ---------------- */
    var typeRadios = document.querySelectorAll('input[name="announcement_type"]');
    var textSection = document.getElementById('text-section');
    var imageSection = document.getElementById('image-section');

    function updateTypeSections() {
        var checked = document.querySelector('input[name="announcement_type"]:checked');
        var type = checked ? checked.value : 'text';
        textSection.style.display = type === 'text' ? '' : 'none';
        imageSection.style.display = type === 'image' ? '' : 'none';
    }
    typeRadios.forEach(function (r) { r.addEventListener('change', updateTypeSections); });
    updateTypeSections();

    /* ---------------- cropper ---------------- */
    var stage = document.getElementById('ann-crop-stage');
    if (!stage) return; // image section not present on this page

    var canvas = document.getElementById('ann-src-canvas');
    var ctx = canvas.getContext('2d');
    var cropBox = document.getElementById('ann-crop-box');
    var handle = cropBox.querySelector('.ann-handle');
    var zoomSlider = document.getElementById('ann-zoom');
    var fileInput = document.getElementById('ann-file-input');
    var applyBtn = document.getElementById('ann-apply-crop');
    var preview = document.getElementById('ann-crop-preview');
    var previewWrap = document.getElementById('ann-crop-preview-wrap');
    var cropperWrap = document.getElementById('ann-cropper-wrap');
    var hiddenData = document.getElementById('ann-cropped-data');

    var ASPECT = 3; // width / height guide for the crop box
    var STAGE_W = 600, STAGE_H = 300;
    var img = null, baseScale = 1, curScale = 1;

    function drawImage() {
        if (!img) return;
        var w = img.naturalWidth * curScale;
        var h = img.naturalHeight * curScale;
        canvas.width = w;
        canvas.height = h;
        ctx.clearRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
    }

    function resetCropBox() {
        var w = Math.min(300, STAGE_W * 0.8);
        var h = w / ASPECT;
        cropBox.style.width = w + 'px';
        cropBox.style.height = h + 'px';
        cropBox.style.left = ((STAGE_W - w) / 2) + 'px';
        cropBox.style.top = ((STAGE_H - h) / 2) + 'px';
    }

    function loadFile(file) {
        var okTypes = ['image/png', 'image/jpeg', 'image/webp'];
        if (okTypes.indexOf(file.type) === -1) {
            alertMsg('Only JPG, PNG or WEBP images are allowed.');
            fileInput.value = '';
            return;
        }
        if (file.size > 3 * 1024 * 1024) {
            alertMsg('Image must be 3MB or smaller.');
            fileInput.value = '';
            return;
        }
        var url = URL.createObjectURL(file);
        var im = new Image();
        im.onload = function () {
            img = im;
            baseScale = Math.min(STAGE_W / im.naturalWidth, STAGE_H / im.naturalHeight, 1);
            curScale = baseScale;
            zoomSlider.value = 100;
            drawImage();
            resetCropBox();
            cropperWrap.style.display = '';
            previewWrap.style.display = 'none';
            hiddenData.value = '';
            URL.revokeObjectURL(url);
        };
        im.src = url;
    }

    fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) loadFile(fileInput.files[0]);
    });

    /* drag to move / resize via pointer events (mouse + touch unified) */
    var dragging = null;

    cropBox.addEventListener('pointerdown', function (e) {
        if (e.target === handle) return;
        dragging = {
            mode: 'move', startX: e.clientX, startY: e.clientY,
            boxLeft: parseFloat(cropBox.style.left), boxTop: parseFloat(cropBox.style.top)
        };
        cropBox.setPointerCapture(e.pointerId);
    });

    handle.addEventListener('pointerdown', function (e) {
        e.stopPropagation();
        dragging = {
            mode: 'resize', startX: e.clientX, startY: e.clientY,
            boxWidth: parseFloat(cropBox.style.width),
            boxLeft: parseFloat(cropBox.style.left), boxTop: parseFloat(cropBox.style.top)
        };
        cropBox.setPointerCapture(e.pointerId);
    });

    cropBox.addEventListener('pointermove', function (e) {
        if (!dragging) return;
        var dx = e.clientX - dragging.startX;
        var dy = e.clientY - dragging.startY;

        if (dragging.mode === 'move') {
            var w = parseFloat(cropBox.style.width), h = parseFloat(cropBox.style.height);
            var left = Math.max(0, Math.min(STAGE_W - w, dragging.boxLeft + dx));
            var top = Math.max(0, Math.min(STAGE_H - h, dragging.boxTop + dy));
            cropBox.style.left = left + 'px';
            cropBox.style.top = top + 'px';
        } else {
            var newW = Math.max(80, Math.min(STAGE_W - dragging.boxLeft, dragging.boxWidth + dx));
            var newH = newW / ASPECT;
            if (dragging.boxTop + newH > STAGE_H) { newH = STAGE_H - dragging.boxTop; newW = newH * ASPECT; }
            cropBox.style.width = newW + 'px';
            cropBox.style.height = newH + 'px';
        }
    });

    ['pointerup', 'pointercancel'].forEach(function (ev) {
        cropBox.addEventListener(ev, function () { dragging = null; });
    });

    zoomSlider.addEventListener('input', function () {
        if (!img) return;
        curScale = baseScale * (parseInt(zoomSlider.value, 10) / 100);
        drawImage();
        resetCropBox();
    });

    applyBtn.addEventListener('click', function () {
        if (!img) { alertMsg('Choose an image first.'); return; }

        var stageRect = stage.getBoundingClientRect();
        var canvasRect = canvas.getBoundingClientRect();
        var boxLeft = parseFloat(cropBox.style.left);
        var boxTop = parseFloat(cropBox.style.top);
        var boxW = parseFloat(cropBox.style.width);
        var boxH = parseFloat(cropBox.style.height);

        var canvasOffsetX = canvasRect.left - stageRect.left;
        var canvasOffsetY = canvasRect.top - stageRect.top;

        var srcX = (boxLeft - canvasOffsetX) / curScale;
        var srcY = (boxTop - canvasOffsetY) / curScale;
        var srcW = boxW / curScale;
        var srcH = boxH / curScale;

        var OUT_W = 1200, OUT_H = 400;
        var out = document.createElement('canvas');
        out.width = OUT_W; out.height = OUT_H;
        var octx = out.getContext('2d');
        octx.drawImage(img, srcX, srcY, srcW, srcH, 0, 0, OUT_W, OUT_H);

        var dataUrl = out.toDataURL('image/jpeg', 0.9);
        hiddenData.value = dataUrl;
        preview.src = dataUrl;
        previewWrap.style.display = '';
    });

    /* exposed so announcement-edit-settings.js can gate submission on it —
       running this as a second, independent click listener on the same submit
       button would race the first listener's async validate+axios chain */
    window.annAnnouncementReadyToSubmit = function () {
        var checked = document.querySelector('input[name="announcement_type"]:checked');
        var type = checked ? checked.value : 'text';
        if (type !== 'image') return true;
        return !!(hiddenData.value || imageSection.dataset.hasImage === '1');
    };
});
