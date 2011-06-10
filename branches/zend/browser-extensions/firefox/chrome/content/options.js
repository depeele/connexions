var cOptions = {
    elUser:     null,
    elLogin:    null,
    elRegister: null,
    elSync:     null,
    elFullSync: null,

    strings:    null,

    load: function() {
        cOptions.strings    =
                document.getElementById('connexions-options-strings');
        cOptions.elUser     =
                document.getElementById('connexions-account-user');
        cOptions.elLogin    =
                document.getElementById('connexions-account-login');
        cOptions.elRegister =
                document.getElementById('connexions-account-register');
        cOptions.elSync     =
                document.getElementById('connexions-prefs-sync');
        cOptions.elFullSync =
                document.getElementById('connexions-prefs-fullSync');

        cOptions.elUser.setAttribute('label', 'User here');

        cOptions._bindEvents();

        cDebug.log("cOptions.load(): complete");
    },

    getString: function(name) {
        return (cOptions.strings
                    ? cOptions.strings.getString(name)
                    : null);
    },

    _bindEvents: function() {
        cOptions.elLogin
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): login click");
                    connexions.loadPage(e, 'signin', 'popup');
                 }, false);
        cOptions.elRegister
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): register click");
                    connexions.loadPage(e, 'register', 'popup');
                 }, false);
        cOptions.elSync
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): sync click");
                    connexions.sync();
                 }, false);
        cOptions.elFullSync
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): fullSync click");
                    connexions.sync(true);
                 }, false);
    },

    unload: function() {
    }
};

window.addEventListener("load",   function() { cOptions.load(); },   false);
window.addEventListener("unload", function() { cOptions.unload(); }, false);
