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
}
