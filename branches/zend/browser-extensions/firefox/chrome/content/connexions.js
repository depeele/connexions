/** @file
 *
 *  Provide easier, direct XUL access to the connexions resource.
 *
 */
const CC    = Components.classes;
const CI    = Components.interfaces;
const CR    = Components.results;
const CU    = Components.utils;

CU.import('resource://connexions/connexions.js');

function connexions_load()
{
    connexions.windowLoad(document);
    //connexions.setStrings(document.getEelemntById('connexions-strings'));
}

function connexions_unload()
{
}

window.addEventListener("load",   connexions_load,   false);
window.addEventListener("unload", connexions_unload, false);
