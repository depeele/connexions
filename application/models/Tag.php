<?php
/** @file
 *
 *  Model for the Tag table.
 */

class Model_Tag extends Connexions_Model
                implements  Zend_Tag_Taggable
{
    //protected   $_mapper    = 'Model_Mapper_Tag';

    // The data for this Model
    protected   $_data      = array(
            'tagId'         => null,
            'tag'           => '',

            /* Note: these items are typically computed and may not be 
             *       persisted directly.
             */
            'userItemCount' => null,
            'userCount'     => null,
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
                    ? $this->tagId
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
        case 'tag':
            // Normalize the tag name
            $value = strtolower($value);
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
        if (! empty($this->tag))
            return $this->tag;

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

        if ($public === self::FIELDS_PUBLIC)
        {
            unset($data['tagId']);
        }

        return $data;
    }

    /*************************************************************************
     * Zend_Tag_Taggable Interface
     *
     */
    protected       $_params    = array();

    public function getParam($name)
    {
        // weightValue, url, selected
        $val = (@isset($this->_params[$name])
                    ? $this->_params[$name]
                    : null);
        return $val;
    }

    public function setParam($name, $value)
    {
        // weightValue, url, selected
        $this->_params[$name] = $value;
    }

    public function getTitle()
    {
        $title = (String)($this->tag);

        return $title;
    }

    public function getWeight()
    {
        if (@isset($this->weight))
            $weight = (Float)($this->weight);
        else
            $weight = (Float)($this->userItemCount);

        /*
        Connexions::log(
                sprintf("Model_Tag::getWeight: "
                            . "weight[ %s ], "
                            . "userItemCount[ %s ] == [ %s ]",
                                $this->weight,
                                $this->userItemCount,
                                $weight));
        // */

        return $weight;
    }
}
