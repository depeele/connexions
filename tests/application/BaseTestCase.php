<?php
/** @file
 *
 *  Base class for a non-database-related test case
 */
class BaseTestCase extends PHPUnit_Framework_TestCase
{
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
