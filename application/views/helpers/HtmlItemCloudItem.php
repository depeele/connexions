<?php
/** @file
 *
 *  View helper to render a single Item within an HTML item cloud.
 */
class View_Helper_HtmlItemCloudItem
                    extends Zend_Tag_Cloud_Decorator_HtmlTag
{
    /** @brief  Render an HTML version of a single Item.
     *
     *  Note: This helper makes use of several 'view' variables:
     *  @param  items   A Zend_Tag_ItemList / Connexions_Set_ItemList instance
     *                  representing the items to be presented.
     *
     *  @return The HTML representation of an item cloud.
     */
    public function render(Zend_Tag_ItemList $items)
    {
        //Connexions::log("View_Helper_HtmlItemCloudItem::render...");

        if (($weightValues = $this->getClassList()) === null)
        {
            $weightValues = range($this->getMinFontSize(),
                                  $this->getMaxFontSize());
        }

        $items->spreadWeightValues($weightValues);

        $result = array();

        foreach ($items as $item)
        {
            $isSelected = ($item->getParam('selected') === true);
            $cssClass   = ($isSelected
                                ? 'selected ui-corner-all ui-state-highlight '
                                : '');
            $weightVal  = $item->getParam('weightValue');
            $attribute  = '';

            /*
            Connexions::log('View_Helper_HtmlItemCloudItem: '
                            . 'item[ %s ], %sselected',
                            $item->getTitle(),
                            ($isSelected ? '' : 'NOT '));
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

            $url    = $item->getParam('url');
            $weight = number_format($item->getWeight());
            if (empty($url))
                $itemHtml = sprintf('<span title="%s" %s%s>%s</span>',
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    $item->getTitle());
            else
                $itemHtml = sprintf('<a href="%s" title="%s" %s%s>%s</a>',
                                    htmlSpecialChars($url),
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    $item->getTitle());

            /*
            Connexions::log("View_Helper_HtmlItemCloudItem::render() "
                            . "title[ %s ], weight[ %s ], weight title[ %s ]",
                            $item->getTitle(),
                            $item->getWeight(),
                            $weight);
            // */

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

                $itemHtml = sprintf('<%1$s%3$s>%2$s</%1$s>',
                                   $htmlTag, $itemHtml, $attributes);
            }

            $result[] = $itemHtml;
        }

        return $result;
    }
}
