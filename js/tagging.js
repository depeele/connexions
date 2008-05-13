/** @brief  A global class for manipulating cookies. */
var Cookies = {
    set: function(name, value, days) {
        var date = new Date();
        days = days || 30;
        date.setTime(date.getTime() + (days * 26 * 60 * 60 * 1000));
        document.cookie = name + "=" + value + "; " +
                          "expires=" + date.toGMTString() + "; " +
                          "domain=." + location.host + "; " +
                          "path=/";
    },
    get: function(name) {
        var ca  = document.cookie.split(';');
        var idex;
        name += '=';
        for (idex = 0; idex < ca.length; idex++)
        {
            var cookie  = ca[idex];
            while (cookie.charAt(0) == ' ')
            {
                cookie = cookie.substring(1,cookie.length);
            }
            if (cookie.indexOf(name) == 0)
                return cookie.substring(name.length, cookie.length);
        }

        return '';
    }
}

/** @brief  Add a trim method to String.
 *
 *  This method trims white-space from the beginning and end of a string.
 */
String.prototype.trim = function()
{
    return this.replace(/^\s+|\s+$/g,'');
}

/** @brief  Log somthing (text or an HtmlRequest from an AJAX response)
 *  @param  info    The info to log.
 */
function Logit(info)
{
    if (typeof jslog == "object")
    {
        if (typeof info == "object")
        {
            if (info.responseText)
                jslog.info('AJAX Response: ['+info.responseText+']\n');
            else if (info.statusText)
                jslog.info('AJAX Status: ['+info.statusText+']\n');
        }
        else
            jslog.info(info + "\n");
    }
}

/** @brief  Given a text input item, return the current cursor/input position
 *          (in characters).
 *  @param  item    The text input item.
 *
 *  @return The current cursor/input position (in characters).
 */
function getCursorPos(item)
{
    var cursorPos   = 0;

    if (document.selection)
    {
        // IE
        sel = document.selection.createRange();
    }
    else if (item.selectionStart || (item.selectionStart == '0'))
    {
        var startPos = item.selectionStart;
        var endPos   = item.selectionEnd;

        cursorPos = endPos;
    }
    else
    {
        cursorPos = item.value.length;
    }

    return cursorPos;
}

/** @brief  Validate the character input and tag length of tags.
 *  @param  item    The input item associated with this event.
 *  @param  e       The input event.
 *
 *  for a tag keeping track of the last
 *          tag separator (,) and the length of the current tag.  If an in
 */
function ValidateTagChar(item, e)
{
    e = e || window.event;
    if (e.ctlKey || e.altKey)
    {
        return true;
    }

    var key     = window.event ? e.keyCode : e.which;
    var keychar = String.fromCharCode(key);
    jslog.info('ValidateTagChar: ['+key+':'+keychar+'], '+
                          'curVal['+item.value+']');

    if ((key == 27) ||  // ESC
        (key ==  8) ||  // Backspace
        (key == 13) ||  // Carriage Return
        (key ==  0))    // Arrow keys
    {
        return true;
    }

    if (keychar == ',')
    {
        return true;
    }

    /* Validate the character. */
    var reg = /([!@#$%&+\|\\\/\?\'\"\`])/;
    if (reg.test(keychar))
    {
        new Effect.Appear(item.id + '.Characters');
        new Effect.Highlight(item.id + '.Characters');
        return false;
    }

    /* Validate the length of the tag currently being input/modified.
     *   Keep in mind that we COULD be modifying a tag that was previously
     *   typed if the user has used the mouse or arrow keys to move backwards.
     */
    var curPos = getCursorPos(item);
    var lastSep = item.value.lastIndexOf(',', curPos-1) + 1;
    var nextSep = item.value.indexOf(',', lastSep + 1);
    if (lastSep < 0)  lastSep = 0;
    if (nextSep < 0)  nextSep = item.value.length;
    var tagLen = nextSep - lastSep + 1;

    jslog.info('ValidateTagChar: ['+lastSep+':'+
                                   +curPos+':'+
                                   +nextSep+'], '+
                                   'tagLen['+tagLen+']');
    if (tagLen > gTagSize)
    {
        new Effect.Appear(item.id + '.Length');
        new Effect.Highlight(item.id + '.Length');
        return false;
    }

    return true;
}

/** @brief  Block non-numeric characters.
 *  @param  e   The input event (onkeypress).
 */
function Blocknon_numeric(e)
{
    var key = window.event ? e.keyCode : e.which;

    if ((key == 27) ||  // ESC
        (key ==  8) ||  // Backspace
        (key == 13) ||  // Carriage Return
        (key ==  0))    // Arrow keys
    {
        return true;
    }

    var keychar = String.fromCharCode(key);
    reg = /([0-9])/;
    if (reg.test(keychar))
    {
        return true;
    }

    Logit("Blocknon_numeric: key["+key+"], char["+keychar+"]");
    return false;
}

/** @brief  Focus on the given input control and (if possible) move the cursor
 *          to the indicated location possibly highlighting one or more
 *          characters.
 *  @param  obj         The input field object.
 *  @param  selectFrom  The index of the first character to select.
 *  @param  selectTo    The index of the last  character to select.
 */
function    InputFocus(obj, selectFrom, selectTo)
{
    if (selectTo < 0)
    {
        selectTo = obj.value.length;
    }
    if (selectFrom < 0) selectFrom = selectTo;

    if (obj.createTextRange)        // ie + opera
    {
        if (range == 0) range = obj.createTextRange();
        //range.moveEnd('character', obj.value.length);
        //range.moveStart('character', obj.value.length);
        //range.moveEnd('character', selectTo);
        //range.moveStart('character', selectFrom);
        range.moveEnd('character',   selectTo);
        range.moveStart('character', selectTo);
        setTimeout('range.select()', 10);
    }
    else if (obj.setSelectionRange) // ff
    {
        obj.select();
        //obj.setSelectionRange(obj.value.length, obj.value.length);
        //obj.setSelectionRange(selectFrom, selectTo);
        obj.setSelectionRange(selectTo, selectTo);
    }
    else                            // safari
    {
        obj.blur();
        obj.focus();
    }
}

/** @brief  Load the set of global tags.
 *  @param  newTags The set of tags to load.
 */
function LoadTags(newTags)
{
    tags = newTags;
}

/** @brief  Create a new window with the given size and navigate to the
 *          given URL.
 *  @param  URL     The URL to navigate to.
 *  @param  nWidth  The window width.
 *  @param  nHeight The window height.
 */
function Pop(URL,nWidth,nHeight)
{
    day = new Date();
    id  = day.getTime();
    eval("page" + id + " = window.open(URL, '" + id + "', " +
            "'toolbar=0,scrollbars=1,location=0,statusbar=0,"+
             "menubar=0,resizable=1,width="+nWidth+",height="+nHeight+"');");
}

/** @brief  Text size calculator
 *
 *  Create a hidden div that we will place target text into in order to
 *  obtain a pixel-accurate measure of the text size within the current
 *  browser.
 */
function TextSizer(mirrors)
{
    var style   = {zborder:       '1px solid red',
                   visibility:    'hidden',
                   position:      'absolute',
                   top:           0,
                   left:          0,
                   fontSize:      Element.getStyle(mirrors, 'font-size'),
                   fontFamily:    Element.getStyle(mirrors, 'font-family'),
                   fontWeight:    Element.getStyle(mirrors, 'font-weight')};
    var div     =   Builder.node('div');
    Element.setStyle(div, style);

    //document.appendChild(this.div);
    mirrors.parentNode.appendChild(div);

    div.getTextSize = function(text) {
        // Compute the pixel size of the given text.
        this.innerHTML = text.stripTags().replace(/ /g, '&nbsp;');

        //Logit("TextSizer.getTextSize: text["+text+"], new["+this.innerHTML+"], size["+this.offsetWidth+"]");

        return this.offsetWidth;
    };

    return div;
}

/* Page header bread crumb input */
var Crumb = {
    go: function(root) {
        var crumbInput  = $('crumb');
        var tag         = crumbInput.value;

        Logit("Crumb::go: root["+root+"]");
        crumbInput.originalVal = tag;
        crumbInput.root        = root || baseUrl + '/tag';
        crumbInput.onblur      = Crumb.blur;
        crumbInput.onfocus     = Crumb.focus;
        crumbInput.onmouseover = Crumb.mouseover;
        crumbInput.onmouseout  = Crumb.mouseout;
        crumbInput.onkeyup     = Crumb.keyhandler;
        crumbInput.onkeypress  = Crumb.keyhandler;

        crumbInput.sizer       = TextSizer(crumbInput);
        crumbInput.style.width = crumbInput.sizer.getTextSize(tag) + 40 +'px';

        //Logit("Crumb.go: root["+root+"]");
    },

    mouseover: function() {
        //Logit("Crumb.mouseover");
        this.addClassName('crumb-focus');   //addClass(me, 'crumb-focus');
    },

    mouseout: function() {
        //Logit("Crumb.mouseout");
        if (! this.focused)
        {
            this.removeClassName('crumb-focus'); //rmClass(this, 'crumb-focus');
        }
    },

    focus: function() {
        //Logit("Crumb.focus");
        this.focused = true;
        this.addClassName('crumb-focus');   //addClass(this, 'crumb-focus');
    },

    blur: function() {
        //Logit("Crumb.blur");
        if (this.submitting)    return false;
        this.focused = false;
        this.value   = this.originalValue;
        this.removeClassName('crumb-focus'); //rmClass(this, 'crumb-focus');
        this.style.width = this.sizer.getTextSize(this.value) + 40 +'px';
    },

    keyhandler: function(e) {
        //Logit("Crumb.keyhandler");
        if (e.type == 'keypress' && e.keyCode == 13)
        {
            // Apply the tag limits
            var tag = this.value;   //.replace(/[+,\/]\s*/g, '+');
            if (tag)
            {
                //Logit("Crumb: redirect, root["+this.root+"], tag["+tag+"]");
                this.submitting = true;
                location.href = this.root + '/' + tag;
            }
        }
        this.style.width = this.sizer.getTextSize(this.value) + 40 +'px';
    }
}

/** @brief  Change the way information is displayed.
 *  @param  dataType    The type of data displayed (Items or Tags).
 *  @param  order       The desired ordering.
 *  @param  limit       The number of items per page.
 *  @param  dispType    Display type for Tags (List or Cloud).
 */
function ChangeDisplay(dataType, order, limit, dispType)
{
    Logit("ChangeDisplay: dataType["+dataType+"], "+
          "order["+order+"], limit["+limit+"], "+
          "dispType["+dispType+"]");

    var tags    = '';
    var crumb   = $('crumb');
    if (crumb)
    {
        tags    = $('crumb').value.replace(/[+,\/]\s*/g, '+');
    }
    var loading = $('global_loading');
    var area    = $(dataType + '-top');

    Cookies.set(dataType.toLowerCase()+'Area_order', order, 30);

    if (dispType)
    {
        Cookies.set('tagsArea_type',  dispType, 30);
        Cookies.set('tagsArea_limit', limit,  30);
    }

    if (loading)
        loading.show();

    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=change_params'+
                        '&type='+dataType+
                        '&order='+order+
                        '&limit='+limit+
                        '&display='+dispType,
            onComplete:function(req){
                // Toggle the progress image off and the input field on.
                if (loading)
                    loading.hide();
                area.innerHTML = req.responseText;
            }
        });
}

/** @brief  Add the user in the given form element to the watchlist.
 *  @param  elemId  The form element containing the user.
 *
 *  NOTE: If there is an element with id 'elemId'.progress, show/hide it to
 *        indicate progress.
 */
function WatchlistAdd(elemId)
{
    var targId  = 'WatchList-top';
    var elem    = $(elemId);
    var status  = $(elemId + '.status');

    var userId = elem.value;
    Logit("WatchlistAdd: userId["+userId+"]");

    if (status != null)
        status.innerHTML = '&nbsp;';

    Logit("WatchlistAdd: ajax...");

    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=watchlist'+
                        '&watchingid='+userId+
                        '&type=watchlist'+
                        '&status=add',
            onLoading:function() {
                // Toggle the progress image on and the input field off.
                Logit("WatchlistAdd: show progress image");
                if (status != null)
                    status.innerHtml = "<img src='"+
                                        baseUrl + "/images/Progress.gif' />";

                Logit("WatchlistAdd: continuing");
            },
            onComplete:function(req){
                //Logit(req);

                if (status != null)
                    status.innerHTML = '&nbsp;';

                if (req.responseText.substr(0,7) == "FAILURE")
                {
                    Logit("WatchlistAdd: FAILURE");
                    if (status != null)
                    {
                        status.innerHTML = "<span class='error'>"+
                                            req.responseText + "</span>";
                    }
                }
                else
                {
                    Logit("WatchlistAdd: success targId["+targId+"]");
                    var target = $(targId);

                    target.innerHTML = req.responseText;
                }
            }
        });
}

/** @brief  Make a call to change the status of a user in a watchlist.
 *  @param  imgId       The id status image.
 *  @param  type        Type of area (watchlist or user)
 *  @param  userId      The userId to change.
 *  @param  status      The new status.
 *  @param  dispUserid  The userid to use when re-generating the watchlist.
 */
function ChangeStatus(imgId, type, userId, status, dispUserId)
{
    var img     = $(imgId);
    var origSrc = img.src;
    var targId  = 'WatchList-top';
    if (type == 'user')
    {
        targId = 'User'+userId;
    }

    if (! dispUserId)
    {
        dispUserId = userId;
    }

    Logit("ChangeStatus: userId["+userId+"], "+
          "type["+type+"], status["+status+"], targId["+targId+"]");

    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=watchlist_changestatus'+
                        '&watchingid='+userId+
                        '&type='+type+
                        '&status='+status+
                        '&dispUserId='+dispUserId,
            onLoading:function() {
                // Toggle the progress image on and the input field off.
                img.src = baseUrl + '/images/Progress.gif';
            },
            onError:function(req){
                Logit(req);
                img.src = origSrc;
            },
            onSuccess:function(req){
                Logit(req);
                Logit("req.response: "+req.response);

                if (req.responseText.substr(0,7) != "FAILURE")
                {
                    Logit("ChangeStatus: success targId["+targId+"]");
                    var target = $(targId);

                    target.innerHTML = req.responseText;
                }
            }
        });
}

/** @brief  Change status image.
 *  @param  imgId   The id status image.
 *  @param  src     The new source.
 */
function Status(imgId, src)
{
    var img = $(imgId);

    img.src = src;
}

/*****************************************************
 * Paging
 *
 */

/** @brief  Update paging display.
 *  @param  forms       The form(s) to modify.
 *  @param  showNum     Show page number (or progress spinner)?
 *  @param  cur         The current page number.
 *  @param  max         The maximum page number.
 */
function UpdatePagingControls(forms, showNum, cur, max)
{
    if (forms instanceof Array)
    {
        for(var idex = 0; idex < forms.length; idex++)
        {
            UpdatePagingControls(forms[idex], showNum, cur, max);
        }
        return;
    }

    Logit("UpdatePagingControls: "+(showNum?"true":"false")+
          ", cur["+cur+"], max["+max+"]");

    var form        = forms;
    var images      = form.getElementsByTagName("img");
    var rew         = $(images.item(0));
    var progress    = $(images.item(1));
    var fwd         = $(images.item(2));
    var input       = $(form.PageNum);

    if (showNum != true)
    {
        /* Paging beginning - Show the progress spinner and hide the page
         *                     number.
         */
        /*progress.show();
        input.hide();*/
        
        rew.src = baseUrl + '/images/Progress.gif';
        fwd.src = baseUrl + '/images/Progress.gif';
    }
    else
    {
        /* Paging complete - Change the page number */
        input.value = cur;

        /* Adjust the rewind and forward buttons */
        rew.src = baseUrl + "/images/Rewind"  +
                                (cur <  2   ? "-off" : "") + ".png";
        fwd.src = baseUrl + "/images/Forward" +
                                (cur >= max ? "-off" : "") + ".png";

        /* Hide the progress spinner and show the page number. */
        /*progress.hide();
        input.show();*/
    }
}

/** @brief  This is a wrapped routine to update all paging form areas.
 *  @param  form        The paging form.
 *  @param  content     The content area.
 *  @param  req         The AJAX request.
 *  @param  cur         The current page number (optional)
 *  @param  max         The maximum page number (optional)
 */
function UpdatePagingForm(form, content, req, cur, max)
{
    if (cur > max)
        cur = max;

    Logit("UpdatePagingForm: content["+content+"]");
    /*Logit("UpdatePagingForm: content["+content.id+"], "+
           ", cur["+cur+"], max["+max+"]");*/

    if (req != null)    //showNum == true)
    {
        //Logit(req);

        if ((req.status == undefined) ||
            (req.status == 0)         ||
            (req.status >= 200 && req.status < 300))
        {
            if (form.id.match(/Bottom/))
            {
                /*
                 * This is the page at the bottom.  Since we now have
                 * a new page of data, scroll to the top.
                 */
                new Effect.ScrollTo(content.parentNode);
                //new Effect.Scroll(content);
            }

            Logit("UpdatePagingForm: 3");
            /*if(Async != 'async')
                expandcontent('sc1', $('tab1'));*/

            content.innerHTML = req.responseText;

            Logit("UpdatePagingForm: 4");
        }

        form.PageNum.value = cur;
    }

    var otherName = form.id.replace(/(.*?)(Top|Bottom)/,
                                        function(str,p1,p2,offset,s)
                                        {
                                            if (p2 == 'Top')
                                                return p1 + 'Bottom';

                                            return p1 + 'Top';
                                        }
                                     );

    Logit("UpdatePagingForm: form["+form.id+"], other["+otherName+"]");

    var forms   = new Array(form, $(otherName));
    UpdatePagingControls(forms, (req != null), cur, max);
}

/** @brief  Perform paging.
 *  @param  form        The form used to perform paging.
 *  @param  Op          The direction/operation: + is forward, - is backward
 *  @param  Pg          The number of pages to move.
 *  @param  Async       Asynchronous?
 */
function Pagination(form,Op,Pg,Async)
{
    var PAGE    = 0;
    var script  = '/action.php';
    var tags    = '';
    var content = false;
    var id      = '';

    content = $(form.ContentId.value);
    id      = form.PagerId.value;

    try{
        // Grab the current page number from the form.
        PAGE = parseInt(form.PageNum.value);
    }
    catch(e)
    {
        PAGE = 1;
    }

    if(isNaN(PAGE) == false)
    {
        if (Op == '+')
        {
            PAGE += Pg;
        }
        else if (Op == '-')
        {
            PAGE -= Pg;
        }
        
        //Logit("Pagination: Op["+Op+"], PAGE["+PAGE+"], Pg["+Pg+"]");
        if (PAGE < 1)
        {
            if (Pg != 0)
                return; /* Trying to move back 1 page from page 1 */

            PAGE = 1;
        }

        /* Use prototype directly */
        new Ajax.Request(baseUrl + '/action.php',
            {
                method: 'get',
                parameters: 'Action=page&PagerId='+id+'&Page='+PAGE,
                onLoading:function() {
                    // Toggle the progress image on and the input field off.
                    UpdatePagingForm(form, content, null);
                },
                onComplete:function(req){
                    // Toggle the progress image off and the input field on.
                    var maxNode = form.getElementsByTagName("span").item(0);
                    var maxPage = parseInt(maxNode.innerHTML.substr(1));

                    UpdatePagingForm(form, content, req, PAGE, maxPage);
                }
            });
    }

    return;
}

/*****************************************************
 * Voting & Star ratings.
 *
 */
var ones = new Array(  '',  ' one',  ' two',  ' three',  ' four',  ' five',  ' six',  ' seven',  ' eight',  ' nine',  ' ten',  ' eleven',  ' twelve',  ' thirteen',  ' fourteen',  ' fifteen',  ' sixteen',  ' seventeen',  ' eighteen',  ' nineteen' );  

/** @brief  Called when mousing over a star rating.
 *  @param  item    The star item the mouse is over.
 *  @param  rate    The rate value of this item.
 */
function RateOver(item, rate)
{
    Logit('RateOver: item.id['+item.id+']');
    Logit('RateOver: item.parentNode.id['+item.parentNode.id+']');

    var Numbr = ones[rate];
    item.className = Numbr+'-stars-focus';
}

/** @brief  Called when mousing out of a star rating.
 *  @param  item    The star item being left.
 *  @param  rate    The rate value of this item.
 */
function RateOut(item, rate)
{
    Logit('RateOut: item.id['+item.id+']');
    Logit('RateOut: item.parentNode.id['+item.parentNode.id+']');

    var Numbr = ones[rate];
    item.className = Numbr+'-stars';
}

/** @brief  Called when a star is clicked upon.
 *  @param  item    The star item that was clicked on.
 *  @param  type    The type of vote (Item, User).
 *  @param  rate    The rating.
 */
function RateVote(item, type, rate)
{
    /*
     * 'item' is an <a> element within an <li> element of a <ul> list.
     * so the grandparent of 'item' is the top-level that holds the current
     * rating value in its title attribute.
     *
     * By design, the id of the top-level element is <RatingId>.<ItemId>
     * where <ItemId> is the unique identifier of the item being rated.
     */
    var Rated   = $(item.parentNode.parentNode);
    var strs    = Rated.id.split('.');
    var id      = strs[2];

    Logit('RateVote: item.id['+item.id+'], type['+type+'], rate['+rate+']');
    Logit('RateVote: rated.id['+Rated.id+'], id['+id+']');

    var curRating   = Rated.title * 1;
    if (curRating == rate)
    {
        // Reset the value to "No rating"
        rate = 0;
    }

    Logit("RateVote: curRating["+curRating+"]");

    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=Vote&Type='+type+
                        '&Id=' + id +
                        '&State='+rate,
            onLoading:  function(req){
                new Effect.Opacity(Rated, {duration:0.3, from:1.0, to:0.5});
            },
            onError:function(req){
                Logit(req);
                new Effect.Opacity(Rated, {duration:0.3, from:0.5, to:1.0});
            },
            onSuccess:function(req){
                Logit(req);
                new Effect.Opacity(Rated, {duration:0.3, from:0.5, to:1.0});

                if (req.responseText.substr(0,7) != "FAILURE")
                {
                    Logit("RateVote: success id["+id+"]");
                    var target = $(type+id);

                    target.innerHTML = req.responseText;
                }
            }
        });
}

/** @brief  Called when a star is clicked upon - this simply remembers the
 *          setting without automatically calling home to save it.
 *  @param  item    The star that was clicked on.
 *  @param  type    The type of vote (Item, User).
 *  @param  rate    The rating.
 */
function RateSet(item, type, rate)
{
    Logit('RateSet: item.id['+item.id+'], type['+type+'], rate['+rate+']');

    /*
     * 'item' is an <a> element within an <li> element of a <ul> list.
     * so the grandparent of 'item' is the top-level that holds the current
     * rating value in its title attribute.
     *
     * By design, the id of the top-level element is <RatingId>.<ItemId>
     * where <ItemId> is the unique identifier of the item being rated.
     *
     * There MAY be a form input element associated with this rating.  If
     * so, it will have the id 'Rated.id-input'.
     */
    var Rated       = $(item.parentNode.parentNode);
    var curRating   = Rated.title * 1;
    var RateInput   = $(Rated.id+'-input');
    var strs        = Rated.id.split('.');
    var id          = strs[2];

    Logit('RateSet: rated.id['+Rated.id+'], id['+id+'], curRating['+curRating+']');

    if (curRating == rate)
    {
        // Reset the value to "No rating"
        rate = 0;
    }

    Logit("RateSet: curRating["+curRating+"], id["+id+"], rate["+rate+"]");
    new Effect.Opacity(Rated, {duration:0.2, from:1.0, to:0.5});

    var Numbr  = ones[curRating];
    var Numbrr = ones[rate];
    if (curRating > 0)
        item.className = Numbr+'-stars';

    item.className = Numbrr + '-stars-focus';
    Rated.title    = rate;
    if (RateInput)
    {
        RateInput.value = rate;
    }

    for(var idex = rate+1; idex<=5; idex++)
    {
        Numbr = ones[idex];
        $(Rated.id + '.' + idex).className = Numbr+"-stars";
    }

    new Effect.Opacity(Rated, {duration:0.2, from:0.5, to:1.0});
}

/*****************************************************
 * Privacy and Favorite indicators
 *
 */

/** @brief  Set the privacy indicator to the given state.
 *  @param  item        The indicator DOM element.
 *  @param  state       The new state ('public' or 'private').
 */
function SetPrivacy(item, state)
{
    var itemInput   = $(item.id + '-input');

    if(state == "public")
    {
        var stateImg    = baseUrl + '/images/Pad-un-lock.png';
        var stateTitle  = 'Click to make this link private';
    }
    else
    {
        state = "private";

        var stateImg    = baseUrl + '/images/Padlock.png';
        var stateTitle  = 'Click to make this link public';
    }

    item.alt   = state;
    item.src   = stateImg;
    item.title = stateTitle;

    if (itemInput)
        itemInput.value = state;
}

/** @brief  Toggle the privacy of an item.
 *  @param  item        The indicator DOM element.
 *  @param  immediate   Use AJAX to immediately change the privacy state?
 *                      (otherwise, simply set the state, including the
 *                       associated hidden input value).
 */
function TogglePrivacy(item, immediate)
{
    Logit('TogglePrivacy: item.id['+item.id+'], tagName['+item.tagName+']');

    if (item.tagName != 'IMG')
    {
        item = item.firstChild;
        if (! item || (item.tagName != 'IMG'))
            return;
    }

    var strs    = item.id.split('.');
    var id      = strs[2];

    Logit('TogglePrivacy: id['+id+']');

    var newState    = 'public';
    var origImg     = item.src;

    if (item.alt == 'public')
    {
        newState    = 'private';
    }

    Logit('TogglePrivacy: newState['+newState+']');

    if (immediate)
    {
        item.src = baseUrl + '/images/Progress.gif';

        /* Use prototype directly */
        new Ajax.Request(baseUrl + '/action.php',
            {
                method: 'get',
                parameters: 'Action=Private&Type=Item&State='+ newState +
                            '&Id=' + id,
                onError:function(req){
                    Logit(req);
                    item.src = origImg;
                },
                onSuccess:function(req){
                    Logit(req);

                    if (req.responseText.substr(0,7) != "FAILURE")
                    {
                        Logit("TogglePrivacy: success id["+id+"]");
                        var target = $('Item'+id);

                        target.innerHTML = req.responseText;
                        target.className = newState;
                        //SetPrivacy(item, newState);
                    }
                }
            });
    }
    else
    {
        SetPrivacy(item, newState);
    }
}

/** @brief  Set the favorite indicator to the given state.
 *  @param  item        The indicator DOM element.
 *  @param  state       The new state ('On' or 'Off').
 */
function SetFavorite(item, state)
{
    var itemInput   = $(item.id + '-input');

    if(state == "On")
    {
        var stateImg    = baseUrl + '/images/Star.png';
        var stateTitle  = 'Click to remove this link from your favorites';
    }
    else
    {
        state = "Off";

        var stateImg    = baseUrl + '/images/Fish.png';
        var stateTitle  = 'Click to add this link to your favorites';
    }

    item.alt   = state;
    item.src   = stateImg;
    item.title = stateTitle;

    if (itemInput)
        itemInput.value = state;
}

/** @brief  Toggle the favorite status of an item.
 *  @param  item        The indicator DOM element.
 *  @param  immediate   Use AJAX to immediately change the privacy state?
 *                      (otherwise, simply set the state, including the
 *                       associated hidden input value).
 */
function ToggleFavorite(item, immediate)
{
    Logit('ToggleFavorite: item.id['+item.id+'], tagName['+item.tagName+']');

    if (item.tagName != 'IMG')
    {
        item = item.firstChild;
        if (! item || (item.tagName != 'IMG'))
            return;
    }

    var strs    = item.id.split('.');
    var Type    = strs[1];
    var id      = strs[2];

    Logit('ToggleFavorite: id['+id+'], Type['+Type+']');


    var newState    = 'On';
    var origImg     = item.src;

    if(item.alt == "On")
    {
        newState    = 'Off';
    }

    Logit('ToggleFavorite: newState['+newState+']');

    if (immediate)
    {
        item.src = baseUrl + "/images/Progress.gif";

        /* Use prototype directly */
        new Ajax.Request(baseUrl + '/action.php',
            {
                method: 'get',
                parameters: 'Action=Favorite&Type='+Type+'&State='+newState+
                            '&Id='+id,
                onError:function(req){
                    Logit(req);
                    item.src = origImg;
                },
                onSuccess:function(req){
                    if (req.responseText.substr(0,7) != "FAILURE")
                    {
                        Logit("ToggleFavorite: success id["+id+"]");
                        var target = $('Item'+id);

                        target.innerHTML = req.responseText;
                        //SetFavorite(item, newState);
                    }
                }
            });
    }
    else
    {
        SetFavorite(item, newState);
    }
}

/*****************************************************
 * Item management
 *
 */

/** @brief  Delete an item.
 *  @param  id      The unique identifier of the item to delete.
 */
function ItemDelete(id)
{
    var nameElement = $('Disp.Item.'+id+'.Name');
    var prompt      = 'Delete link';
    if (nameElement)
        prompt += '"' + nameElement.title +'"';
    prompt += '?';

    var target = $('Item'+id);
    new Effect.Highlight(target);

    var agree   = confirm(prompt,
                          {windowParameters:{className: "alphacube",
                                    width:300, height:100}, okLabel: "yes"});

    if (! agree)
        return;

    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=Delete'+
                        '&Type=Item'+
                        '&State=true'+
                        '&Id=' +id,
            onLoading:  function(req){
                try{
                    new Effect.Opacity(target, {duration:0.2, from:1.0, to:0.5});
                }catch(e){}
            },

            onSuccess:function(req){
                Logit(req);
                try{
                    new Effect.BlindUp(target, {duration:0.5});
                }catch(e){}
            }
        });
}

/** @brief  Tag an existing item for the current user.
 *  @param  id      The unique identifier of the item to tag.
 */
function ItemTag(id)
{
    /* Ensure that the edit information is correct. */
    var ItemBase    = "Disp.Item." + id;

    var Name        = $(ItemBase + ".Name").innerHTML;
    var Description = $(ItemBase + ".Description").innerHTML;
    var Url         = $(ItemBase + ".Name").href;
    var Tags        = '';   //$(ItemBase + ".Tags").innerHTML;
    /*var Favorite    = $(ItemBase + ".Favorite").alt;
    var Privacy     = $(ItemBase + ".Privacy").alt;
    var Rate        = $(ItemBase + ".Rate").title * 1;*/

    //Logit('ItemTag: id['+id+'], Name['+Name+'], Url['+Url+'], Desc['+Description+'], Tags['+Tags+']');

    /* Open a new window with the given information for tagging. */
    Pop(baseUrl + '/action.php?Action=main:post'+
                             '&id='+id+
                             '&name='+Name+
                             '&url='+Url+
                             '&description='+Description+
                             '&tags='+Tags+
                             '&closeAction=close',
        800, 500);
}

/** @brief  Display detailed information about an item.
 *  @param  url     The url of the item to display.
 */
function ItemInfo(url)
{
    var hash    = md5(url);

    //alert("ItemInfo("+id+"): params["+params+"]");

    //Pop(baseUrl + '/details?id='+id, 600, 400);
    window.location = baseUrl + '/details/' + url;
}

/** @brief  Hide item information and show item editing.
 *  @param  id      The unique item identifier.
 */
function ItemEdit(id)
{
    /* Ensure that the edit information is correct. */
    var ItemBase    = "Disp.Item." + id;
    var EditBase    = "Edit.Item." + id;

    Logit('ItemEdit: ItemBase['+ItemBase+'], EditBase['+EditBase+']');

    var Form        = $(EditBase + '.Form');
    if (! Form)
        return;

    var Name        = $(ItemBase + ".Name").innerHTML;
    var Description = $(ItemBase + ".Description").innerHTML;
    var Url         = $(ItemBase + ".Name").href;
    var Tags        = $(ItemBase + ".Tags").innerHTML;
    var Favorite    = $(ItemBase + ".Favorite").alt;
    var Privacy     = $(ItemBase + ".Privacy").alt;
    var Rate        = $(ItemBase + ".Rate").title * 1;

    $(EditBase + ".Name").value        = Name;
    $(EditBase + ".Description").value = Description;
    $(EditBase + ".Url").value         = Url;
    $(EditBase + ".Tags").value        = Tags;

    // Initialize the favorite status
    SetFavorite($(EditBase + ".Favorite"), Favorite);

    // Initialize the privacy status
    SetPrivacy($(EditBase + ".Privacy"), Privacy);

    // Initialize the rating
    if (Rate > 0)
        var rateItemId = EditBase + '.Rate.' + Rate;
    else
        var rateItemId = EditBase + '.Rate.1';

    var rateItem    = $(rateItemId);
    Logit('ItemEdit: rateItem['+rateItemId+']');

    // Since RateSet will reset to 0 if the new rate == the old rate
    // we need to call this twice to get it set of the proper value.
    RateSet(rateItem, Rate);
    RateSet(rateItem, Rate);

    $(ItemBase).hide();
    //new Effect.Fade  (ItemBase);
    new Effect.Appear(EditBase);
    //$(EditBase).show();
}

/** @brief  Hide item editing and show item information.
 *  @param  id      The unique item identifier.
 */
function HideItemEdit(id)
{
    var ItemBase    = "Disp.Item." + id;
    var EditBase    = "Edit.Item." + id;

    Logit("HideItemEdit: id["+id+"]");

    $(EditBase).hide();
    //new Effect.Fade  (EditBase);
    new Effect.Appear(ItemBase);
}

/** @brief  Final close of an item edit.
 *  @param  id          The unique item identifier of the item being edited.
 *  @param  resp        The AJAX response associated with this edit.
 *  @param  url         For 'redirect', the url of the item being edited.
 *  @param  closeAction What action should occur on completion:
 *                          - close     attempt to close the window
 *                          - hide      hide the edit form
 *                          - redirect  redirect to $url
 */
function CloseItemEdit(id, resp, url, closeAction)
{
    switch (closeAction)
    {
    case 'close':
        //Logit("CloseItemEdit: close window");
        //window.close();
        self.close();
        break;

    case 'redirect':
        if (url.length < 1)
        {
            // No url was provided.  Just move back a page.
            //history.go(-1);
            history.back();
        }
        else
        {
            // Redirect to the url we posted about.
            location.href = url;
        }
        break;

    case 'hide':
        var DispBase    = "Disp.Item." + id;
        var repItem     = $('Item'+id);

        Logit("CloseItemEdit: display results - repItem["+repItem+"]");

        if (repItem && resp)
            repItem.innerHTML = resp.responseText;

        HideItemEdit(id);
        new Effect.Highlight(DispBase);
    }
}

/** @brief  Finish item editing.  Submit the changes, hide the item edit and
 *          show item information.
 *  @param  id          The unique item identifier.
 *  @param  closeAction What action should occur on completion:
 *                          - close     attempt to close the window
 *                          - hide      hide the edit form
 *                          - redirect  redirect to $info['url']
 */
function FinishItemEdit(id, closeAction)
{
    var EditBase    = "Edit.Item." + id;
    var DispBase    = "Disp.Item." + id;

    Logit("FinishItemEdit: id["+id+"], closeAction["+closeAction+"]");

    //$("Edit.Item." + id).hide();
    //$("Disp.Item." + id).show();

    var ItemForm    = $(EditBase + '.Form');
    Logit("FinishItemEdit: Form["+ItemForm.id+"]");

    // Validate the input
    var Name        = $F(EditBase + ".Name");
    var Url         = $F(EditBase + ".Url");
    var Tags        = $F(EditBase + ".Tags");
    
    if (Name.length == 0)
    {
        new Effect.Highlight(EditBase + ".Name");
        return false;
    }

    if ((Url.length == 0) || (Url == 'http://') || (Url == 'https://'))
    {
        new Effect.Highlight(EditBase + ".Url");
        return false;
    }

    if (Tags.length == 0)
    {
        new Effect.Highlight(EditBase + ".Tags");
        return false;
    }

    Logit("FinishItemEdit: Input valid: "+
                "Name["+Name +"], "+
                "Url["+Url   +"], "+
                "Tags["+Tags +"]\n");

    var Jsondata    = Form.serialize(ItemForm); //Form.serialize(EditBase + '.Form');

    Logit("FinishItemEdit: Jsondata ["+Jsondata+"]");

    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=edit&' + Jsondata,
            onLoading:function (req){
                Logit("FinishItemEdit: AJAX onLoading");
                Logit(req);
                $(EditBase + '.Submit').value = 'Saving...';
                new Effect.Opacity(EditBase,{duration:0.5, from:1.0, to:0.4});
            },
            onError:function(req){
                Logit("FinishItemEdit: AJAX onError");
                $(EditBase + '.Submit').value = 'Save (error)';

                // Leave the edit open to display the error
                Logit(req);
                return false;
            },
            onSuccess:function(req){
                Logit("FinishItemEdit: AJAX onSuccess");
                Logit(req);
                if(req.responseText == "Domain has been blocked.")
                {
                    Logit("FinishItemEdit: Domain blocked");

                    $(EditBase + '.Submit').value = 'Save (blocked)';

                    // Leave the edit open to display the error.
                    Dialog.alert("Domain has been blocked.",
                        {windowParameters:{className: "alphacube",
                                width:300, height:100}, okLabel: "close"});
                    return false;
                }
                else if(req.responseText.substr(0,7) == "FAILURE")
                {
                    Logit("FinishItemEdit: Server-side failure");

                    $(EditBase + '.Submit').value = 'Save (error)';

                    // Leave the edit open to display the error.
                    Dialog.alert("Server side failure - try again...",
                        {windowParameters:{className: "alphacube",
                                width:300, height:100}, okLabel: "close"});
                    return false;
                }

                // Update the privacy...
                var Privacy = $(EditBase + ".Privacy").alt;
                Logit("FinishItemEdit: New Privacy: "+ Privacy);

                SetPrivacy($(DispBase + ".Privacy"), Privacy);

                $(EditBase + '.Submit').value = 'Save';
                if (closeAction == 'close')
                    // We need a slight pause otherwise the window won't
                    // actually close...
                    setInterval('CloseItemEdit('+id+', null, '+
                                '"'+Url+'", "'+closeAction+'")', 500);
                else
                    CloseItemEdit(id, req, Url, closeAction);
            }
        });

    Logit("FinishItemEdit: done");
}

/** @brief  Perform a quick link to the given item.
 *  @param  Id      The item to quick link.
 *  @param  Ulid    ??
 */
function QuickLink(Id,Ulid)
{
    /* Use prototype directly */
    new Ajax.Request(baseUrl + '/action.php',
        {
            method: 'get',
            parameters: 'Action=qblink&Data='+Ulid,
            onSuccess:function(req){
                Logit(req);
                try{        
                    $("3d"+Id+"a").className = "Topcontainer";
                    $("3d"+Id+"o").innerHTML = $("3d"+Id+"o").innerHTML * 1 + 1;
                }catch(e){}
        
                try{
                    new Effect.Fade("3d"+Id+"u");
                }catch(e){}
        
                try{
                    new Effect.Fade("3d"+Id+"i");
                }catch(e){}
        
            
                try{        
                    $("r3d"+Id+"a").className = "Topcontainer";
                    $("r3d"+Id+"o").innerHTML = $("r3d"+Id+"o").innerHTML * 1 + 1;
                }catch(e){}
        
                try{
                    new Effect.Fade("r3d"+Id+"u");
                }catch(e){}
        
                try{
                    new Effect.Fade("r3d"+Id+"i");
                }catch(e){}
        
    
                try{       
                     $("4d"+Id+"a").className = "Topcontainer";
                    $("4d"+Id+"o").innerHTML = $("4d"+Id+"o").innerHTML * 1 + 1;
                }catch(e){}
        
                try{
                    new Effect.Fade("4d"+Id+"u");
                }catch(e){}
        
                try{
                    new Effect.Fade("4d"+Id+"i");
                }catch(e){}
        
                try{       
                     $("5d"+Id+"a").className = "Topcontainer";
                    $("5d"+Id+"o").innerHTML = $("5d"+Id+"o").innerHTML * 1 + 1;
                }catch(e){}
        
                try{
                    new Effect.Fade("35d"+Id+"u");
                }catch(e){}
        
                try{
                    new Effect.Fade("5d"+Id+"i");
                }catch(e){}
            }
        });
}

/******************************************************************/
        
function Tagmanager()
{
    this.Show = function()
    {
        /* Use prototype directly */
        new Ajax.Request(baseUrl + '/action.php',
          {
            method: 'get',
            parameters: 'Action=tagManager',
            onSuccess:function(req){
                Logit(req);
                win = new Window('dialog1', {className: "dialog",  width:700, height:450, zIndex: 100, minimizable:false,maximizable:false,resizable: true, title: "Tag Manager"})
                win.getContent().innerHTML= req.responseText;
                win.showCenter();
                win.setDestroyOnClose();
                fdTableSort.init();
            }
          });

/*
        new Ajax.Request('/?Action=User/Tagmanager/get.php',{
            onSuccess:function(req)
            {
        win = new Window('dialog1', {className: "dialog",  width:700, height:450, zIndex: 100, minimizable:false,maximizable:false,resizable: true, title: "Tag Manager"})
        win.getContent().innerHTML= req.responseText;
        win.showCenter();
        win.setDestroyOnClose();
        fdTableSort.init();
            }
            });
*/
    }
                
    this.Rename = function(Str,Nm)
    {
        var Nuname = prompt("Please provide a name to rename '"+Str+"' to:",Str);
        if(Nuname != '' && Nuname != null  && Nuname != Str)
        {
            /* Use prototype directly */
            new Ajax.Request(baseUrl + '/action.php',
                {
                    method: 'get',
                    parameters: 'Action=tagRename&Utid=' + Nm +
                                '&Nuname='+ Nuname,
                    onSuccess:function(req){
                        eval('var Jsondata = ' + req.responseText);
                        var HTML = Jsondata.HTML;
                        var Rmid = Jsondata.Rmid;
            
                        try{
                        $('Tagname'+Rmid).parentNode.parentNode.removeChild($('Tagname'+Rmid).parentNode);
                        }catch(e){}
            
                        var Jw = $('Tagname'+Nm).parentNode;
                        while (Jw.hasChildNodes()) { 
                        Jw.removeChild(Jw.childNodes[0]);
                        }
            
                        element = Builder.node('td',{id:'Tagname'+Jsondata.Usertagid},Jsondata.Name);
            
                        Jw.appendChild(element);
            
            
                        element = Builder.node('td',{id:'Taglinkcount'+Jsondata.Usertagid},Jsondata.Linkcount);
            
                        Jw.appendChild(element);
            
                        element = Builder.node('td');
                        element.innerHTML = Jsondata.Actioncolumn;
                        Jw.appendChild(element);
            
                        new Effect.Highlight($('Tagname'+Rmid).parentNode);
                    }
                });

/*
            new Ajax.Request('/?Action=User/Tagmanager/rename.php&Utid='+Nm+'&Nuname='+Nuname,{
            onSuccess:function(req)
            {
                eval('var Jsondata = ' + req.responseText);
                var HTML = Jsondata.HTML;
                var Rmid = Jsondata.Rmid;
            
                try{
                $('Tagname'+Rmid).parentNode.parentNode.removeChild($('Tagname'+Rmid).parentNode);
                }catch(e){}
            
                var Jw = $('Tagname'+Nm).parentNode;
                while (Jw.hasChildNodes()) { 
                Jw.removeChild(Jw.childNodes[0]);
                }
            
                element = Builder.node('td',{id:'Tagname'+Jsondata.Usertagid},Jsondata.Name);
            
                Jw.appendChild(element);
            
            
                element = Builder.node('td',{id:'Taglinkcount'+Jsondata.Usertagid},Jsondata.Linkcount);
            
                Jw.appendChild(element);
            
                element = Builder.node('td');
                element.innerHTML = Jsondata.Actioncolumn;
                Jw.appendChild(element);
            
                new Effect.Highlight($('Tagname'+Rmid).parentNode);
            }
            });
            */
        
        }
    }
                
    this.Delete = function(Str,Nm)
    {
        var Doitdoit = confirm('Are you sure you want to delete this tag?');
        if(Doitdoit)
        {
            /* Use prototype directly */
            new Ajax.Request(baseUrl + '/action.php',
              {
                method: 'get',
                parameters: 'Action=tagDelete&Utid='+Nm,
                onSuccess:function(req){
                    try{ $('Tagname'+Nm).parentNode.parentNode.removeChild($('Tagname'+Nm).parentNode);
                    }catch(e){}
                }
              });
              /*
            new Ajax.Request('/?Action=User/Tagmanager/delete.php&Utid='+Nm,{
                onSuccess:function(req)
                {
                    try{ $('Tagname'+Nm).parentNode.parentNode.removeChild($('Tagname'+Nm).parentNode);
                    }catch(e){}
                }
                });
                */
        }
    }
}
        
Tagmngr = new Tagmanager();

