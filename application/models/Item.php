<?php
/** @file
 *
 *  Model for the Item table.
 *
 */

class Model_Item extends Model_Taggable
{
    /* inferred via classname
    protected   $_mapper    = 'Model_Mapper_Item'; */

    // The data for this Model
    protected   $_data      = array(
            'itemId'        => null,
            'url'           => null,
            'urlHash'       => null,

            /* Note: these items are typically computed and may not be 
             *       persisted directly.
             */
            'userCount'     => 0,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );

    // Associated Domain Model instances
    protected   $_users     = null;
    protected   $_tags      = null;

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */

    /** @brief  Retrieve the unique identifier for this instance.  This MAY 
     *          return an array of identifiers as key/value pairs.
     *
     *  @return The unique identifier.
     */
    public function getId()
    {
        return ( $this->itemId );
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    /** @brief  Get a value of the given field.
     *  @param  name    The field name.
     *
     *  @return The field value (null if invalid).
     */
    public function __get($name)
    {
        switch ($name)
        {
        case 'tags':
            $val = $this->_tags;
            if ( $val === null )
            {
                // Load the Model_Tag array now.
                $val = $this->getMapper()->getTags( $this );
                $this->_tags = $val;
            }
            break;

        default:
            $val = parent::__get($name);
            break;
        }

        return $val;
    }

    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
        $needSave = false;
        switch ($name)
        {
        case 'url':
            /* Whenever the url is modified, update the urlHash
             *
             * :XXX: Should we normalize the URL here?
             *          $value = Connexions::normalizeUrl($value);
             */
            $hash = Connexions::md5Url($value);
            parent::__set('urlHash', $hash);
            break;

        case 'urlHash':
            if (! empty($this->url))
            {
                // Force the url hash to the hash of the current url.
                $newValue = Connexions::md5Url($this->url);

                if ($value !== $newValue)
                {
                    Connexions::log("Model_Item::__set(%s, %s): "
                                    . "Rewrite the hash to "
                                    . "'%s' to match the existing URL",
                                    $name, $value, $newValue);
                    $value = $newValue;

                    if ($this->isBacked())
                        $needSave = true;
                }
            }

            // ALSO set the Zend_Tag_Taggable parameter 'urlId'
            $this->setParam('urlId', $value);
            break;
        }

        parent::__set($name, $value);
        if ($needSave)
            $this->save();

        return $this;
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if (! empty($this->url))
            return $this->_data['url'];
        else if (! empty($this->urlHash))
            return $this->_data['urlHash'];

        return parent::__toString();
    }

    /**********************************************
     * Statistics related methods
     *
     */

    /** @brief  Update external-table statistics related to this Item instance:
     *              userCount, ratingCount, ratingSum
     *
     *  @return $this for a fluent interface
     */
    public function updateStatistics()
    {
        $this->getMapper()->updateStatistics( $this );

        return $this;
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface (via Model_Taggable)
     *
     */

    /** @brief  Return an HTML-safe version of this items title.
     *
     *  @return An HTML-safe title.
     */
    public function getTitle()
    {
        $title = (String)($this->url);
        $orig  = $title;

        // Convert the URL to a wrappable HTML string.
        $title = Connexions::wrappableUrl($title);

        /*
        Connexions::log("Model_Item::getTitle(): [ %s ] == [ %s ]",
                        $orig, $title);
        // */

        return $title;
    }

    public function getWeight()
    {
        $weight = $this->getParam('weight');

        if ($weight === null)
        {
            // Best guess depending on what values are set
           $weight = 0;
           if (isset($this->weight))
               $weight = $this->weight;
           else if ($this->ratingCount > 0)
           {
               $weight = $this->ratingSum / $this->ratingCount;
           }
           else if (isset($this->userItemCount))
               $weight = $this->userItemCount;
           else if (isset($this->userCount))
               $weight = $this->userCount;

            $this->setWeight($weight);
        }

        return (Float)$weight;
    }
}
