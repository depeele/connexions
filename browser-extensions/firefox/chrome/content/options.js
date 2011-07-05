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

    elAccountStatus:    null,
    elAccountId:        null,

    btnSignout:          null,
    btnSignin:           null,
    btnRegister:         null,

    elSyncBox:          null,
    elSyncButtons:      null,
    elSyncPeriod:       null,
    btnSyncNow:         null,
    btnSyncFull:        null,
    btnSyncDel:         null,
    btnSyncCancel:      null,

    elStatusBox:        null,
    elStatus:           null,
    elProgress:         null,
    elProgressFinal:    null,
    elProgressCurrent:  null,

    syncProgressReceived:   false,

    strings:    null,

    init: function() {
    },

    load: function() {
        var self    = this;

        self.strings =
            document.getElementById('connexions-options-strings');

        self.elAccountStatus =
            document.getElementById('connexions-prefs-account-status');
        self.elAccountId =
            document.getElementById('connexions-prefs-account-id');

        self.btnSignout =
            document.getElementById('connexions-prefs-account-signout');
        self.btnSignin =
            document.getElementById('connexions-prefs-account-signin');
        self.btnRegister =
            document.getElementById('connexions-prefs-account-register');

        self.elSyncBox =
            document.getElementById('connexions-prefs-sync-box');
        self.elSyncButtons =
            document.getElementById('connexions-prefs-sync-buttons');

        self.elSyncPeriod =
            document.getElementById('connexions-prefs-sync-period');
        self.btnSyncNow =
            document.getElementById('connexions-prefs-sync-now');
        self.btnSyncFull =
            document.getElementById('connexions-prefs-sync-full');
        self.btnSyncDel =
            document.getElementById('connexions-prefs-sync-del');
        self.btnSyncCancel =
            document.getElementById('connexions-prefs-sync-cancel');


        self.elStatusBox =
            document.getElementById('connexions-prefs-sync-status-box');
        self.elStatus    =
            document.getElementById('connexions-prefs-sync-status');
        self.elProgress  =
            document.getElementById('connexions-prefs-sync-progress-meter');
        self.elProgressFinal =
            document.getElementById('connexions-prefs-sync-progress-final');
        self.elProgressCurrent =
            document.getElementById('connexions-prefs-sync-progress-current');

        self._loadObservers();

        // Initialize the user area
        self.showUser( connexions.getUser() );

        // Initialize the sync period information
        self.showSyncPeriod();

        // Initialize the last sync information
        self.showLastSync();

        self._bindEvents();

        //cDebug.log("cOptions.load(): complete");
    },

    unload: function() {
        //cDebug.log("cOptions.unload():");

        this._unloadObservers();
    },

    showUser:   function(user) {
        var str;
        if (! user)
        {
            // Update the status to "Not signed in"
            str = this.getString('connexions.prefs.account.status.notSignedIn');
            this.elAccountStatus.value = str;

            this.elAccountStatus.hidden  = false;

            // Hide the user info
            this.elAccountId.hidden      = true;
            this.btnSignout.hidden       = true;

            // And set the signin/register link text
            str = this.getString('connexions.prefs.account.signin');
            this.btnSignin.value = str;

            str = this.getString('connexions.prefs.account.register');
            this.btnRegister.value = str;

            // Hide (or disable) the sync items.
            this.disableAll(this.elSyncButtons, 'true');
        }
        else
        {
            // Show the user info
            str = this.getString('connexions.prefs.account.status.signedIn');
            this.elAccountStatus.value = str;

            str = user.name +' ('+ user.fullName +')';
            this.elAccountId.value = str;

            this.elAccountStatus.hidden  = false;
            this.elAccountId.hidden      = false;
            this.btnSignout.hidden       = true;    //false;

            // And adjust the signin/register link text
            str = this.getString('connexions.prefs.account.signin.diff');
            this.btnSignin.label = str;

            str = this.getString('connexions.prefs.account.register.diff');
            this.btnRegister.label = str;

            // Show (or enable) the sync items.
            this.disableAll(this.elSyncButtons, '');
        }
    },

    showSyncPeriod: function() {
        var self        = this;
        var syncMins    = connexions.pref('syncMinutes');

        self.elSyncPeriod.value = syncMins;
    },

    showLastSync: function() {
        var self        = this;
        var lastSync    = parseInt( connexions.db.state('lastSync'), 10 );

        //cDebug.log('options::showLastSync(): lastSync int[ %s ]', lastSync);

        // Convert the unix date time to a Date instance
        lastSync = new Date (lastSync * 1000);

        /*
        cDebug.log('options::showLastSync(): lastSync date[ %s ]',
                   lastSync.toLocaleString());
        // */

        var str = self.getString('connexions.prefs.sync.status.last');
        self.elStatus.value = str;

        str = self.getString('connexions.prefs.sync.progress.final',
                                 [ lastSync.toLocaleString() ]);
        self.elProgressFinal.value = str;

        self.elStatusBox.hidden       = false;
        self.elStatus.hidden          = false;
        self.elProgress.hidden        = true;
        self.elProgressFinal.hidden   = false;
        self.elProgressCurrent.hidden = true;
        self.btnSyncCancel.hidden     = true;
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

        el.disabled = state;
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
     *  @param  data    Any additional data
     *                  (JSON-encoded for 'connexions.*' topics);
     */
    observe: function(subject, topic, data) {
        var self    = this;
        if (data !== undefined)
        {
            try {
                data = JSON.parse(data);
            } catch(e) {}
        }

        /*
        cDebug.log('options::observer(): topic[ %s ], data[ %s ]',
                   topic, cDebug.obj2str(data));
        // */

        switch (topic)
        {
        case 'connexions.userChanged':
            self.showUser( data );
            break;

        case 'connexions.syncBegin':
            //cDebug.log('options::observe(): connexions.syncBegin:');
            self.syncProgressReceived = false;

            // Disable the sync items.
            self.disableAll(self.elSyncButtons, 'true');

            // Show the status and progressmeter
            self.elStatus.value           =
                self.getString('connexions.prefs.sync.status.working');
            self.elProgressFinal.value    =
                self.getString('connexions.prefs.sync.progress.fetching');

            self.elStatusBox.hidden       = false;
            self.elStatus.hidden          = false;
            self.elProgress.hidden        = false;
            self.elProgress.mode          = 'undetermined';

            self.elProgressFinal.hidden   = false;
            self.elProgressCurrent.hidden = true;
            self.btnSyncCancel.hidden     = false;
            break;

        case 'connexions.syncProgress':
            self.syncProgressReceived = true;

            /* progress SHOULD contain:
             *      total   The total number of items being processed;
             *      current The item currently being processed;
             *
             * and MAY contain:
             *      added   The number of items successfully added;
             *      updated The number of items successfully updated;
             */
            var progress    = data.progress;

            var str = self.getString('connexions.prefs.sync.progress.total',
                                     [ progress.total ]);
            self.elProgressFinal.value = str;

            if (progress.added !== undefined)
            {
                str = self.getString('connexions.prefs.sync.progress.detail',
                                         [ progress.current,
                                           progress.added,
                                           progress.updated,
                                           progress.deleted ]);
            }
            else
            {
                str = self.getString('connexions.prefs.sync.progress.current',
                                         [ progress.current ]);
            }
            self.elProgressCurrent.value  = str;
            self.elProgressCurrent.hidden = false;

            if (progress.total > 0)
            {
                var val = Math.floor((progress.current / progress.total)
                                        * 100);

                self.elProgress.mode  = 'determined';
                self.elProgress.value = val;
            }

            break;

        case 'connexions.syncEnd':
            /* if data === true, success
             * otherwise, the error object from the jsonRpc containing:
             *  code and message
             */

            // Hide the progress meter and update the status information
            self.elProgress.hidden      = true;
            self.btnSyncCancel.hidden   = true;
            var strStatus;
            if (data.error === null)
            {
                strStatus = self.getString('connexions.prefs.sync.success');
            }
            else
            {
                strStatus = self.getString('connexions.prefs.sync.error',
                                           data.error.message);
            }
            self.elStatus.value = strStatus;

            if (self.syncProgressReceived !== true)
            {
                /* Hide the 'total' and current, which is still indicate an
                 * "indeterminated" state.
                 */
                self.elProgressFinal.hidden   = true;
                self.elProgressCurrent.hidden = true;
            }
            else
            {
                /* Leave the 'total' and 'current' for now so the user can see
                 * how many bookmarks were updated.
                 */
            }

            // :TODO: Wait for a bit, then update to show the last sync date

            // Enable the sync items.
            self.disableAll(self.elSyncButtons, '');

            break;
        }
    },

    /************************************************************************
     * "Private" methods
     *
     */
    _bindEvents: function() {
        var self    = this;

        self.elAccountId
                .addEventListener('click', function(e) {
                    //cDebug.log("cOptions._bindEvents(): user click");
                    connexions.loadPage(e, 'myBookmarks', 'tab');
                 }, false);
        self.btnSignout
                .addEventListener('click', function(e) {
                    //cDebug.log("cOptions._bindEvents(): signout click");
                    //connexions.loadPage(e, 'signin', 'popup', 'close');
                 }, false);
        self.btnSignin
                .addEventListener('click', function(e) {
                    //cDebug.log("cOptions._bindEvents(): signin click");
                    connexions.loadPage(e, 'signin', 'popup', 'close');
                 }, false);
        self.btnRegister
                .addEventListener('click', function(e) {
                    //cDebug.log("cOptions._bindEvents(): register click");
                    connexions.loadPage(e, 'register', 'popup', 'close');
                 }, false);

        self.elSyncPeriod
                .addEventListener('change', function(e) {
                    if (this.disabled === 'true')
                    {
                        return;
                    }
                    cDebug.log("cOptions._bindEvents(): syncPeriod changed");

                    var period  = parseInt(self.elSyncPeriod.value, 10);
                    connexions.pref('syncMinutes', period);
                    //connexions._updatePeriodicSync();
                 }, false);
        self.btnSyncNow
                .addEventListener('click', function(e) {
                    if (this.disabled === 'true')
                    {
                        return;
                    }
                    //cDebug.log("cOptions._bindEvents(): syncNow click");
                    connexions.sync();
                 }, false);
        self.btnSyncFull
                .addEventListener('click', function(e) {
                    if (this.disabled === 'true')
                    {
                        return;
                    }
                    //cDebug.log("cOptions._bindEvents(): syncFull click");
                    connexions.sync(true);
                 }, false);
        self.btnSyncDel
                .addEventListener('click', function(e) {
                    if (this.disabled === 'true')
                    {
                        return;
                    }
                    //cDebug.log("cOptions._bindEvents(): syncDel click");
                    connexions.delBookmarks();
                 }, false);
        self.btnSyncCancel
                .addEventListener('click', function(e) {
                    if (this.disabled === 'true')
                    {
                        return;
                    }
                    //cDebug.log("cOptions._bindEvents(): syncCancel click");
                    connexions.syncCancel();
                 }, false);
    },

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        this.os.addObserver(this, "connexions.userChanged",  false);
        this.os.addObserver(this, "connexions.syncBegin",    false);
        this.os.addObserver(this, "connexions.syncProgress", false);
        this.os.addObserver(this, "connexions.syncEnd",      false);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        this.os.removeObserver(this, "connexions.userChanged");
        this.os.removeObserver(this, "connexions.syncProgress");
        this.os.removeObserver(this, "connexions.syncEnd");
    }
};

var cOptions;
function cOptions_load()
{
    //cDebug.log("cOptions_load");
    if (cOptions === undefined)
    {
        cOptions = new COptions();
    }
    cOptions.load();
}
function cOptions_unload()
{
    //cDebug.log("cOptions_unload");
    cOptions.unload();
}

window.addEventListener("load",   cOptions_load,   false);
window.addEventListener("unload", cOptions_unload, false);
