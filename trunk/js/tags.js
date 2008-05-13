/** @file
 *  @brief  Tag click-entry.
 *
 *  We have several globals we need:
 *      - tags[tag-name]        => {selected:true/false, ids:[id1,...]}
 */

/** @brief  Toggle the visibility of the folded display div.
 *  @param  name    The basic name of the div.
 *
 *  The ids of the divs are:
 *      <name>.ctl  - control the visibility.
 *      <name>      - the area for displaying the tags.
 */
function toggleFold(name)
{
    var ctl     = $(name + '.ctl');
    var sect    = $(name);
    var state   = '';

    if (ctl.className == 'ctl-open')
    {
        // Close this open section
        //new Effect.Fade(sect);
        sect.hide();
        ctl.className = 'ctl-close';
        state         = 'close';
    }
    else
    {
        // Open this closed section
        //new Effect.Appear(sect);
        sect.show();
        ctl.className = 'ctl-open';
        state         = 'open';
    }

    // Set the cookie representing this fold's state
    Cookies.set('foldState_'+name, state, 30);
}

/** @brief  Select the given tag and highlight all instances.
 *  @param  name    The name of the tag.
 */
function selectTag(name)
{
    var idex;

    //Logit('selectTag: name['+name+']');
    var tag = tags[name];

    if (! tag)
        return;

    tags[name]['selected'] = true;
    for (idex = 0; idex < tags[name]['ids'].length; idex++)
    {
        var tagId       = 'tag.'+tags[name]['ids'][idex];
        var tagElement  = $(tagId);

        if (tagElement)
        {
            //Logit('selectTag: add selected to ['+tagElement.id+']');
            tagElement.addClassName('selected');
        }
    }
}

/** @brief  Deselect the given tag and unhighlight all instances.
 *  @param  name    The name of the tag.
 */
function deselectTag(name)
{
    var idex;

    //Logit('deselectTag: name['+name+'], ');

    tags[name]['selected'] = false;
    for (idex = 0; idex < tags[name]['ids'].length; idex++)
    {
        var tagId       = 'tag.'+tags[name]['ids'][idex];
        var tagElement  = $(tagId);

        //Logit('deselectTag: try to remove selected from ['+tagId+']');

        if (tagElement)
        {
            //Logit('deselectTag: remove selected from ['+tagElement.id+']');
            tagElement.removeClassName('selected');
        }
    }
}

/** @brief  When the user clicks on a tag, turn that tag on/off in the
 *          tag list depending on its current "state".
 *  @param  name    The name of the tag.
 */
function swapTag(name)
{
    var nameL       = name.toLowerCase();
    var tagStr      = $(tagInput).value;
    var tagArray    = tagStr.split(',');    //strip().split(',');
    var present     = false;
    var idex;

    for (idex = 0; idex < tagArray.length; idex++)
    {
        var tagL    = tagArray[idex].strip().toLowerCase();
        if (tagL == '')
        {
            /*
             * For some reason, split() creates an array with a single
             * empty item if the initial string is empty.  Remove it here
             * so we don't get an initial comma in the final content list.
             */
            tagArray.splice(idex, 1);
            idex   -= 1;
        }
        else if (tagL == nameL)
        {
            tagArray.splice(idex, 1);
            deselectTag(nameL);
            present = true;
            idex   -= 1;
        }
        else
        {
            tagArray[idex] = tagL;
        }
    }

    if (! present)
    {
        tagArray.push(nameL);
        selectTag(nameL);
    }

    var content = tagArray.join(', ')

    tagInput.refocus = function (tagInput, from, to) {
        InputFocus(tagInput, from, to);
    };

    tagInput.value = content;
    setTimeout('tagInput.refocus(tagInput, -1, -1)', 0);
}
