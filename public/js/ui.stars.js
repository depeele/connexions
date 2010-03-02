/*!
 * jQuery UI Stars v2.1.1
 * http://plugins.jquery.com/project/Star_Rating_widget
 *
 * Copyright (c) 2009 Orkan (orkans@gmail.com)
 * Dual licensed under the MIT and GPL licenses.
 * http://docs.jquery.com/License
 *
 * $Rev: 114 $
 * $Date:: 2009-06-12 #$
 * $Build: 32 (2009-06-12)
 *
 * Take control of pre-assembled HTML:
 *  <div >
 *    <input type='hidden' name='rating' value='...' />
 *    <div class='ui-stars ui-stars-cancel ...'><a ..></a></div>
 *    <div class='ui-stars ui-stars-star ...'><a ..></a></div>
 *    <div class='ui-stars ui-stars-star ...'><a ..></a></div>
 *    ...
 *  </div>
 *
 * Depends:
 *  ui.core.js
 *
 */
(function($) {

$.widget("ui.stars", {
  version: "2.1.1a",
  options: {
    // Defaults
    inputType: "div", // radio|select
    split: 0,
    disabled: false,
    cancelTitle: "Cancel Rating",
    cancelValue: 0,
    cancelShow: true,
    oneVoteOnly: false,
    showTitles: false,
    captionEl: null,
    callback: null, // function(ui, type, value, event)

    /*
     * CSS classes
     */
    starWidth: 16,
    baseClass:   'ui-stars',            // Included for all star/cancel items
    cancelClass: 'ui-stars-cancel',
    starClass: 'ui-stars-star',
    starOnClass: 'ui-stars-star-on',
    starHoverClass: 'ui-stars-star-hover',
    starDisabledClass: 'ui-stars-star-disabled',
    cancelHoverClass: 'ui-stars-cancel-hover',
    cancelDisabledClass: 'ui-stars-cancel-disabled'
  },

  _create: function() {
    var self = this, o = this.options, id = 0;

    //this.$stars  = $('.'+o.baseClass,   this.element);
    this.$stars  = $('.'+o.starClass,   this.element);
    this.$cancel = $('.'+o.cancelClass, this.element);
    this.$input  = $('input[type=hidden]:first', this.element);

    // How many Stars and how many are 'on'?
    o.items = this.$stars.filter('.'+o.starClass).length;
    o.value = this.$stars.filter('.'+o.starOnClass).length; // - 1;
    if (o.value > 0) {
        o.checked = o.defaultValue = o.value;
    } else {
        o.value = o.cancelValue;
    }

    if (o.disabled)
        this.$cancel.addClass(o.cancelDisabledClass);

    //o.cancelShow &= !o.disabled && !o.oneVoteOnly;
    o.cancelShow &= !o.oneVoteOnly;
    //o.cancelShow && this.element.append(this.$cancel);

    /*
     * Attach stars event handler
     */
    this.$stars.bind("click.stars", function(e) {
      if(!o.forceSelect && o.disabled) return false;

      var i = self.$stars.index(this);
      o.checked = i;
      o.value   = i + 1;
      o.title   = $(this).find('a').attr('title');

      self.$input.val(o.value);

      fillTo(o.checked, false);
      self._disableCancel();

      !o.forceSelect && self.callback(e, "star");
    })
    .bind("mouseover.stars", function() {
      if(o.disabled) return false;
      var i = self.$stars.index(this);
      fillTo(i, true);
    })
    .bind("mouseout.stars", function() {
      if(o.disabled) return false;
      fillTo(o.checked, false);
    });


    /*
     * Attach cancel event handler
     */
    this.$cancel.bind("click.stars", function(e) {
      if(!o.forceSelect && (o.disabled || o.value == o.cancelValue))
        return false;

      o.checked = -1;
      o.value   = o.cancelValue;

      self.$input.val(o.cancelValue);

      fillNone();
      self._disableCancel();

      !o.forceSelect && self.callback(e, "cancel");
    })
    .bind("mouseover.stars", function() {
      if(self._disableCancel()) return false;
      self.$cancel.addClass(o.cancelHoverClass);
      fillNone();
      self._showCap(o.cancelTitle);
    })
    .bind("mouseout.stars", function() {
      if(self._disableCancel()) return false;
      self.$cancel.removeClass(o.cancelHoverClass);
      self.$stars.triggerHandler("mouseout.stars");
    });

    /*
     * Clean up to avoid memory leaks in certain versions of IE 6
     */
    $(window).unload(function(){
      self.$cancel.unbind(".stars");
      self.$stars.unbind(".stars");
      self.$stars = self.$cancel = null;
    });


    /*
     * Star selection helpers
     */
    function fillTo(index, hover) {
      if(index >= 0) {
        var addClass = hover ? o.starHoverClass : o.starOnClass;
        var remClass = hover ? o.starOnClass    : o.starHoverClass;

        self.$stars.eq(index)
                      .removeClass(remClass)
                      .addClass(addClass)
                    .prevAll("." + o.starClass)
                      .removeClass(remClass)
                      .addClass(addClass);
        //             .end()
        //            .end()
        self.$stars.eq(index)
                    .nextAll("." + o.starClass)
                     .removeClass(o.starHoverClass + " " + o.starOnClass);

        self._showCap(self.$stars.eq(index).find('a').attr('title'));
      }
      else fillNone();
    };
    function fillNone() {
      self.$stars.removeClass(o.starOnClass + " " + o.starHoverClass);
      self._showCap("");
    };


    /*
     * Finally, set up the Stars
     */
    this.select(o.value);
    o.disabled && this.disable();

  },

  /*
   * Private functions
   */
  _disableCancel: function() {
    var o        = this.options,
        disabled = o.disabled || o.oneVoteOnly || (o.value == o.cancelValue);

    if(disabled)
        this.$cancel.removeClass(o.cancelHoverClass)
                    .addClass(o.cancelDisabledClass);
    else
        this.$cancel.removeClass(o.cancelDisabledClass);

    this.$cancel.css("opacity", disabled ? 0.5 : 1);
    return disabled;
  },
  _disableAll: function() {
    var o = this.options;
    this._disableCancel();
    if(o.disabled)  this.$stars.filter("div").addClass(o.starDisabledClass);
    else            this.$stars.filter("div").removeClass(o.starDisabledClass);
  },
  _showCap: function(s) {
    var o = this.options;
    if(o.captionEl) o.captionEl.text(s);
  },

  /*
   * Public functions
   */
  value: function() {
    return this.options.value;
  },
  select: function(val) {
    var o = this.options,
        e = (val == o.cancelValue)
                ? this.$cancel : this.$stars.eq(val - 1);

    o.forceSelect = true;
    e.triggerHandler("click.stars");
    o.forceSelect = false;
  },
  selectID: function(id) {
    var o = this.options, e = (id == -1) ? this.$cancel : this.$stars.eq(id);
    o.forceSelect = true;
    e.triggerHandler("click.stars");
    o.forceSelect = false;
  },
  enable: function() {
    this.options.disabled = false;
    this._disableAll();
  },
  disable: function() {
    this.options.disabled = true;
    this._disableAll();
  },
  destroy: function() {
    this.$cancel.unbind(".stars");
    this.$stars.unbind(".stars");
    this.element.unbind(".stars").removeData("stars");
  },
  callback: function(e, type) {
    var o = this.options;
    o.callback && o.callback(this, type, o.value, e);
    o.oneVoteOnly && !o.disabled && this.disable();
  }
});

})(jQuery);
