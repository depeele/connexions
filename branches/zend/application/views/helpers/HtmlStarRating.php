<?php
/** @file
 *
 *  Output a star rating using:
 *      rating
 *      css
 *      count       [ 5 ]
 *      starWidth   [ 16 ]
 */
class Connexions_View_Helper_HtmlStarRating extends Zend_View_Helper_Abstract
{
    public static   $ratingTitles   = array('Terrible',
                                            'Fair',
                                            'Average',
                                            'Good',
                                            'Excellent'
                                      );

    /** @brief  Create a new ui-stars rating area.
     *  @param  rating      The current rating value;
     *  @param  css         The CSS class of the wrapping div;
     *  @param  titles      A single string title for the entire container OR
     *                      an array of 'count' titles, one for each rating
     *                      option [ self::$ratingTitles ];
     *  @param  readOnly    Is this a read-only rating? [ false ];
     *  @param  count       The number of rating options
     *                      (if 'titles' is an array, the number of titles
     *                       determines the count, over-riding any value here);
     *  @param  starWidth   The width, in pixels, of each star [ 16 ];
     *                      
     */
    public function htmlStarRating($rating,
                                   $css,
                                   $titles      = null,
                                   $readOnly    = false,
                                   $count       = 5,
                                   $starWidth   = 16)
    {
        $useSplits = $rating != ceil($rating);
        $onCount   = ($useSplits ? floor($rating) : ceil($rating));

        if ($titles === null)
        {
            if ($count === count(self::$ratingTitles))
                $titles = self::$ratingTitles;
        }

        if (is_array($titles))
        {
            $count          = count($titles);
            $containerTitle = '';
        }
        else if (is_string($titles))
        {
            $containerTitle = $titles;
        }

        /*
        Connexions::log(sprintf("Connexions_View_Helper_HtmlStarRating: "
                                . "rating[ %f ], onCount[ %d ], "
                                . "useSplits[ %s ], readOnly[ %s ] -->",
                                $rating, $onCount,
                                ($useSplits ? 'true' : 'false'),
                                ($readOnly ? 'true' : 'false')) );
        // */

        $html =  "<div class='{$css} ui-stars-wrapper'"
              .      (! @empty($containerTitle)
                        ? " title='{$containerTitle}'"
                        : "")
              .                         ">";

        /****************************************************
         * Render 'cancel' if appropriate
         *
         */
        if (! $readOnly)
        {
            /*
            Connexions::log("Connexions_View_Helper_HtmlStarRating: "
                            . "render cancel");
            // */

            // We need a hidden form element and cancel
            $html .= "<input class='ui-stars-rating' type='hidden' "
                  .          "name='rating' value='{$rating}' />"
                  .  "<div class='ui-stars ui-stars-cancel "
                  .              "ui-stars-cancel-disabled'>"
                  .   "<a title='cancel'>&nbsp;</a>"
                  .  "</div>";
        }
        
        /****************************************************
         * Render stars that are 'on'
         *
         */
        for ($rdex = 0; $rdex < $onCount; $rdex++)
        {
            $title = (is_array($titles)
                        ? $titles[$rdex]
                        : $containerTitle); //($rdex + 1) );

            /*
            Connexions::log("Connexions_View_Helper_HtmlStarRating: "
                            . "render on #{$rdex}, title[ {$title} ]");
            // */

            $html .= "<div class='ui-stars ui-stars-star "
                  .              "ui-stars-star-on'>"
                  .   "<a title='{$title}'>&nbsp;</a>"
                  .  "</div>";
        }

        if ($useSplits)
        {
            /****************************************************
             * Split the final 'on' star to present the fraction
             * that is 'on'
             *
             */
            $split     = 8;
            $sRating   = $rating * $split;
            $stWidth   = floor($starWidth / $split);
            $divStyle  = " style='width: {$stWidth}px'";
            $onOffset  = $onCount * $split;

            /*
            Connexions::log("Connexions_View_Helper_HtmlStarRating: "
                            . "render split [ {$sRating} ]");
            // */

            $title = (is_array($titles)
                        ? $titles[$onCount]
                        : $containerTitle); //($onCount + 1) );

            // Create the split star
            for ($rdex = 0; $rdex < $split; $rdex++)
            {
                $html .= sprintf(  "<div class='ui-stars ui-stars-star %s'%s>"
                                 .  "<a title='%s' "
                                 .     "style='margin-left: -%dpx'>&nbsp;</a>"
                                 . "</div>",
                                 ( ( ($onOffset + $rdex) < $sRating)
                                     ? "ui-stars-star-on"
                                     : "ui-stars-star-disabled"),
                                 $divStyle,
                                 $title,
                                 ( ($rdex + 1) % $split) * $stWidth);
            }
            $onCount++;
        }

        /****************************************************
         * Render stars that are 'off'
         *
         */
        for ($rdex = $onCount; $rdex < $count; $rdex++)
        {
            $title = (is_array($titles)
                        ? $titles[$rdex]
                        : $containerTitle); //($rdex + 1) );

            /*
            Connexions::log("Connexions_View_Helper_HtmlStarRating: "
                            . "render off #{$rdex}, title[ {$title} ]");
            // */

            $html .= "<div class='ui-stars ui-stars-star "
                  .              "ui-stars-star-disabled'>"
                  .   "<a title='{$title}'>&nbsp;</a>"
                  .  "</div>";
        }
        
        $html .= "</div>";

        return $html;
    }
}
