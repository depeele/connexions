/** @file
 *
 *  A jQuery-UI / flot-based graphical timeline capable of presenting timeline
 *  data generated by connexions.
 *
 *  The target DOM element MAY contain a 'div.timeline-plot',
 *  'div.timeline-legend', and/or 'div.timeline-annotation'.  If it does not,
 *  these DOM elements will be added.
 */
/*jslint nomen:false, laxbreak:true, white:false, onevar:false */
/*global jQuery:false, window:false */
(function($) {

function leftPad(val)
{
    val = "" + val;
    return val.length === 1 ? "0" + val : val;
}

var numericRe = /^[0-9]+(\.[0-9]*)?$/;
              //   year      month           day
var datePat   = '^([0-9]{4})(0[0-9]|1[0-2])?([0-2][0-9]|3[01])?'
              //   hour               minute       second
              + '([0-1][0-9]|2[0-3])?([0-5][0-9])?([0-5][0-9])?$';
var dateRe    = new RegExp(datePat);

$.widget('connexions.timeline', {
    version:    '0.0.1',
    options:    {
        // Defaults
        xDataHint:      null,   // hour | day-of-week |
                                //  day | week | month | year
                                //
        css:            null,   // Additional CSS class(es) to apply to the
                                // primary DOM element
        annotation:     null,   // Any annotation to include for this timtline
        rawData:        null,   // Raw, connexions timeline data to present
        data:           [],     // Initial, empty data

        width:          null,   // The width of the timeline plot area
        height:         null,   // The height of the timeline plot area
        hwRatio:        9/16,   // Ratio of height to width
                                // (used if 'height' is not specified)


        hideLegend:     false,  // Hide the legend?
        valueInLegend:  true,   // Show the y hover value(s) in the series
                                // legend (hideLegend should be true);
        valueInTips:    false,  // Show the y hover value(s) in series graph
        replaceLegend:  false,  // Should the data label completely replace
                                // any existing legend text when the value
                                // is being presented? [ false ];

        /* General Json-RPC information:
         *  {version:   Json-RPC version,
         *   target:    URL of the Json-RPC endpoint,
         *   transport: 'POST' | 'GET'
         *  }
         *
         * If not provided, 'version', 'target', and 'transport' are
         * initialized from:
         *      $.registry('api').jsonRpc
         *
         * which is initialized from
         *      application/configs/application.ini:api
         * via
         *      application/layout/header.phtml
         */
        jsonRpc:    null,
        rpcMethod:  'bookmark.getTimeline',
        rpcParams:  null,   /* Any RPC parameter required by 'rpcMethod:
                             *  {
                             *      'tags':     Context-restricting tags,
                             *      'group':    Timeline grouping indicator,
                             *  }
                             */

        // DataType value->label tables
        hours:      [ '12a', ' 1a', ' 2a', ' 3a', ' 4a', ' 5a',
                      ' 6a', ' 7a', ' 8a', ' 9a', '10a', '11a',
                      '12p', ' 1p', ' 2p', ' 3p', ' 4p', ' 5p',
                      ' 6p', ' 7p', ' 8p', ' 9p', '10p', '11p' ],
        months:     [ 'January',   'Febrary', 'March',    'April',
                      'May',       'June',    'July',     'August',
                      'September', 'October', 'November', 'December' ],
        days:       [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ],

        // Timeline grouping indicator to xDataHint format mapping
        groupingFmt:{
            // Straight Timelines
            'YM':   'fmt:%Y %b',        // Year, Month
            'YMD':  'fmt:%Y.%M.%D',     // Year, Month, Day
            'MD':   'fmt:%b %D',        // Month, Day
            'MH':   'fmt:%b %h',        // Month, Hour
            'MDH':  'fmt:%b %D %h',     // Month, Day, Hour

            // Series Timelines (by Year)
            'Y:M':  'fmt:%b',           // Month
            'Y:D':  'fmt:%D',           // Day (of month)
            'Y:d':  'fmt:%a',           // Day (of week)
            'Y:H':  'fmt:%h',           // Hour

            // Series Timelines (by Month)
            'M:D':  'fmt:%D',           // Day (of month)
            'M:d':  'fmt:%a',           // Day (of week)
            'M:H':  'fmt:%h',           // Hour

            // Series Timelines (by Week)
            'w:d':  'fmt:%a',           // Day (of week)
            'w:H':  'fmt:%h',           // Hour

            // Series Timelines (by Day-of-Month)
            'D:H':  'fmt:%h',           // Hour

            // Series Timelines (by Day-of-Week)
            'd:H':  'fmt:%h',           // Hour
        }
    },

    /************************
     * Private methods
     *
     */
    _init: function() {
        var self        = this;
        var opts        = self.options;

        /********************************
         * Initialize jsonRpc
         *
         */
        if ( $.isFunction($.registry) )
        {
            if (opts.jsonRpc === null)
            {
                var api = $.registry('api');
                if (api && api.jsonRpc)
                {
                    opts.jsonRpc = $.extend({}, api.jsonRpc, opts.jsonRpc);
                }
            }
        }

        /********************************
         * Remember initial position
         * settings and add appropriate
         * CSS classes.
         *
         */
        self.element.data('orig-position', self.element.css('position'));
        self.element.css('position', 'relative')
                    .addClass('ui-timeline');

        if ( opts.hideLegend !== true )
        {
            self.element.addClass('ui-timeline-labeled');
        }

        if ($.type(opts.css) === 'string')
        {
            self.element.addClass( opts.css );
        }


        /********************************
         * Locate/create our primary
         * pieces.
         *
         */
        self.$legend = self.element.find('.timeline-legend');
        if ( (self.$legend.length < 1) && (opts.hideLegend !== true) )
        {
            // Append a legend container
            self.$legend = $('<div class="timeline-legend"></div>');

            self.$legend.data('remove-on-destroy', true);
            self.element.append(self.$legend);
        }
        else if ( opts.hideLegend === true )
        {
            self.$legend.hide();
        }

        self.$timeline = self.element.find('.timeline-plot');
        if (self.$timeline.length < 1)
        {
            // Append a plot container
            self.$timeline = $('<div class="timeline-plot"></div>');
            self.$timeline.data('remove-on-destroy', true);
            self.element.append(self.$timeline);
        }

        self.$controls = self.element.find('.timeline-controls');
        self.$grouping = self.$controls
                                .find(':input[name="timeline.grouping"]');

        if (opts.annotation)
        {
            self.$annotation = self.element.find('.timeline-annotation');
            if (self.$annotation.length < 1)
            {
                // Append a annotation container
                self.$annotation = $('<div class="timeline-annotation"></div>');

                self.$annotation.data('remove-on-destroy', true);
                self.element.append(self.$annotation);
            }

            self.$annotation.html(opts.annotation);
        }

        self.hlTimer = null;

        // Ensure that our plot area has a non-zero height OR width
        var width   = self.$timeline.width();
        var height  = self.$timeline.height();

        if (opts.width !== null)
        {
            self.$timeline.css('width', opts.width);
            width = self.$timeline.width();
        }
        if (opts.height !== null)
        {
            self.$timeline.css('height', opts.height);
            height = self.$timeline.height();
        }

        if (width < 1)
        {
            // Force to the width of the container
            self.$timeline.width( self.element.width() );
            width = self.$timeline.width();
        }

        if (height < 1)
        {
            // Force the height to a ratio of the width
            height = width * opts.hwRatio
            self.$timeline.css('height', height);
        }

        // Interaction events
        self._bindEvents();


        window.setTimeout(function() { self._createPlot(); }, 50);
    },

    _bindEvents: function() {
        var self        = this;
        var opts        = self.options;

        // Handle 'plothover' events
        self.$timeline.bind('plothover', function(e, pos, item) {
            if (! self.hlTimer)
            {
                self.hlTimer = window.setTimeout(function() {
                                                    self._updateHighlights(pos);
                                                 }, 50);
            }
        });

        self.$controls.delegate('input,select', 'change', function(e) {
            self._reload( self.$grouping.val() );
        });
    },

    /** @brief  Asynchronously (re)load the data for the presented timeline.
     *  @param  grouping    The new grouping value;
     *
     *  Use this.options:
     *      jsonRpc     the primary Json-RPC information;
     *      rpcMethod   the Json-RPC method;
     *      rpcParams   additional Json-RPC method parameters;
     */
    _reload: function(grouping) {
        var self    = this;
        var opts    = self.options;

        var params  = opts.rpcParams;
        params.grouping = grouping;

        // What's the xDataHint based upon 'grouping'?
        var fmt = opts.groupingFmt[ grouping ];
        if (fmt !== undefined)
        {
            opts.xDataHint = fmt;
        }


        self.element.mask();
        $.jsonRpc(opts.jsonRpc, opts.rpcMethod, params, {
            success: function(data) {
                if ( (! data) || (data.error !== null) )
                {
                    $.notify({
                        title:  'Timeline Update failed',
                        text:   '<p class="error">'
                              +  (data ? data.error.message : '')
                              + '</p>'
                    });
                    return;
                }

                opts.data = self._convertData(data.result);
                self._draw();
            },
            error: function(req, textStatus, err) {
                $.notify({
                    title:  'Timeline Update failed',
                    text:   '<p class="error">'
                          +  textStatus
                          + '</p>'
                });
            },
            complete: function(req, textStatus) {
                self.element.unmask();
            }
        });

    },

    /** @brief  Use this.options to generate/update the current timeline.
     *  
     *  Use this.options:
     *      rpcParams.grouping  to determine the xDataHint to use;
     *      data                the (converted) plot data;
     */
    _draw: function() {
        var self    = this;
        var opts    = self.options;

        var height  = self.$timeline.height();
        var width   = self.$timeline.width();
        if ((height === 0) || (width === 0))
        {
            if (height === 0)   height
        }

        self.$plot  = $.plot(self.$timeline, opts.data, self.flotOpts);

        // Cache the size of the plot box/area
        self.plotBox        = self.$plot.getPlotOffset();
        self.plotBox.width  = self.$plot.width();
        self.plotBox.height = self.$plot.height();
        self.plotBox.right  = self.plotBox.left + self.plotBox.width;
        self.plotBox.bottom = self.plotBox.top  + self.plotBox.height;

        /*
        // Position the legend to the right of the plot box/area
        self.$legend.css({
            position:   'absolute',
            top:        self.plotBox.top   - 5,
            left:       self.plotBox.right + 10
        });
        // */
        self.$legends = self.$legend.find('.legendLabel');

        // Wrap the current content of each legend item in 'timeline-text'
        self.$legends.each(function() {
            var $legend = $(this);

            if ($legend.find('.timeline-text').length < 1)
            {
                $legend.html(  '<span class="timeline-text">'
                             +   $legend.html()
                             + '</span>');
            }
        });
    },

    _createPlot: function() {
        var self            = this;
        var opts            = self.options;
        var flotDefaults    = {
            grid:       { hoverable:true, autoHighlight:false },
            points:     { show:true },
            lines:      { show:true },
            legend:     { container: self.$legend },
            xaxis:      {
                tickFormatter: function(val,data) {
                    return self._xTickFormatter(val, data);
                }
            }
        };

        // Default flot options
        self.flotOpts   = $.extend(true, {}, flotDefaults, opts.flot);

        if (opts.rpcParams !== null)
        {
            var fmt = opts.groupingFmt[ opts.rpcParams.grouping ];

            if (fmt !== undefined)
            {
                opts.xDataHint = fmt;
            }
        }

        // Convert any raw data
        if ( $.isArray(opts.rawData) || $.isPlainObject(opts.rawData) )
        {
            opts.data   = self._convertData(opts.rawData);
        }

        self._draw();
    },

    /** @brief  Convert incoming, raw, connexions timeline data into a form
     *          usable by flot.
     *  @param  rawData The raw, connexions timeline data to convert.
     *
     *  @return An array of data series suitable for flot.
     */
    _convertData: function(rawData) {
        var self    = this;
        var opts    = self.options;
        var data    = [];

        opts.xDataType = undefined;
        opts.xDateFmt  = undefined;

        $.each(rawData, function(key, vals) {
            var info    = { label: key, data: [] };

            $.each(vals, function(x, y) {
                y = (numericRe.test(y) ? parseInt(y, 10) : y);

                var match   = dateRe.exec(x);
                if (match !== null)
                {
                    match[2] = (match[2] === undefined ? 0 : match[2] - 1);
                    match[3] = (match[3] === undefined ? 1 : match[3]);
                    match[4] = (match[4] === undefined ? 0 : match[4]);
                    match[5] = (match[5] === undefined ? 0 : match[5]);
                    match[6] = (match[6] === undefined ? 0 : match[6]);

                    var newX    = new Date(match[1], match[2], match[3],
                                           match[4], match[5], match[6]);

                    info.data.push([newX, y]);

                    opts.xDataType     = 'date';
                    if (($.type(opts.xDataHint) === 'string') &&
                        (opts.xDataHint.substr(0,4) === 'fmt:'))
                    {
                        opts.xDateFmt  = opts.xDataHint.substr(4);
                        opts.xDataHint = 'fmt';
                    }
                }
                else
                {
                    x = (numericRe.test( x )
                            ? parseInt(x, 10)
                            : x);
                    info.data[x] = [x, y];
                }
            });

            data.push(info);
        });

        return data;
    },

    /** @brief  Given a Date instance and format string, generate a string
     *          representation of the Date.
     *  @param  date    The Date instance;
     *  @param  fmt     The format string;
     *                      %Y  - Year              ( 2010 )
     *                      %m  - Month             ( 01 - 12 )
     *                      %d  - Day               ( 01 - 31 )
     *                      %w  - Day-of-week       ( 0  -  6 )
     *                      %H  - Hour              ( 00 - 23 )
     *                      %M  - Minute            ( 00 - 59 )
     *                      %S  - Second            ( 00 - 59 )
     *                      %z  - Timezone Offset
     *
     *                      %B  - Month Name        ( January - December )
     *                      %b  - Month Name        ( Jan     - Dec      )
     *                      
     *                      %A  - Day-of-week Name  ( Sunday  - Saturday )
     *                      %a  - Day-of-week Name  ( Su      - Sa       )
     *
     *                      %h  - Hour Name         ( 12a     - 11p      )
     *
     *  @return A string representation of the Date.
     */
    _formatDate: function(date, fmt) {
        var self    = this;
        var opts    = self.options;

        if (fmt === undefined)
        {
            fmt = opts.xDateFmt;
        }

        var isFmt   = false;
        var res     = [];
        for (var idex = 0; idex < fmt.length; idex++)
        {
            var fmtChar = fmt.charAt(idex);
            if (isFmt)
            {
                isFmt = false;
                switch (fmtChar)
                {
                case 'Y':   // Year
                    fmtChar = date.getFullYear();
                    break;
    
                case 'm':   // Month (01-12)
                    fmtChar = leftPad(date.getMonth() + 1);
                    break;

                case 'd':   // Day   (01-31)
                    fmtChar = leftPad(date.getDate());
                    break;

                case 'w':   // Day-of-week (0 - 6)
                    fmtChar = date.getDay();
                    break;

                case 'H':   // Hours (00-23)
                    fmtChar = leftPad(date.getHours());
                    break;

                case 'M':   // Minutes (00-59)
                    fmtChar = leftPad(date.getMinutes());
                    break;

                case 'S':   // Seconds (00-59)
                    fmtChar = leftPad(date.getSeconds());
                    break;

                case 'z':   // Timezone offset
                    fmtChar = date.getTimezoneOffset();
                    break;

                // Number to String mappings
                case 'B':   // Month (January - December)
                    fmtChar = opts.months[date.getMonth()];
                    break;
    
                case 'b':   // Month (Jan - Dec)
                    fmtChar = opts.months[date.getMonth()].substr(0,3);
                    break;
    
                case 'A':   // Day-of-week   (Sunday - Saturday)
                    fmtChar = opts.days[date.getDay()];
                    break;

                case 'a':   // Day-of-week   (Su - Sa)
                    fmtChar = opts.days[date.getDay()].substr(0,2);
                    break;

                case 'h':   // Hours (12a-11p)
                    fmtChar = opts.hours[date.getHours()];
                    break;

                default:
                    break;
                }

                res.push(fmtChar);
            }
            else if (fmtChar === '%')
            {
                isFmt = true;
            }
            else
            {
                res.push(fmtChar);
            }
        }
    
        return res.join('');
    },

    _xTickFormatter: function(val, data) {
        var self        = this;
        var opts        = self.options;

        if (opts.xDataType === 'date')
        {
            val = new Date( val );
        }
        
        switch (opts.xDataHint)
        {
        case 'hour':
            if (val instanceof Date)
            {
                val = val.getHours();
            }
            val = opts.hours[ val ];
            break;

        case 'day-of-week':
            if (val instanceof Date)
            {
                val = val.getDay();
            }
            val = opts.days[ val ];
            break;

        case 'month':
            if (val instanceof Date)
            {
                val = val.getMonth() + 1;
            }
            val = opts.months[ (val > 0 ? val - 1 : val) ];
            break;

        case 'mon':
            if (val instanceof Date)
            {
                val = val.getMonth() + 1;
            }
            val = opts.months[ (val > 0 ? val - 1 : val) ];
            
            if (val !== undefined)
            {
                val = val.substr(0,3);
            }
            break;

        case 'day':
            if (val instanceof Date)
            {
                val = val.getDate();
            }
            break;

        case 'year':
            if (val instanceof Date)
            {
                val = val.getFullYear();
            }
            break;

        case 'fmt':
            if (val instanceof Date)
            {
                val = self._formatDate(val);
            }
            break;

        // No special formatting
        case 'week':
        }

        return (val === undefined ? '' : val);
    },

    /** @brief  Present a "tip" for the given series and point
     *  @param  idex    The series index;
     *  @param  series  The series
     *  @param  p       The point
     */
    _showTip:   function(idex, series, p) {
        if (p === null)
        {
            return;
        }

        var self        = this;
        var opts        = self.options;
        var hlRadius    = (series.points.radius + series.points.lineWidth);
        var itemPos     = {
            top:    series.yaxis.p2c(p[1]),
            left:   series.xaxis.p2c(p[0])
        };
        //var x           = series.xaxis.tickFormatter(p[0], series.xaxis);
        var y           = series.yaxis.tickFormatter(p[1], series.yaxis);
        var str         = y;    //x +': '+ y;

        var tip         = '<div class="timeline-tooltip">'
                        +  str
                        + '</div>';
        var yaxis       = series.yaxis;
        var $tip        = $(tip).css({
                            position:           'absolute',
                            top:                itemPos.top  - hlRadius - 5,
                            left:               itemPos.left + (hlRadius*2)
                                                             + 5,
                            'text-align':       'left',
                            /*
                            'font-size':        yaxis.font.size +'px',
                            'font-family':      yaxis.font.family,
                            'font-weight':      yaxis.font.weight,
                            */
                            width:              yaxis.labelWidth +'px',
                            height:             yaxis.labelHeight +'px',
                            color:              series.yaxis.options.color
                        });
        self.$timeline.append($tip);

        /* Adjust the position of the tip to be within the bounds of the
         * plot
         */
        var tipBox      = $tip.position();
        tipBox.width    = $tip.outerWidth();
        tipBox.height   = $tip.outerHeight();
        tipBox.left    += tipBox.width;
        tipBox.right    = tipBox.left + tipBox.width;
        tipBox.bottom   = tipBox.top  + tipBox.height;

        if (tipBox.top    < self.plotBox.top)
        {
            tipBox.top = itemPos.top + hlRadius + hlRadius + 5;
        }
        else if (tipBox.bottom > self.plotBox.bottom)
        {
            tipBox.top = self.plotBox.bottom - tipBox.height - 5;
        }

        if (tipBox.left   < self.plotBox.left)
        {
            tipBox.left = self.plotBox.left + 5;
        }
        else if (tipBox.right > self.plotBox.right)
        {
            tipBox.left = self.plotBox.right - tipBox.width - 10;
        }

        $tip.css({
            top:    tipBox.top,
            left:   tipBox.left
        });
    },

    /** @brief  Update the legend to include the current y value for the
     *          specified series.
     *  @param  idex    The series index;
     *  @param  series  The series;
     *  @param  p       The point;
     */
    _updateLegend: function(idex, series, p) {
        if (p === null)
        {
            return;
        }

        var self    = this;
        var opts    = self.options;
        var $legend = self.$legends.eq(idex);
        var $stat   = $legend.find('.timeline-stat');
        var x       = series.xaxis.tickFormatter(p[0], series.xaxis);
        var y       = series.yaxis.tickFormatter(p[1], series.yaxis);
        var str     = (opts.replaceLegend ? '' : ': ') + x +': '+ y;

        if ($stat.length > 0)
        {
            $stat.text( str )
                 .show();
        }
        else
        {
            var html    = '<span class="timeline-stat">'
                        +  str
                        + '</span>';

            $legend.append(  html );

        }

        if (opts.replaceLegend)
        {
            $legend.find('.timeline-text').hide();
        }
    },

    /** @brief  Update the highlighted points based upon the reported mouse
     *          position.
     *  @param  pos     The reported mouse position;
     */
    _updateHighlights: function(pos) {
        var self    = this;
        var opts    = self.options;

        // Clear all highlights, tips, and label stats and tips
        self.$plot.unhighlight();
        self.$timeline.find('.timeline-tooltip').remove();
        self.$legends
                .find('.timeline-stat').hide()
                .end()
                .find('.timeline-text').show();

        var axes    = self.$plot.getAxes();
        if ( (pos.x < axes.xaxis.min) || (pos.x > axes.xaxis.max) ||
             (pos.y < axes.yaxis.min) || (pos.y > axes.yaxis.max) )
        {
            self.hlTimer = null;
            return;
        }

        var idex, jdex, dataset = self.$plot.getData();
        for (idex = 0; idex < dataset.length; idex++)
        {
            var series  = dataset[idex];
            var p       = null;
            var p1      = null;
            var p2      = null;

            // Locate the items on either side of the mouse's x position
            for (jdex = 0; jdex < series.data.length; jdex++)
            {
                if (series.data[jdex] === undefined)
                {
                    continue;
                }

                var pt  = series.data[jdex];
                if ( pt[0] > pos.x )
                {
                    if ((p2 === null) || (pt[0] < p2[0]))
                    {
                        p2 = pt;
                    }
                }
                else if ( pt[0] < pos.x )
                {
                    if ((p1 === null) || (pt[0] > p1[0]))
                    {
                        p1  = pt;
                    }
                }
            }

            // Choose the closest point
            if (p1 === null)
            {
                p = p2;
            }
            else if (p2 === null)
            {
                p = p1;
            }
            else
            {
                if (Math.abs(pos.x - p1[0]) < Math.abs(pos.x - p2[0]))
                {
                    p = p1;
                }
                else
                {
                    p = p2;
                }
            }

            // Highlight the point
            self.$plot.highlight(series, p, false);

            // Present point information in legend and/or tips
            if (opts.valueInLegend)
            {
                self._updateLegend(idex, series, p);
            }
            if (opts.valueInTips)
            {
                self._showTip(idex, series, p);
            }
        }

        self.hlTimer = null;
    },

    /************************
     * Public methods
     *
     */
    destroy: function() {
        var self        = this;
        var opts        = self.options;

        // Remove added items
        self.$plot.shutdown();
        self.$timeline.find('.timeline-tooltip').remove();

        if (self.$annotation.data('remove-on-destroy') === true)
        {
            self.$annotation.remove();
        }

        if (self.$legend.data('remove-on-destroy') === true)
        {
            self.$legend.remove();
        }
        else
        {
            // Unwrap the legend text
            self.$legends.each(function() {
                var $legend = $(this);

                $legend.html(  $legend.find('.timeline-text').html() );
            });
        }

        if (self.$timeline.data('remove-on-destroy') === true)
        {
            self.$timeline.remove();
        }

        self.element.css('position', self.element.data('orig-position'))
                    .removeData('orig-position')
                    .removeClass('ui-timeline');

        if ( opts.hideLegend !== true )
        {
            self.element.removeClass('ui-timeline-labeled');
        }

        if ($.type(opts.css) === 'string')
        {
            self.element.removeClass( opts.css );
        }
    }
});
    
}(jQuery));
