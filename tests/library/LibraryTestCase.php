<?php
/** @file
 *
 *  Base class for a non-database-related test case
 */
class LibraryTestCase extends PHPUnit_Framework_TestCase
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
