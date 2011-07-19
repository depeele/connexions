/** @file
 *
 *  The statusbar overlay.
 *
 *  Requires: chrome://connexions/connexions.js
 *  which makes available:
 *      resource://connexions/debug.js          cDebug
 *      resource://connexions/db.js             cDb
 *      resource://connexions/connexions.js     connexions
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, CU:false, document:false, window:false, gContextMenu:false */
CU.import('resource://connexions/debug.js');
CU.import('resource://connexions/Observers.js');

function CStatusbar()
{
    this.init();
}

CStatusbar.prototype = {
    strings:        null,

    elSyncBox:      null,
    elSyncProgress: null,
    elSyncCurrent:  null,
    elSyncFinal:    null,

    init: function() {
    },

    load: function() {
        //cDebug.log('CStatusbar::load():');

        var self    = this;

        self.strings =
            document.getElementById('connexions-statusbar-strings');

        self.elSyncBox = 
          document.getElementById('connexions-statusbar-sync');
        self.elSyncProgress = 
          document.getElementById('connexions-statusbar-sync-progress-meter');
        self.elSyncCurrent = 
          document.getElementById('connexions-statusbar-sync-progress-current');
        self.elSyncFinal = 
          document.getElementById('connexions-statusbar-sync-progress-final');

        self._loadObservers();
    },

    click_syncCancel: function(event) {
        event.stopPropagation();

        connexions.syncCancel();
    },

    unload: function() {
        //cDebug.log('CStatusbar::unload():');
        this._unloadObservers();
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
        /*
        cDebug.log("CStatusbar::observe(): topic[ %s ], subject[ %s ]",
                    topic, cDebug.obj2str(subject));
        // */

        var self    = this;
        switch (topic)
        {
        case 'connexions.syncBegin':
            self.elSyncBox.hidden = false;

            self.elSyncProgress.mode  = 'undetermined';
            self.elSyncProgress.value = 0;

            self.elSyncFinal.value   = '';

            var str = self.getString(
                            'connexions.statusbar.sync.progress.fetching');
            self.elSyncCurrent.value  = str;
            self.elSyncCurrent.hidden = false;
            break;

        case 'connexions.syncProgress':
            /* progress SHOULD contain:
             *      total   The total number of items being processed;
             *      current The item currently being processed;
             *
             * and MAY contain:
             *      added   The number of items successfully added;
             *      updated The number of items successfully updated;
             */
            var progress    = subject.progress;

            var str = self.getString(
                            'connexions.statusbar.sync.progress.total',
                            [ progress.total ]);
            self.elSyncFinal.value = str;

            if (progress.added !== undefined)
            {
                str = self.getString(
                            'connexions.statusbar.sync.progress.detail',
                            [ progress.current,
                              progress.added,
                              progress.updated,
                              progress.deleted]);
            }
            else
            {
                str = self.getString(
                            'connexions.statusbar.sync.progress.current',
                            [ progress.current ]);
            }
            self.elSyncCurrent.value  = str;
            self.elSyncCurrent.hidden = false;

            if (progress.total > 0)
            {
                var val = Math.floor((progress.current / progress.total)
                                        * 100);

                self.elSyncProgress.mode  = 'determined';
                self.elSyncProgress.value = val;
            }

            break;

        case 'connexions.syncEnd':
            self.elSyncBox.hidden = true;
            break;
        }
    },

    /*************************************************************************
     * "Private" methods
     *
     */

    /** @brief  Establish our state observers.
     */
    _loadObservers: function() {
        Observers.add('connexions.syncBegin',       this);
        Observers.add('connexions.syncProgress',    this);
        Observers.add('connexions.syncEnd',         this);
    },

    /** @brief  Establish our state observers.
     */
    _unloadObservers: function() {
        Observers.remove('connexions.syncBegin',       this);
        Observers.remove('connexions.syncProgress',    this);
        Observers.remove('connexions.syncEnd',         this);
    }
};

var cStatusbar  = null;
function statusbar_load()
{
    if (cStatusbar === null)
    {
        cStatusbar = new CStatusbar();
    }

    cStatusbar.load();
}

function statusbar_unload()
{
    if (cStatusbar !== null)
    {
        cStatusbar.unload();
    }
}


window.addEventListener("load",   statusbar_load,   false);
window.addEventListener("unload", statusbar_unload, false);

