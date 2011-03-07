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
        $fontUnit  = null;
        $classList = $weightValues = $this->getClassList();
        if ($weightValues === null)
        {
            $fontUnit     = $this->getFontSizeUnit();
            $weightValues = range($this->getMinFontSize(),
                                  $this->getMaxFontSize());
        }

        // /*
        Connexions::log("View_Helper_HtmlItemCloudItem::render %d items, "
                        . "weight values[ %s ]...",
                        count($items),
                        Connexions::varExport($weightValues));
        // */

        $items->spreadWeightValues($weightValues);

        // Process the HTML tags once
        $tags = array();
        $enc  = $this->getEncoding();
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
                               .    htmlspecialchars($value, ENT_COMPAT, $enc)
                               . '"';
                }
            }
            else
            {
                $htmlTag    = $data;
                $attributes = '';
            }

            array_push($tags, array('tag'   => $htmlTag,
                                    'attrs' => $attributes));
        }


        // Generate HTML results
        $result = array();
        foreach ($items as $item)
        {
            $url        = $item->getParam('url');
            $title      = $item->getTitle();
            $weight     = number_format($item->getWeight());
            $isSelected = ($item->getParam('selected') === true);
            $cssClass   = ($isSelected
                                ? 'selected ui-corner-all ui-state-highlight '
                                : '');
            $weightVal  = $item->getParam('weightValue');
            $attribute  = '';

            // /*
            Connexions::log('View_Helper_HtmlItemCloudItem: '
                            . 'title[ %s ], url[ %s ], '
                            . 'weight[ %s ], weight value[ %s ], '
                            . '%sselected',
                            $title, $url,
                            $weight, $weightVal,
                            ($isSelected ? '' : 'NOT '));
            // */

            if ($classList === null)
            {
                $attribute = sprintf('style="font-size: %d%s;"',
                                        $weightVal, $fontUnit);
            }
            else
            {
                $cssClass .= htmlspecialchars($weightVal, ENT_COMPAT, $enc);
            }

            if (! empty($cssClass))
                $cssClass = ' class="'. $cssClass .'"';

            if (empty($url))
            {
                $itemHtml = sprintf('<span title="%s" %s%s>%s</span>',
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    $title);
            }
            else
            {
                $itemHtml = sprintf('<a href="%s" title="%s" %s%s>%s</a>',
                                    htmlspecialchars($url, ENT_COMPAT, $enc),
                                    $weight,
                                    $cssClass,
                                    $attribute,
                                    $title);
            }

            /*
            Connexions::log("View_Helper_HtmlItemCloudItem::render() "
                            . "title[ %s ], weight[ %s ], weight title[ %s ]",
                            $title,
                            $item->getWeight(),
                            $weight);
            // */

            foreach ($tags as $html)
            {
                $itemHtml = sprintf('<%1$s%3$s>%2$s</%1$s>',
                                   $html['tag'], $itemHtml, $html['attrs']);
            }

            $result[] = $itemHtml;
        }

        return $result;
    }
}
