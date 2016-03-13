// The scripts for the TSUGI runtime
// Needs to be loaded at the end after JQuery is loaded

// Make sure console.log does not fail.
if(typeof console === "undefined") { var console = { log: function (logMsg) { } }; }

// Send the CRF token on all of the non-ajax() calls
$.ajaxSetup({
    cache: false,
    headers : {
        'X-CSRF-Token' : CSRF_TOKEN
    }
});

function dataToggle(divName) {
    var ele = document.getElementById(divName);
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
}

function doHeartBeat() {
    window.console && console.log('Calling heartbeat to extend session');
    $.getJSON(HEARTBEAT_URL, function(data) {
        window.console && console.log(data);
        if ( data.lti || data.cookie ) {
            // No problem
        } else {
            clearInterval(HEARTBEAT_INTERVAL);
            HEARTBEAT_INTERVAL = false;
            alert('Your session has expired');
            window.location.href = "about:blank";
        }
    });
}

// https://gist.github.com/flesch/315070
function sprintf(){
    var args = Array.prototype.slice.call(arguments);
    return args.shift().replace(/%s/g, function(){
        return args.shift();
    });
}

var DE_BOUNCE_LTI_FRAME_RESIZE_TIMER = false;
var DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT = false;

// Adapted from Lumen Learning / Bracken Mosbacker
// element_id is the id of the frame in the parent document
function lti_frameResize(new_height, element_id) {
    if ( self == top ) return;

    if ( !new_height ) {
        new_height = $(document).height() + 10;
    }
    if ( new_height < 100 ) new_height = 100;
    if ( new_height > 5000 ) new_height = 5000;

    if ( DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT ) {
        delta = new_height - DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT;
        if ( new_height == 5000 && DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT >= 5000 ) {
            console.log("maximum lti_frameResize 5000 exceeded");
            return;
        } else if ( new_height > (DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT + 10) ) {
            // Do the resize for small increases
        } else if ( new_height < (DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT - 30) ) {
            // Do the resize for large decreases
        } else {
            console.log("lti_frameResize delta "+delta+" is too small, ignored");
            return;
        }
    }

    if ( DE_BOUNCE_LTI_FRAME_RESIZE_TIMER ) {
        clearTimeout(DE_BOUNCE_LTI_FRAME_RESIZE_TIMER);
        DE_BOUNCE_LTI_FRAME_RESIZE_TIMER = false;
    }

    DE_BOUNCE_LTI_FRAME_RESIZE_TIMER = setTimeout(
        function () { lti_frameResizeNow(new_height, element_id); }, 
        1000
    );
}

function lti_frameResizeNow(new_height, element_id) {
    parms = {
      subject: "lti.frameResize",
      height: new_height
    }
    if ( element_id ) {
        parms.element_id = element_id;
    }
    var parm_str = JSON.stringify(parms);

    console.log("sending "+parm_str);
    parent.postMessage(parm_str, "*");

    DE_BOUNCE_LTI_FRAME_RESIZE_HEIGHT = new_height;
}

function lti_hideLMSNavigation() {
    parent.postMessage(JSON.stringify({
      subject: "lti.hideModuleNavigation",
      show: false
    }), "*");
}

function lti_showLMSNavigation() {
    parent.postMessage(JSON.stringify({
      subject: "lti.showModuleNavigation",
      show: true
    }), "*");
}

// tell the parent iframe to scroll to top
function lti_scrollParentToTop() {
    parent.postMessage(JSON.stringify({
      subject: "lti.scrollToTop"
    }), "*");
}
  
// Straight Outta Github (with adaptations)
// https://github.com/lumenlearning/candela/blob/master/wp-content/plugins/candela-utility/themes/bombadil/js/iframe_resizer.js
/**
 * Listen for a window post message to resize an embedded iframe
 * Needs to be an json stringified object that identifies the id of
 * the element to resize like this:
   parent.postMessage(JSON.stringify({
      subject: "lti.frameResize",
      height: default_height,
      element_id: "lumen_assessment_1"
  }), "*");
 * The element_id needed is passed as a query parameter `iframe_resize_id`
 */

// Unlike candela, we always do this - even if we are in an iframe - Inception
// console.log(window.location.href + "setting up listener");
window.addEventListener('message', function (e) {
    // console.log(window.location.href + " got message");
    // console.log(e.data);
    try {
        var message = JSON.parse(e.data);
        switch (message.subject) {
            case 'lti.frameResize':
                var height = message.height;
                if (height >= 5000) height = 5000;
                if (height <= 0) height = 1;
                if ( message.element_id ) {
                    var $iframe = jQuery('#' + message.element_id);
                    $iframe.css('height', height + 'px');
                    console.log("window.location.href set "+message.element_id+" height="+height);
                } else { // Must loop through all of them - best if there is one
                    $('.lti_frameResize').each(function(i, obj) {
                        $(this).css('height', height + 'px');
                        console.log("window.location.href set height="+height);
                    });
                }
                break;
        }
    } catch (err) {
        console.log('invalid message received from ', e.origin);
        console.log(e.data);
        console.log('Exception: '+err)
    }
});

// If we are not the top frame - immediately communicate our size and jack into the JQuery resize
// Debounce happens in lti_frameResize()
if ( ! (self == top) ) {
    if ( typeof LTI_PARENT_IFRAME_ID === 'undefined' ) {
        lti_frameResize();
        $(window).on('resize', function() { lti_frameResize(); });
    } else {
        lti_frameResize(false, LTI_PARENT_IFRAME_ID);
        $(window).on('resize', function() { lti_frameResize(false, LTI_PARENT_IFRAME_ID); });
    }
}
    
// From Sakai
// Return the breakpoint between small and medium sized displays - for morpheus currently the same
function portalSmallBreakPoint() { return 800; }
function portalMediumBreakPoint() { return 800; }

// Return the correct width for a modal dialog.
function modalDialogWidth() {
    var wWidth = $(window).width();
    var pbr = portalSmallBreakPoint();
    var dWidth = wWidth * 0.8;
    if ( wWidth <= pbr ) {
        dWidth = pbr * 0.8;
        if ( dWidth > (wWidth * 0.95) ) {
            dWidth = wWidth * 0.95;
        }
    }
    if ( dWidth < 300 ) dWidth = 300; // Should not happen
    return Math.round(dWidth);
}

function showModalIframe(title, modalId, iframeId, spinnerUrl) {
console.log("showModalIframe "+modalId);
    $("#"+modalId).dialog({
        title: title,
        width: modalDialogWidth(),
        position: { my: "center top+30px", at: "center top+30px", of: window },
        modal: true,
        draggable: false,
        open: function() {
            $('#'+iframeId).width('95%');
        },
        close: function() {
            if ( spinnerUrl ) {
                $('#'+iframeId).attr('src',spinnerUrl);
            }
        }
    });

    $(window).resize(function() {
        $("#"+modalId).dialog("option", "width", modalDialogWidth());
        $('#'+iframeId).width('95%');
    });
}

