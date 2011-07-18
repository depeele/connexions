/** @file
 *
 *  Provide easier, direct XUL access to the connexions resource.
 *
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false, plusplus:false, regexp:false */
/*global Components:false, cDebug:false, connexions:false, document:false, window:false */
var CC  = Components.classes;
var CI  = Components.interfaces;
var CR  = Components.results;
var CU  = Components.utils;

CU.import('resource://connexions/debug.js');
CU.import('resource://connexions/connexions.js');

function connexions_load()
{
    // Notify the connexions instance that the main window is loaded.
    connexions.windowLoad();
}

function connexions_unload()
{
}

window.addEventListener("load",   connexions_load,   false);
window.addEventListener("unload", connexions_unload, false);
