onInitialize();

chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
    if (request.action === "logOut") {
        localStorage.removeItem('loggedIn');
        setupUI();
    }
});

function onInitialize() {

    chrome.tabs.getSelected(null, function(tab) {

        var URL = tab.url;

        $('.settings').on('click', function () {
            chrome.tabs.create({
                url: 'https://app.30daycra.com/member/login.php'
            });
        });

    });

}

function sendMessage(settings) {
    chrome.tabs.query({active: true, currentWindow: true}, function(tabs){
        chrome.tabs.sendMessage(tabs[0].id, settings, function(response) {

        });
    });
}
