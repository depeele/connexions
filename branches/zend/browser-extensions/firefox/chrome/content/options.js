/** @file
 *
 *  The options overlay.
 *
 *  Requires: chrome://connexions/connexions.js
 *  which makes available:
 *      resource://connexions/debug.js          cDebug
 *      resource://connexions/db.js             cDb
 *      resource://connexions/connexions.js     connexions
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, CU:false, CC:false, CI:false, connexions:false, document:false, window:false */
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
    btnSync:    null,
    btnFullSync:null,

    elStatusBox:null,
    elStatus:   null,
    elProgress: null,

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

        self.elSync      =
                document.getElementById('connexions-prefs-sync');
        self.btnSync     =
                document.getElementById('connexions-prefs-button-sync');
        self.btnFullSync =
                document.getElementById('connexions-prefs-button-fullSync');

        self.elStatusBox =
                document.getElementById('connexions-prefs-sync-status-box');
        self.elStatus    =
                document.getElementById('connexions-prefs-sync-status');
        self.elProgress  =
                document.getElementById('connexions-prefs-sync-progress');

        self._loadObservers();

        // Initialize the user area
        self.showUser( connexions.getUser() );

        // Initialize the last sync information
        self.showLastSync();

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

            // Disable the sync items.
            this.disableAll(this.elSync, 'true');
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

            // Enable the sync items.
            this.disableAll(this.elSync, '');
        }
    },

    showLastSync: function() {
        var self        = this;
        var lastSync    = parseInt( connexions.db.state('lastSync'), 10 );

        cDebug.log('options::showLastSync(): lastSync int[ %s ]', lastSync);

        // Convert the unix date time to a Date instance
        lastSync = new Date (lastSync * 1000);

        cDebug.log('options::showLastSync(): lastSync date[ %s ]',
                   lastSync.toLocaleString());

        var str = self.getString('connexions.prefs.sync.last',
                                 [ lastSync.toLocaleString() ]);

        cDebug.log('options::showLastSync(): status str[ %s ]', str);

        self.elStatus.setAttribute('value', str);
        self.elStatusBox.hidden = false;
        self.elStatus.hidden    = false;
        self.elProgress.hidden  = true;
    },

    /** @brief  Disable or enable the given element and all children.
     *  @param  el      The DOM element to modify.
     */
    disableAll: function(el, state) {
        if (! el)   { return; }

        var nChildren   = el.children.length;
        for (var idex = 0; idex < nChildren; idex++)
        {
            this.disableAll(el.children[idex], state);
        }

        el.setAttribute('disabled', state);
    },

    /** @brief  If our strings (nsIStringBundle) have been set, retrieve and
     *          return the named string.
     *  @param  name        The name of the desired string;
     *  @param  strArray    If provided,
     *                          strings.getFormattedString(name, strArray)
     *                      will be used to retrieve the desired string.
     *                      Otherwise,
     *                          strings.getString(name)
     *                      will be used;
     *
     *  @return The desired string (null if no match);
     */
    getString: function(name, strArray) {
        var str = null;

        if (this.strings)
        {
            try {
                str = (strArray
                        ? this.strings.getFormattedString(name, strArray)
                        : this.strings.getString(name));
            } catch(e) {}
        }

        return str;
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

        case 'connexions.syncBegin':
            cDebug.log('options::observe(): conexions.syncBegin:');

            // Disable the sync items.
            self.disableAll(self.elSync, 'true');

            // Show the status and progressmeter
            self.elStatus.setAttribute('value',
                self.getString('connexions.prefs.sync.working'));
            self.elStatusBox.hidden = false;
            self.elStatus.hidden    = false;
            self.elProgress.hidden  = false;
            break;

        case 'connexions.syncEnd':
            /* if data === true, success
             * otherwise, the error object from the jsonRpc containing:
             *  code and message
             */
            var syncStatus  = connexions.getSyncStatus();
            cDebug.log('options::observe(): conexions.syncEnd: '
                        +   'syncStatus[ %s ]',
                       cDebug.obj2str( connexions.getSyncStatus() ));

            // Hide the progress meter and update the status information
            self.elProgress.hidden = true;
            var strStatus;
            if (syncStatus === true)
            {
                strStatus = self.getString('connexions.prefs.sync.success');
            }
            else
            {
                strStatus = self.getString('connexions.prefs.sync.error')
                          + ' ' + syncStatus.message;
            }
            self.elStatus.setAttribute('value', strStatus);

            // :TODO: Wait for a bit, then update to show the last sync date

            // Enable the sync items.
            self.disableAll(self.elSync, '');

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
        self.btnSync
                .addEventListener('click', function(e) {
                    if (this.getAttribute('disabled') === 'true')
                    {
                        return;
                    }
                    cDebug.log("cOptions._bindEvents(): sync click");
                    connexions.sync();
                 }, false);
        self.btnFullSync
                .addEventListener('click', function(e) {
                    if (this.getAttribute('disabled') === 'true')
                    {
                        return;
                    }
                    cDebug.log("cOptions._bindEvents(): fullSync click");
                    connexions.sync(true);
                 }, false);
    },

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "connexions.userChanged", false);
        this.os.addObserver(this, "connexions.syncBegin",   false);
        this.os.addObserver(this, "connexions.syncEnd",     false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "connexions.userChanged");
        this.os.removeObserver(this, "connexions.syncBegin");
        this.os.removeObserver(this, "connexions.syncEnd");
    }
};

var cOptions;
function cOptions_load()
{
    cDebug.log("cOptions_load");
    if (cOptions === undefined)
    {
        cOptions = new COptions();
    }
    cOptions.load();
}
function cOptions_unload()
{
    cDebug.log("cOptions_unload");
    cOptions.unload();
}

window.addEventListener("load",   cOptions_load,   false);
window.addEventListener("unload", cOptions_unload, false);
