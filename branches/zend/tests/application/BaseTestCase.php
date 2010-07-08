<?php
/** @file
 *
 *  Base class for a non-database-related test case
 */
class BaseTestCase extends PHPUnit_Framework_TestCase
{
    protected static    $toArray_deep_all       = array(
        'deep'      => true,
        'public'    => false,
        'dirty'     => false,
    );
    protected static    $toArray_shallow_all    = array(
        'deep'      => false,
        'public'    => false,
        'dirty'     => false,
    );

    public $application;

    public function __construct(       $name     = NULL,
                                 array $data     = array(),
                                       $dataName = '')
    {
        global  $application;
        $this->application = $application;

        parent::__construct($name, $data, $dataName);
    }
}
