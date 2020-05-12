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
        console.log(args);
        console.log(notificationType);
        console.log(alertType);
        var details = args[0];
        var title = details.title;
        var message = details.message + ' Please proceed to pending tab to view.';
        var url = "";

        if (notificationType == 'registration' || notificationType == 'requestProduct') {
            message = details.message + ' Please proceed to member list to view.';
            url = Global.dummyCustomerProfileUrl.replace('/__id__', '/'+ details.otherDetails.id).replace('/__activeTab__', '/'+ details.otherDetails.type);
        } else if (notificationType == 'docs') { 
            message = details.message + ' Please proceed to kyc pending list to view.';
            url = Global.dummyMemberRequestUrl.replace('/__id__', '/'+ details.otherDetails.id).replace('/__type__', '/'+ details.otherDetails.type);
        } else if(notificationType == 'login') {
            message = details.message;
            url = '';
        } else {
            var type = details.otherDetails.type || '';
            var id = details.otherDetails.id || '';
            url = Global.dummyTransactionUrl.replace('/__type__', '/'+ type).replace('/__id__', '/'+ id);
        }

        playSoundAlert();
        prependToList(title, details.message, url);
        updateNotificationListCounter();
        notification(title, message, alertType, 'bottom right');
        getCurrentTransactionCount();
    }

    var playDefaultSoundAlert = function() {
        var audioNotification = document.createElement('audio');
        var audio = $('<audio controls autoplay hidden="" src="/assets/audio/sound.mp3" type ="audio/mp3"">');
        $(audio).on('ended', function () {
            $(this).remove();
        });
        $('#wrapper').append(audio);
    }

    var playMemberCreationSoundAlert = function() {
        var audio = $('<audio controls autoplay hidden="" src="/assets/audio/member_creation_sound.mp3" type ="audio/mp3"">');
        $(audio).on('ended', function () {
            $(this).remove();
        });
        $('#wrapper').append(audio);
    }

    function guidGenerator() {
        var S4 = function() {
            return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
        };
        return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
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

        session.subscribe('member.registered', function (args) {
            triggerNotification(args, 'registration', 'success', playMemberCreationSoundAlert);
        });

        session.subscribe('created.transaction', function (args) {
            triggerNotification(args, 'transaction', 'success', playDefaultSoundAlert);
        });

        session.subscribe('bo.event.kyc_file_uploaded', function(args) {
            triggerNotification(args, 'docs', 'success', playDefaultSoundAlert);
        });

        session.subscribe('bo.topic.admin_user_login', function(args){
            console.log('bo.topic.admin_user_login');
           triggerNotification(args, 'login', 'info', playDefaultSoundAlert);
        });

        if (typeof btcExchangeRateSubscription === "function") {
            btcExchangeRateSubscription(session);
        }
        session.subscribe('wamp.metaevent.session.on_leave', updateCounters);
        session.subscribe('wamp.metaevent.session.on_join', updateCounters);

        // Get active sessions on page load.
        updateCounters();

        function updateCounters(args) {
            getMSActiveSessionCount().then(function (result) {
                $('#msCount').text(result);
            }).catch(function(error) {
                console.log(error);
            });

            getBOActiveSessionCount().then(function (result) {
                $('#boCount').text(result);
            }).catch(function(error) {
                console.log(error);
            });

            getBOActiveSessions().then(function (result){
                console.log(result);
            });

            getMSActiveSessions().then(function (result){
                console.log(result);
            });
        }

        function getMSActiveSessionCount() {
            return session.call('zimiwebsockets.counter.ms_active_session_count', []);
        }

        function getBOActiveSessionCount() {
            return session.call('zimiwebsockets.counter.bo_active_session_count', []);
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
