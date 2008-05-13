<?php
class cloud_tag
{
    // constructor. init our variables
    function cloud_tag()
    {
        $this->tag_label = "tag";
        $this->tag_sizes = 7;
    }

    // set the label for the css class
    function set_label($label) {$this->tag_label = $label;}

    // set the number of buckets
    function set_tagsizes($sizes) {$this->tag_sizes = $sizes;}

    /** @brief  Create a cloud tag from an associative array of tags
     *  @param  array   The associative array of tags ('tag_name' => count).
     *  @param  string  The final display order of the cloud
     *                  ('Alpha' or 'Count').
     *
     *  @return array   An associative array of:
     *                      'tag_name' => array('count' => count,
     *                                          'bucket'=> bucket,
     *                                          'class' => label . bucket)
     */
    function make_cloud($tags, $order = 'Alpha')
    {
        if(count($tags) == 0) return $tags;

        // Sort according to counts
        asort($tags, SORT_NUMERIC);
    
        // Start with the sorted list of tags and divide by the number of font
        // sizes (buckets).  Then proceed to put an even number of tags into
        // each bucket. The only restriction is that tags of the same count
        // can't span 2 buckets, so some buckets may have more tags than
        // others. Because of this, the sorted list of remaining tags is
        // divided by the remaining 'buckets' to evenly distribute the
        // remainder of the tags and to fill as many 'buckets' as possible up
        // to the largest font size.
        $total_tags = count($tags);
        $min_tags   = $total_tags / ($this->tag_sizes - 1);
    
        $bucket_count = 1;
        $bucket_items = 0;
        $tags_set     = 0;
        $new_tags     = array();
        foreach($tags as $key => $tag_count)
        {
            // If we've met the minimum number of tags for this class and the
            // current tag does not equal the last tag, we can proceed to the
            // next class.
            if(($bucket_items >= $min_tags) &&
               ($last_count   != $tag_count) &&
               ($bucket_count <  $this->tag_sizes))
            {
                $bucket_count++;
                $bucket_items = 0;
    
                // Calculate a new minimum number of tags for the remaining
                // classes.
                $remaining_tags = $total_tags - $tags_set;
                $min_tags = $remaining_tags / $bucket_count;
            }
    
            // Set the tag to the current class.
            $new_tags[$key] = array('count'     => $tag_count,
                                    'bucket'    => ($bucket_count - 1),
                                    'class'     => $this->tag_label .
                                                    ($bucket_count - 1)
                                   );
            $bucket_items++;
            $tags_set++;
    
            $last_count = $tag_count;
        }
    
        if ($order == 'Alpha')
        {
            // Sort by key/tag name.
            ksort($new_tags);
        }
        else
        {
            // Sort by tag count.
            function sort_count($tag1,$tag2)
            {
                // Reverse sort by count.
                return $tag2['count'] - $tag1['count'];
            }

            uasort($new_tags, 'sort_count');
        }
    
        return $new_tags;
    }
    
    /*-------------------------------------------------------
     * internal-use-only below here
     *-------------------------------------------------------*/
    
    /*-------------------------------------------------------
     * member variables
     *-------------------------------------------------------*/
    
    var $tag_label; // the css base class name
    var $tag_sizes; // number of buckets (font sizes)
}


/** @brief  Generate the HTML for a tag cloud given a set of tags.
 *  @param  prefix      The prefix for accessing 'tags' (e.g. userName).
 *  @param  tags        The set of tags.
 *  @param  string      The final display order of the cloud
 *                      ('Alpha' or 'Count').
 *
 *  Tags should be an associative array of the form:
 *      .
 *
 *  @return html    The HTML representation of the cloud.
 */
function    generateCloud($prefix, $tags, $order = 'Alpha')
{
    $html   = '';

    // instantiate a cloud
    $cloud = new cloud_tag();
    
    // the class already has defaults, but let's override them for fun
    $cloud->set_label('cloud'); // css classes will be cloud1, cloud2, etc.
    $cloud->set_tagsizes(7); // 7 font sizes will be used
    
    $tagCloud = $cloud->make_cloud($tags, $order); // make a tag cloud
    
    foreach($tagCloud as $tagName => $item)
    {
        $html .= "<a href='{$prefix}/{$tagName}'><span class='{$item['class']}'>";
    
        if (false)  //$item['class'] == 'cloud6')
        {
            /*
             * Colorize this link as a gradient using one color per character
             * from #549abb to #ff6d07
             */
            $tagLen     = strlen($tagName);
            $redStart   = 0x54;
            $grnStart   = 0x9a;
            $bluStart   = 0xbb;
            $redEnd     = 0xff;
            $grnEnd     = 0x6d;
            $bluEnd     = 0x07;
            $redStep    = ($redEnd - $redStart) / ($tagLen - 1);
            $grnStep    = ($grnEnd - $grnStart) / ($tagLen - 1);
            $bluStep    = ($bluEnd - $bluStart) / ($tagLen - 1);
    
            for ($jdex = 0, $red = $redStart, $blu = $bluStart, $grn = $grnStart;
                   $jdex < $tagLen;
                     $jdex++, $red += $redStep, $blu += $bluStep, $grn += $grnStep)
            {
                $color = sprintf("#%02x%02x%02x", $red, $grn, $blu);
                $char  = substr($tagName, $jdex, 1);
    
                $html .= "<font color=\"{$color}\">{$char}</font>";
            }
        }
        else
        {
            $html .= $tagName;
        }
    
        $html .= "</span></a>\n";
    }
    
    return ($html);
}

?>
