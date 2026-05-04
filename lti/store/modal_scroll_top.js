function scrollInstallContextToTop(modalElement) {
    window.scrollTo(0, 0);
    if (document.scrollingElement) document.scrollingElement.scrollTop = 0;
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;

    // If embedded, attempt to scroll parent/top frame when same-origin is allowed.
    try {
        if (window.parent && window.parent !== window && typeof window.parent.scrollTo === 'function') {
            window.parent.scrollTo(0, 0);
        }
    } catch (e) {
        // Cross-origin parent; fallback to postMessage below.
    }
    try {
        if (window.top && window.top !== window && typeof window.top.scrollTo === 'function') {
            window.top.scrollTo(0, 0);
        }
    } catch (e) {
        // Cross-origin top; fallback to postMessage below.
    }

    // Ask embedding page to scroll if it has a message listener.
    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ subject: 'lti.scrollToTop' }, '*');
        }
    } catch (e) {
        // Ignore postMessage errors.
    }
    try {
        if (window.top && window.top !== window) {
            window.top.postMessage({ subject: 'lti.scrollToTop' }, '*');
        }
    } catch (e) {
        // Ignore postMessage errors.
    }

    if (!modalElement) return;
    modalElement.scrollTop = 0;
    var dialog = modalElement.querySelector('.modal-dialog');
    if (dialog) dialog.scrollTop = 0;
    var body = modalElement.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
}

function bindInstallModalScrollToTop() {
    $(document).on('show.bs.modal shown.bs.modal', '.modal', function() {
        scrollInstallContextToTop(this);
    });
}
