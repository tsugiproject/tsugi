// The scripts for the TSUGI runtime
// Needs to be loaded at the end after JQuery is loaded

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

