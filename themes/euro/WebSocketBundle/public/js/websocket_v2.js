/* global autobahn, websocketUrl, websocketJwt, websocketRealm */

(function(){
    'use strict';
    var connection = new autobahn.Connection({
        url: websocketUrl,
        realm: websocketRealm,
        authmethods: ['jwt'],
        onchallenge: function() {
            return websocketJwt;
        }
    });

    var triggerNotification = function(args, notificationType, alertType, playSoundAlert = playDefaultSoundAlert) {
        var details = JSON.parse(args[0]);
        var title = details.title;
        var message = details.message + ' Please proceed to pending tab to view.';
        var url = Global.dummyTransactionUrl.replace('/__type__', '/'+ details.otherDetails.type).replace('/__id__', '/'+ details.otherDetails.id);

        if (notificationType == 'registration' || notificationType == 'requestProduct') {
            message = details.message + ' Please proceed to member list to view.';
            url = Global.dummyCustomerProfileUrl.replace('/__id__', '/'+ details.otherDetails.id).replace('/__activeTab__', '/'+ details.otherDetails.type);
        }

        playSoundAlert();
        prependToList(title, details.message, url);
        updateNotificationListCounter();
        notification(title, message, alertType, 'bottom right');
        getCurrentTransactionCount();
    }

    var playDefaultSoundAlert = function() {
        var audioNotification = document.createElement('audio');
        audioNotification.innerHTML = '<source src="/assets/audio/sound.mp3" />';
        audioNotification.play();
    }

    var playMemberCreationSoundAlert = function() {
        var audioNotification = document.createElement('audio');
        audioNotification.innerHTML = '<source src="/assets/audio/member_creation_sound.mp3" />';
        audioNotification.play();
    }

    var prependToList = function(title, message, url) {
        var htmlContent = '<a href="'+ url +'" class="date-created list-group-item unread">'
            + '<div class="media"><div class="media-body">'
            + '<h5 class="media-heading">'+ title.toUpperCase() +'</h5>'
            + '<p class="m-0"><small>'+ message +' <i class="ti-email"></i></small></p>'
            + '</div></div></a>';
        $('li .notification-list').prepend(htmlContent);
    }

    var updateNotificationListCounter = function () {
        var count = $('li .notification-list a.unread').size();
        $('.notification-counter').html(count);
    }

    connection.onopen = function(session, details) {
        if (typeof loginSubscription === "function") {
            loginSubscription(session);
        }

        session.subscribe(topics.memberCreated, function(args) {
          triggerNotification(args, 'registration', 'info', playMemberCreationSoundAlert);
        });

        session.subscribe(topics.productRequested, function(args) {
            triggerNotification(args, 'requestProduct', 'success');
        });

        session.subscribe('ms.topic.transaction_deposit', function(args) {
            triggerNotification(args, 'transaction', 'success');
        });

        session.subscribe('ms.topic.transaction_withdrawal', function(args) {
            triggerNotification(args, 'transaction', 'success');
        });

        session.subscribe('ms.topic.transaction_transfer', function(args) {
            triggerNotification(args, 'transaction', 'success');
        });

        session.subscribe('ms.topic.transaction_p2p_transfer', function(args) {
            triggerNotification(args, 'transaction', 'success');
        });

        if (typeof btcExchangeRateSubscription === "function") {
            btcExchangeRateSubscription(session);
        }
        session.subscribe('wamp.metaevent.session.on_leave', updateCounters);
        session.subscribe('wamp.metaevent.session.on_join', updateCounters);

        // Get active sessions on page load.
        updateCounters();

        function updateCounters(args) {
            getMSActiveSessions().then(function (result) {
                $('#msCount').text(result);
            }).catch(function(error) {
                console.log(error);
            });

            getBOActiveSessions().then(function (result) {
            $('#boCount').text(result);
            }).catch(function(error) {
                console.log(error);
            });
        }

        function getMSActiveSessions() {
            return session.call('zimiwebsockets.counter.ms_active_sessions', []);
        }

        function getBOActiveSessions() {
            return session.call('zimiwebsockets.counter.bo_active_sessions', []);
        }

        websocketOnOpen(session);
    };

    connection.open();
})();
