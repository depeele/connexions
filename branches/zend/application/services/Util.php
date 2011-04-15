<?php
/** @file
 *
 *  A simple service to provide API access to utilities needed by our
 *  client-side scripts.
 */
class Service_Util
{
    /** @brief  Given a URL, retrieve the page, stripping it down to just the
     *          content between <head> and </head>, extracting JUST the
     *          tags and content indicated by 'keepTags'.
     *  @param  url         The desired URL;
     *  @param  keepTags    A comma-separated list of tags within <head> to 
     *                      keep (null === keep all);
     *
     *  Note: 'keepTags' REQUIRES well-formed HTML for the specified tags.
     *
     *  @return An array containing:
     *              {url:   The requested URL;
     *               html:  The distilled HTML of the head}
     */
    public function getHead($url, $keepTags = null)
    {
        /*
        Connexions::log("Service_Util::getHead( %s, '%s' ):",
                        $url, $keepTags);
        // */

        if (is_string($keepTags))
        {
            $keepTags = preg_split('/\s*,\s*/', $keepTags);
        }

        // Retrieve the contents of the provided URL.
        $client   = new Zend_Http_Client($url);

        $response = $client->request();

        $html     = '';
        if ($response->isSuccessful())
        {
            $html = $response->getBody();

            // Strip off the ending, from and including </head>
            $html = preg_replace('/\s*<\/head.*$/si', '', $html);

            // Strip off the beginning, down to and including <head>
            $html = preg_replace('/^.*?<head[^>]*>\s*/si', '', $html);


            if (is_array($keepTags))
            {
                $distilled = '';

                foreach ($keepTags as $tag)
                {
                    $re    = '#((?:<'. $tag .'.*?/>|'
                           .      '<'. $tag .'.*?</'. $tag .'>))#si';
                    $count = preg_match_all($re, $html, $matches,
                                            PREG_PATTERN_ORDER);

                    /*
                    Connexions::log("Service_Util::getHead(): well-formed: "
                                    . "%d matches for tag '%s': [ %s ]",
                                    $count, $tag,
                                    Connexions::varExport($matches));
                    // */

                    if ($count > 0)
                    {
                        $distilled .= implode("\n", $matches[1]);
                    }

                    /* Now, for malformed tags...
                     *
                     * :XXX: Currently, this will only match every other 
                     *       malformed tag if they are all directly in-sequence 
                     *       with nothing but white-space between...
                     */
                    $re    = '#(<'. $tag .'[^>]*>)\s*<[^/]#si';
                    $count = preg_match_all($re, $html, $matches,
                                            PREG_PATTERN_ORDER);

                    /*
                    Connexions::log("Service_Util::getHead(): mal-formed: "
                                    . "%d matches for tag '%s': [ %s ]",
                                    $count, $tag,
                                    Connexions::varExport($matches));
                    // */

                    if ($count > 0)
                    {
                        $distilled .= implode("\n", $matches[1]);
                    }
                }

                $html = $distilled;
            }

            /*
            Connexions::log("Service_Util::getHead( %s, '%s' ): "
                            .   "Distilled Body[ %s ]",
                            $url,
                            (is_array($keepTags)
                                ? implode(', ', $keepTags)
                                : ''),
                            $html);
            // */
        }

        return array('url'  => $url,
                     'html' => $html);
    }

    /** @brief  Retrieve the taggedOn date/times for the given user(s) and/or
     *          item(s).
     *  @param  users   A Model_Set_User instance, array, or comma-separated
     *                  string of users to match.
     *  @param  items   A Model_Set_Item instance, array, or comma-separated
     *                  string of items to match.
     *  @param  tags    A Model_Set_Tag instance, array, or comma-separated
     *                  string of tags to match.
     *  @param  order   An array of name/direction pairs representing the
     *                  desired sorting order.  The 'name's MUST be 'taggedOn'
     *                  or 'updatedOn' and the directions a
     *                  Connexions_Service::SORT_DIR_* constant.  If an order
     *                  is omitted, Connexions_Service::SORT_DIR_ASC will be
     *                  used [ {taggedOn: 'ASC'} ];
     *  @param  from    Limit the results to date/times AFTER this date/time
     *                  [ null == no date/time from restriction ];
     *  @param  until   Limit the results to date/times BEFORE this date/time
     *                  [ null == no date/time until restriction ];
     *
     *  @return An array of date/time strings.
     */
    public function getTimeline($users,
                                $items  = null,
                                $tags   = null,
                                $order  = null,
                                $from   = null,
                                $until  = null)
    {
        $service = Connexions_Service::factory('Model_Bookmark');

        $timeline = $service->getTimeline($users, $items, $tags,
                                          $order,
                                          $from, $until);

        return $timeline;
    }
}
