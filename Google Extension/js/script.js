var scrapeIsRunning = false, allQuestions = [];

chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {

    if (request.loggedIn === 'yes') {

    } else if (request.action === 'loggedOut') {
        window.location = window.location.href.replace('#','');
    }
});

