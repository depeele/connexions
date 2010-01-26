<?php
/** @file
 *
 *  View helper to render a single Tag in HTML.
 */
class Connexions_View_Helper_HtmlTagItem
                    extends Zend_Tag_Cloud_Decorator_HtmlTag
{
    /** @brief  Render an HTML version of a single tag item.
     *
     *  Note: This helper makes use of several 'view' variables:
     *          tags        A Model_TagSet instance representing the tags to be
     *                      presented;
     *          maxTags     The maximum number of tags to present [100];
     *          sortBy      The tag field to sort by ['tag'];
     *          sortOrder   Sort order ( ['ASC'] | 'DESC').
     *
     *  @return The HTML representation of a tag cloud.
     */
    public function render(Zend_Tag_ItemList $tags)
    {
        //Connexions::log("Connexions_View_Helper_HtmlTagItem::render...");

        if (($weightValues = $this->getClassList()) === null)
        {
            $weightValues = range($this->getMinFontSize(),
                                  $this->getMaxFontSize());
        }

        $tags->spreadWeightValues($weightValues);

        $result = array();

        foreach ($tags as $tag)
        {
            $isSelected = ($tag->getParam('selected') === true);
            $cssClass   = ($isSelected ? 'selected ' : '');
            $weightVal  = $tag->getParam('weightValue');
            $attribute  = '';

            /*
            Connexions::log(sprintf('Connexions_View_Helper_HtmlTagItem: '.
                                        'tag[ %s ], %sselected',
                                     $tag->getTitle(),
                                     ($isSelected ? '' : 'NOT ')) );
            // */

            if (($classList = $this->getClassList()) === null)
            {
                $attribute = sprintf('style="font-size: %d%s;"',
                                        $weightVal,
                                        $this->getFontSizeUnit());
            }
            else
            {
                $cssClass .= htmlspecialchars($weightVal);
            }

            if (! empty($cssClass))
                $cssClass = ' class="'. $cssClass .'"';

            $url    = $tag->getParam('url');
            $weight = number_format($tag->getWeight());
            if (empty($url))
                $tagHtml = sprintf('<span title="%s" %s%s>%s</span>',
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    htmlSpecialChars($tag->getTitle()));
            else
                $tagHtml = sprintf('<a href="%s" title="%s" %s%s>%s</a>',
                                    htmlSpecialChars($url),
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    htmlSpecialChars($tag->getTitle()));

            foreach ($this->getHtmlTags() as $key => $data)
            {
                if (is_array($data))
                {
                    $htmlTag    = $key;
                    $attributes = '';

                    foreach ($data as $param => $value)
                    {
                        $attributes .= ' '
                                   . $param . '="'
                                   .    htmlspecialchars($value) . '"';
                    }
                }
                else
                {
                    $htmlTag    = $data;
                    $attributes = '';
                }

                $tagHtml = sprintf('<%1$s%3$s>%2$s</%1$s>',
                                   $htmlTag, $tagHtml, $attributes);
            }

            $result[] = $tagHtml;
        }

        return $result;
    }
}
