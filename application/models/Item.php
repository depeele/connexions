<?php
/** @file
 *
 *  Model for the Item table.
 *
 */

class Model_Item extends Connexions_Model
{
    //protected   $_mapper    = 'Model_Mapper_Item';

    // The data for this Model
    protected   $_data      = array(
            'itemId'        => null,
            'url'           => '',
            'urlHash'       => '',
            'userCount'     => '',
            'ratingCount'   => '',
            'ratingSum'     => '',

            /* Note: these items are typically computed and may not be 
             *       persisted directly.
             */
            'userItemCount' => null,
            //'userCount'     => null,
            'itemCount'     => null,
            'tagCount'      => null,
    );

    /*************************************************************************
     * Connexions_Model abstract method implementations
     *
     */
    public function getId()
    {
        return ( $this->isBacked()
                    ? $this->itemId
                    : null );
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    public function __set($name, $value)
    {
        switch ($name)
        {
        case 'url':
            // If the url is set, update the urlHash
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

    /** @brief  Return an array version of this instance.
     *  @param  deep    Should any associated models be retrieved?
     *                      [ Connexions_Model::DEPTH_DEEP ] |
     *                        Connexions_Model::DEPTH_SHALLOW
     *  @param  public  Include only "public" information?
     *                      [ Connexions_Model::FIELDS_PUBLIC ] |
     *                        Connexions_Model::FIELDS_ALL
     *
     *  @return An array representation of this Domain Model.
     */
    public function toArray($deep   = self::DEPTH_DEEP,
                            $public = self::FIELDS_PUBLIC)
    {
        $data = $this->_data;

        if ($public)
        {
            unset($data['itemId']);
        }

        return $data;
    }
}
