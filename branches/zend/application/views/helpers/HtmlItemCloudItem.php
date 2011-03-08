<?php
/** @file
 *
 *  View helper to render a single Item within an HTML item cloud.
 */
class View_Helper_HtmlItemCloudItem
                    extends Zend_Tag_Cloud_Decorator_HtmlTag
{
    protected   $_view          = null;
    protected   $_showControls  = false;

    public function setView(Zend_View   $view)
    {
        /*
        Connexions::log("View_Helper_HtmlItemCloudItem::setView()");
        // */

        $this->_view = $view;
        return $this;
    }

    public function getView()
    {
        return $this->_view;
    }

    public function setShowControls($showControls)
    {
        $this->_showControls = $showControls;
        return $this;
    }

    public function getShowControls()
    {
        return $this->_showControls;
    }

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
        $classList = $weightValues = $this->getClassList();
        if ($weightValues === null)
        {
            $weightValues   = range($this->getMinFontSize(),
                                    $this->getMaxFontSize());
        }

        /*
        Connexions::log("View_Helper_HtmlItemCloudItem::render %d items, "
                        . "weight values[ %s ]...",
                        count($items),
                        Connexions::varExport($weightValues));
        // */

        $items->spreadWeightValues($weightValues);

        $res  = array(
            $this->getView()->partial('itemCloud_items.phtml',
                                      array(
                                        'helper' => $this,
                                        'items'  => &$items,
                                      )),
        );

        return $res;
    }
}
