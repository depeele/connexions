<?php
/** @file
 *
 *  Model for the Item table.
 *
 */

class Model_Item extends Model_Base
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
    public function __get($name)
    {
        if (array_key_exists($name, $this->_data))
            return parent::__get($name);

        if ($name === 'ratingAvg')
        {
            $sum   = $this->ratingSum;
            $count = $this->ratingCount;
            if ($count > 0)
                $val = $sum / $count;
            else
                $val = 0;

            return $val;
        }

        // return null;
    }
     */

    /** @brief  Set the value of the given field.
     *  @param  name    The field name.
     *  @param  value   The new value.
     *
     *  @return $this for a fluent interface.
     */
    public function __set($name, $value)
    {
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
                }
            }
            break;
        }

        return parent::__set($name, $value);
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if (! empty($this->url))
            return $this->_record['url'];
        else if (! empty($this->urlHash))
            return $this->_record['urlHash'];

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

}
