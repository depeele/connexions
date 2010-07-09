/** @file
 *
 *  Provide a simple, global registry that stores data using jQuery.data,
 *  attached to 'document'.
 *
 */
/*jslint nomen: false, laxbreak: true */
/*global jQuery:false, document:false */
(function ($) {
    $.registry = function (name, value) {
        if (value !== undefined)
        {
            // name and value given -- set
            $.data(document, name, value);
        }
        else
        {
            // name, but no value -- get
            return $.data(document, name);
        }
    };

}(jQuery));
