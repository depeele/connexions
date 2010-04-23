<?php
/** @file
 *
 *  An abstract base class for Domain Model input filter/validators.
 *
 */
abstract class Connexions_Model_Filter extends Zend_Filter_Input
{
    /** @brief  Create a new User Domain Model Filter. */
    public function __construct(array $data = null)
    {
        $this->setOptions(array(
            self::ALLOW_EMPTY       => false,
            self::MISSING_MESSAGE   => "'%field%' is missing",
            self::NOT_EMPTY_MESSAGE => "'%field%' must not be empty",
        ));

        if ($data)
        {
            $this->setData($data);
        }
    }
}

