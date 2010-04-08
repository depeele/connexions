<?php
/** @file
 *
 *  User Domain Model.
 *
 *  Note: Since we pull tag sets with varying weight measuremennts so, at least
 *        for now, DO NOT change the base class from Connexions_Model to
 *        Connexions_Model_Cached to make it cacheable.
 */

//class Model_User extends Connexions_Model_Cached
class Model_User extends Connexions_Model
                    implements  Zend_Tag_Taggable
{
    // Instance cache
    protected   $_tags      = null;
    protected   $_userItems = null;


    public function __construct(array $config)
    {
        if (! isset($config['apiKey']))
            $config = $this->genApiKey();

        return parent::__construct($config);
    }

    public function getId()
    {
        return ($this->userId);
    }

    /*************************************************************************
     * Connexions_Model overrides
     *
     */

    public function __get($name)
    {
        switch ($name)
        {
        case 'tags':        $val = $this->_tags();          break;
        case 'userItems':   $val = $this->_userItems();     break;
        default:            $val = parent::__get($name);    break;
        }

        return $val;
    }

    public function __set($name, $value)
    {
        $res = false;
        switch ($name)
        {
        case 'password':
            $value = md5( $this->name .':'. $value);
            $res   =  parent::__set($name, $value);
            break;

        default:
            $res =  parent::__set($name, $value);
            break;
        }

        return $res;
    }

    /** @brief  Return a string representation of this instance.
     *
     *  @return The string-based representation.
     */
    public function __toString()
    {
        if (! empty($this->name))
            return $this->name;

        return parent::__toString();
    }

    /** @brief  Return an associative array representing this item.
     *  @param  public  Include only "public" information?
     *
     *  @return An associaitve array.
     */
    public function toArray($public = true)
    {
        $data = $this->_data;
        if ($public)
        {
            // Remove non-public information
            unset($data['userId']);
            unset($data['password']);
            unset($data['apiKey']);
        }

        return $data;
    }

    /** @brief  Invalidate our internal cache of sub-instances.
     *
     *  @return $this for a fluent interface
     */
    public function invalidateCache()
    {
        $this->_tags      = null;
        $this->_userItems = null;

        return $this;
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
        $title = (String)($this->name);

        return $title;
    }

    public function getWeight()
    {
        $weight = 0;
        if (isset($this->weight))
            $weight = (Float)($this->weight);
        else if (isset($this->tagCount))
            $weight = (Float)($this->tagCount);
        else if (isset($this->totalItems))
            $weight = (Float)($this->totalItems);

        return $weight;
    }

    /*************************************************************************
     * Protected helpers
     *
     */
    protected function _tags()
    {
        if ($this->_tags === null)
        {
            if (@isset($this->_record['userId']))
            {
                $this->_tags =
                    new Model_TagSet(array($this->_record['userId']));
            }
        }

        return $this->_tags;
    }

    protected function _userItems()
    {
        if ($this->_userItems === null)
        {
            if (@isset($this->_record['userId']))
            {
                $this->_userItems =
                    new Model_UserItemSet(null, // tagIds
                                          array($this->_record['userId']));
            }
        }

        return $this->_userItems;
    }

    /*************************************************************************
     * Static methods
     *
     */

    /** @brief  Generate a new API key with characters [a-zA-Z0-9].
     *  @param  len The length of the new key [ 10 ].
     *
     *  @return The new key.
     */
    public static function genApiKey($len = 10)
    {
        $chars    = array_merge(range('a','z'),range('A','Z'),range('0','9'));
        $nChars   = count($chars);
        $key      = '';

        list($ms) = explode(' ', microtime());
        srand($ms * 100000);

        for ($idex = 0; $idex < $len; $idex++)
        {
            $key .= $chars[ rand(0, $nChars) ];
        }

        return $key;
    }
}
