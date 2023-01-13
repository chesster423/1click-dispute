chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
    if (request.action === "checkLogin") {
        if (localStorage.getItem('loggedIn') === 'yes') {
            sendResponse({
                loggedIn: 'yes',
                userID: localStorage.getItem('userID')
            });
        } else
            sendResponse({loggedIn:'no'});
    }
/*
    if (request.action === 'logOut') {
        localStorage.removeItem('userID');
        localStorage.removeItem('loggedIn');
        setupUI();
        sendResponse({loggedIn:'no'});
    }

 */
});

chrome.browserAction.onClicked.addListener(function(tab) {
    var d = new Date();
    var endDate = d.getFullYear() + '-' + (d.getMonth()+1) + '-' + d.getDate();

    chrome.tabs.create({url: 'https://app.creditrepaircloud.com/everything/todays_letter?from=2015-08-12&to='+endDate+'&flag2=2', active: true }, tab => {

    });
});

chrome.runtime.onMessage.addListener(function(request, sender, callback) {
    if (request.action == "xmlhttp") {
        var xhttp = new XMLHttpRequest();
        var method = request.method ? request.method.toUpperCase() : 'GET';

        xhttp.onload = function() {
            callback(xhttp.responseText);
        };
        xhttp.onerror = function() {
            callback();
        };
        xhttp.open(method, request.url, true);
        if (method == 'POST') {
            xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        xhttp.send(request.data);
        return true; // prevents the callback from being called too early on return
    }
});