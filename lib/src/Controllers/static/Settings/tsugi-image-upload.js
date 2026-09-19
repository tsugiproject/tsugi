/**
 * Course image upload: cover-crop to a target size, then JPEG-compress
 * under a byte limit.
 *
 * The compress loop (quality, then dimensions, JPEG always) matches
 * tool/peer-grade/index.php resizeImage() so this can later be one library.
 * Cover-crop / exact target size is the extra "construct" step for
 * 16×9 hero and square icon.
 *
 * Bind: input.tsugi_image[data-target-width][data-target-height][data-max-bytes]
 */
(function (global) {
    const MAX_PREVIEW_SIZE = 300;

    // Same algorithm as tool/peer-grade/index.php resizeImage().
    // Always outputs JPEG. Starts at the canvas's current pixels.
    function compressCanvasToJpeg(canvas, maxSizeBytes, callback) {
        let resizeWidth = canvas.width;
        let resizeHeight = canvas.height;
        let quality = 0.92;
        let attempts = 0;
        const maxAttempts = 20;
        const source = document.createElement('canvas');
        source.width = canvas.width;
        source.height = canvas.height;
        source.getContext('2d').drawImage(canvas, 0, 0);

        function tryCompress() {
            attempts++;
            if (attempts > maxAttempts) {
                callback(null, 'Unable to compress image below size limit');
                return;
            }

            canvas.width = resizeWidth;
            canvas.height = resizeHeight;
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, resizeWidth, resizeHeight);
            ctx.drawImage(source, 0, 0, resizeWidth, resizeHeight);

            canvas.toBlob(function (compressedBlob) {
                if (!compressedBlob) {
                    callback(null, 'Failed to compress image');
                    return;
                }
                if (compressedBlob.size > maxSizeBytes) {
                    if (quality > 0.3) {
                        quality -= 0.1;
                    } else {
                        resizeWidth = Math.floor(resizeWidth * 0.85);
                        resizeHeight = Math.floor(resizeHeight * 0.85);
                        quality = 0.85;
                    }
                    if (resizeWidth < 100 || resizeHeight < 100) {
                        callback(null, 'Image too large to compress below size limit');
                        return;
                    }
                    tryCompress();
                } else {
                    callback(compressedBlob, null);
                }
            }, 'image/jpeg', quality);
        }

        tryCompress();
    }

    // Peer-grade compatible: load file, draw at native size, JPEG under maxSizeBytes.
    function resizeImage(file, maxSizeBytes, callback) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0);
                compressCanvasToJpeg(canvas, maxSizeBytes, callback);
            };
            img.onerror = function () {
                callback(null, 'Failed to load image');
            };
            img.src = e.target.result;
        };
        reader.onerror = function () {
            callback(null, 'Failed to read file');
        };
        reader.readAsDataURL(file);
    }

    function coverDraw(img, canvas, targetW, targetH) {
        canvas.width = targetW;
        canvas.height = targetH;
        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, targetW, targetH);

        const srcW = img.width;
        const srcH = img.height;
        const srcAspect = srcW / srcH;
        const dstAspect = targetW / targetH;
        let sx = 0;
        let sy = 0;
        let sw = srcW;
        let sh = srcH;
        if (srcAspect > dstAspect) {
            sw = Math.round(srcH * dstAspect);
            sx = Math.floor((srcW - sw) / 2);
        } else {
            sh = Math.round(srcW / dstAspect);
            sy = Math.floor((srcH - sh) / 2);
        }
        ctx.drawImage(img, sx, sy, sw, sh, 0, 0, targetW, targetH);
    }

    function processImageToSpec(file, spec, callback) {
        const targetW = spec.width;
        const targetH = spec.height;
        const maxSizeBytes = spec.maxBytes;
        const minEdge = spec.minEdge || 1;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                if (Math.min(img.width, img.height) < minEdge) {
                    callback(null, 'Image is too small. Use at least ' + minEdge + ' pixels on the short edge.');
                    return;
                }
                const canvas = document.createElement('canvas');
                coverDraw(img, canvas, targetW, targetH);
                compressCanvasToJpeg(canvas, maxSizeBytes, callback);
            };
            img.onerror = function () {
                callback(null, 'Failed to load image');
            };
            img.src = e.target.result;
        };
        reader.onerror = function () {
            callback(null, 'Failed to read file');
        };
        reader.readAsDataURL(file);
    }

    function processImageFile(input, file) {
        const previewId = input.getAttribute('data-preview');
        const infoId = input.getAttribute('data-info');
        const previewImg = previewId ? document.getElementById(previewId) : null;
        const previewInfo = infoId ? document.getElementById(infoId) : null;
        const targetW = parseInt(input.getAttribute('data-target-width') || '0', 10);
        const targetH = parseInt(input.getAttribute('data-target-height') || '0', 10);
        const maxBytes = parseInt(input.getAttribute('data-max-bytes') || '0', 10);
        const minEdge = parseInt(input.getAttribute('data-min-edge') || '0', 10);

        if (!file || !file.type.match(/^image\/(png|jpeg|jpg)$/i)) {
            if (previewInfo) {
                previewInfo.textContent = 'Choose a PNG or JPEG image.';
                previewInfo.style.color = 'red';
            }
            return;
        }

        if (previewInfo) {
            previewInfo.textContent = 'Processing image...';
            previewInfo.style.color = '#666';
        }
        if (previewImg && previewImg.parentElement) {
            previewImg.parentElement.style.display = 'inline-block';
        }

        const spec = {
            width: targetW,
            height: targetH,
            maxBytes: maxBytes,
            minEdge: minEdge
        };

        processImageToSpec(file, spec, function (processedBlob, error) {
            if (error || !processedBlob) {
                if (previewInfo) {
                    previewInfo.textContent = 'Error: ' + (error || 'Error processing image');
                    previewInfo.style.color = 'red';
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    const maxDim = MAX_PREVIEW_SIZE;
                    previewImg.style.maxWidth = maxDim + 'px';
                    previewImg.style.maxHeight = maxDim + 'px';
                }
                if (previewInfo) {
                    const processedKb = (processedBlob.size / 1024).toFixed(1);
                    const originalKb = (file.size / 1024).toFixed(1);
                    previewInfo.textContent = 'Original: ' + originalKb + ' KB → JPEG ' +
                        targetW + '×' + targetH + ', ' + processedKb + ' KB';
                    previewInfo.style.color = '#28a745';
                }
                const dataTransfer = new DataTransfer();
                const newFileName = file.name.replace(/\.(png|jpeg|jpg)$/i, '.jpg');
                const newFile = new File([processedBlob], newFileName, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });
                dataTransfer.items.add(newFile);
                input.files = dataTransfer.files;
            };
            reader.readAsDataURL(processedBlob);
        });
    }

    function bindInputs(root) {
        const scope = root || document;
        const imageInputs = scope.querySelectorAll('input.tsugi_image');
        imageInputs.forEach(function (input) {
            if (input.getAttribute('data-tsugi-image-bound') === '1') {
                return;
            }
            input.setAttribute('data-tsugi-image-bound', '1');
            input.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    processImageFile(input, file);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindInputs(document);
    });

    global.TsugiImageUpload = {
        resizeImage: resizeImage,
        compressCanvasToJpeg: compressCanvasToJpeg,
        coverDraw: coverDraw,
        processImageToSpec: processImageToSpec,
        processImageFile: processImageFile,
        bindInputs: bindInputs
    };
})(window);
