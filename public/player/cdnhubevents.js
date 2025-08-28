if (window.self !== window.top) {
    function PlayerjsEvents(event, id, info) {
        if (event == "loaderror") {
            window.parent.postMessage({
                type: "CDN_PLAYER_EVENT",
                action: "Load error",
            }, "*");
        }
        if (event == "play") {
            window.parent.postMessage({
                type: "CDN_PLAYER_EVENT",
                action: "Video start play",
            }, "*");
        }
        if (event == "pause") {
            window.parent.postMessage({
                type: "CDN_PLAYER_EVENT",
                action: "Video paused",
            }, "*");
        }
        if (event == "quartile") {
            window.parent.postMessage({
                type: "CDN_PLAYER_EVENT",
                action: info + ' of timeline completed',
            }, "*");
        }
        if (event == "line") {
            window.parent.postMessage({
                type: "CDN_PLAYER_EVENT",
                action: "Video rewind(fwd/rwd)",
            }, "*");
        }
    }
}
