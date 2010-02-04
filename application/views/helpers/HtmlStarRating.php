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
    public function htmlStarRating($rating,
                                   $css,
                                   $readOnly    = false,
                                   $title       = null,
                                   $count       = 5,
                                   $starWidth   = 16)
    {
        $useSplits = $rating != ceil($rating);
        $onCount   = floor($rating);

        $html = sprintf("<!-- htmlStarRating: rating[ %f ], readOnly[ %s ] -->",
                        $rating, ($readOnly ? 'true' : 'false'));

        $html .= "<div class='{$css}'"
              .      (! @empty($title)
                        ? " title='{$title}'"
                        : "")
              .                         ">";

        if (! $readOnly)
        {
            // We need a cancel
            $html .= "<div class='ui-stars ui-stars-cancel "
                  .              "ui-stars-cancel-disabled'>"
                  .   "<a title='cancel'>&nbsp;</a>"
                  .  "</div>";
        }
        
        for ($rdex = 0; $rdex < $onCount; $rdex++)
        {
            $html .= sprintf(  "<div class='ui-stars ui-stars-star "
                             .             "ui-stars-star-on'>"
                             .  "<a title='%s'>&nbsp;</a>"
                             . "</div>",
                             (! $readOnly
                                    ? $rdex + 1
                                    : ''));
        }

        if ($useSplits)
        {
            $split     = 8;
            $sRating   = $rating * $split;
            $stWidth   = floor($starWidth / $split);
            $divStyle  = " style='width: {$stWidth}px'";
            $onOffset  = $onCount * $split;

            // Create the split star
            for ($rdex = 0; $rdex < $split; $rdex++)
            {
                $html .= sprintf(  "<div class='ui-stars ui-stars-star %s'%s>"
                                 .  "<a title='' "
                                 .     "style='margin-left: -%dpx'>&nbsp;</a>"
                                 . "</div>",
                                 ( ( ($onOffset + $rdex) < $sRating)
                                     ? "ui-stars-star-on"
                                     : "ui-stars-star-disabled"),
                                 $divStyle,
                                 ( ($rdex + 1) % $split) * $stWidth);
            }
            $onCount++;
        }

        // Create the "off" stars
        for ($rdex = $onCount; $rdex < $count; $rdex++)
        {
            $html .= "<div class='ui-stars ui-stars-star "
                  .              "ui-stars-star-disabled'>"
                  .   "<a title=''>&nbsp;</a>"
                  .  "</div>";
        }
        
        $html .= "</div>";

        return $html;
    }
}
