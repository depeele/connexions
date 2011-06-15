/** @file
 *
 *  The options overlay.
 *
 *  Requires: chrome://connexions/connexions.js
 */
CU.import('resource://connexions/debug.js');

function COptions()
{
    this.init();
}

COptions.prototype = {
    os:         CC['@mozilla.org/observer-service;1']
                    .getService(CI.nsIObserverService),

    elUserPre:  null,
    elUser:     null,
    elUserPost: null,

    elLogin:    null,
    elRegister: null,
    elSync:     null,
    elFullSync: null,

    strings:    null,

    init: function() {
    },

    load: function() {
        var self    = this;

        self.strings    =
                document.getElementById('connexions-options-strings');

        self.elUserPre  =
                document.getElementById('connexions-account-user-pre');
        self.elUser     =
                document.getElementById('connexions-account-user');
        self.elUserPost =
                document.getElementById('connexions-account-user-post');

        self.elLogin    =
                document.getElementById('connexions-account-login');
        self.elRegister =
                document.getElementById('connexions-account-register');
        self.elSync     =
                document.getElementById('connexions-prefs-sync');
        self.elFullSync =
                document.getElementById('connexions-prefs-fullSync');

        // Initialize the user area
        self.showUser();

        self._loadObservers();

        // Initiate retrieval of the current user
        var user    = connexions.getUser();
        if (user)   self.showUser(user);
        else
        {
            /* If retrieveUser is successful, it will signal
             * 'connexions.userChanged' which we observe.
            connexions.retrieveUser(function(user) {
                self.showUser(user);
            });
             */
            connexions.retrieveUser();
        }

        self._bindEvents();

        cDebug.log("cOptions.load(): complete");
    },

    unload: function() {
        cDebug.log("cOptions.unload():");

        this._unloadObservers();
    },

    showUser:   function(user) {
        var str;
        if (! user)
        {
            // Hide the user info
            this.elUserPre.hidden  = true;
            this.elUser.hidden     = true;
            this.elUserPost.hidden = true;

            // And set the login/register link text
            str = this.getString('connexions.prefs.account.login');
            this.elLogin.setAttribute('value', str);

            str = this.getString('connexions.prefs.account.register');
            this.elRegister.setAttribute('value', str);
        }
        else
        {
            // Show the user info
            str = user.name +' ('+ user.fullName +')';
            this.elUser.setAttribute('value', str);

            this.elUserPre.hidden  = false;
            this.elUser.hidden     = false;
            this.elUserPost.hidden = false;

            // And adjust the login/register link text
            str = this.getString('connexions.prefs.account.login.diff');
            this.elLogin.setAttribute('value', str);

            str = this.getString('connexions.prefs.account.register.diff');
            this.elRegister.setAttribute('value', str);
        }
    },

    getString: function(name) {
        return (this.strings
                    ? this.strings.getString(name)
                    : null);
    },


    /** @brief  Observer register notification topics.
     *  @param  subject The nsISupports object associated with the
     *                  notification;
     *  @param  topic   The notification topic string;
     *  @param  data    Any additional data;
     */
    observe: function(subject, topic, data) {
        var self    = this;
        cDebug.log('options::observer(): topic[ %s ]',
                   topic);

        switch (topic)
        {
        case 'connexions.userChanged':
            var user    = connexions.getUser();

            // /*
            cDebug.log('options::observe(): conexions.userChanged: '
                        +   'data[ %s ]',
                       cDebug.obj2str(user));
            // */
            self.showUser( user );
            break;
        }
    },

    /************************************************************************
     * "Private" methods
     *
     */
    _bindEvents: function() {
        var self    = this;

        self.elUser
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): user click");
                    connexions.loadPage(e, 'myBookmarks', 'tab');
                 }, false);
        self.elLogin
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): login click");
                    connexions.loadPage(e, 'signin', 'popup', 'close');
                 }, false);
        self.elRegister
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): register click");
                    connexions.loadPage(e, 'register', 'popup', 'close');
                 }, false);
        self.elSync
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): sync click");
                    connexions.sync();
                 }, false);
        self.elFullSync
                .addEventListener('click', function(e) {
                    cDebug.log("cOptions._bindEvents(): fullSync click");
                    connexions.sync(true);
                 }, false);
    },

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "connexions.userChanged", false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "connexions.userChanged");
    }
};

var cOptions;
function cOptions_load()
{
    if (cOptions === undefined)
    {
        cOptions = new COptions();
    }
    cOptions.load();
}
function cOptions_unload()
{
    cOptions.unload();
}

window.addEventListener("load",   cOptions_load,   false);
window.addEventListener("unload", cOptions_unload, false);
