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
            $html    = $response->getBody();

            /*
            Connexions::log("Service_Util::getHead(): response: "
                            . "full html[ %s ]",
                            $html);
            // */

            // Strip off the ending, from and including </head>
            $html = preg_replace('/\s*<\/head.*$/si', '', $html);

            // Strip off the beginning, down to and including <head>
            $html = preg_replace('/^.*?<head[^>]*>\s*/si', '', $html);

            /*
            Connexions::log("Service_Util::getHead(): response: "
                            . "trimmed html[ %s ]",
                            $html);
            // */

            if (is_array($keepTags))
            {
                $dom       = new Zend_Dom_Query($html);
                $distilled = '';
                foreach ($keepTags as $tag)
                {
                    $els = $dom->query($tag);

                    /*
                    Connexions::log("Service_Util::getHead(): "
                                    .   "%d '%s' tags",
                                    count($els), $tag);
                    // */

                    foreach ($els as $el)
                    {
                        // Gather the node value and attributes
                        $val     = $el->nodeValue;
                        $attrStr = array();
                        foreach ($el->attributes as $name => $node)
                        {
                            array_push($attrStr,
                                       "{$name}=\"{$node->nodeValue}\"");
                        }

                        /* Re-construct the HTML of this tag and append it to
                         * 'distilled'
                         */
                        $distilled .= "<{$el->nodeName}";
                        if (count($attrStr) > 0)
                        {
                            $distilled .= ' '.  implode(' ', $attrStr); 
                        }
                        if (empty($val))
                        {
                            $distilled .= ' />';
                        }
                        else
                        {
                            $distilled .= ">{$val}</{$el->nodeName}>";
                        }
                    }
                }

                /*
                Connexions::log("Service_Util::getHead(): "
                                .   "distilled[ %s ]",
                                $distilled);
                // */

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
