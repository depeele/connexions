<?php
/** @file
 *
 *  View helper to render a set of Items within an HTML item cloud.
 */
class View_Helper_HtmlItemCloudItem
                    extends Zend_Tag_Cloud_Decorator_HtmlTag
{
    protected   $_view          = null;
    protected   $_showControls  = false;
    protected   $_itemType      = null;
    protected   $_viewer        = null;

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

    public function setItemType($itemType)
    {
        $this->_itemType = $itemType;
        return $this;
    }

    public function getItemType()
    {
        return $this->_itemType;
    }

    public function setViewer($viewer)
    {
        $this->_viewer = $viewer;
        return $this;
    }

    public function getViewer()
    {
        return $this->_viewer;
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
