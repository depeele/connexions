<?php
abstract class DbTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
{
    private $_connectionMock    = null;

    protected function getConnection()
    {
        if ($this->_connectionMock === null)
        {
            $db = Zend_Registry::get('db');
            $this->_connectionMock = $this->createZendDbConnection(
                                        $db, 'zfunittests');
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
        }

        return $this->_connectionMock;
    }

    //protected abstract function getDataSet();

    /** @brief  Assert that a model set is equivalent to an expected data set.
     *  @param  dsTable     The table-based dataset of expected data.
     *  @param  modelSet    The Connexions_Model_Set to compare.
     *
     */
    protected function assertModelSetEquals(
                //PHHPUnit_Extensions_Database_DataSet_IDataSet   $dataSet,
                PHPUnit_Extensions_Database_DataSet_ITable  $dsTable,
                Connexions_Model_Set                        $modelSet)
    {
        // Traverse all expected rows/columns
        $dsMeta   = $dsTable->getTableMetaData();
        $columns  = $dsMeta->getColumns();
        $rowCount = $dsTable->getRowCount();

        $this->assertEquals($rowCount, $modelSet->count());
        for ($idex = 0; $idex < $rowCount; $idex++)
        {
            foreach ($columns as $columnName)
            {
                $expected = $dsTable->getValue($idex, $columnName);
                $actual   = $modelSet[$idex]->$columnName;
                try
                {
                    $this->assertEquals($expected, $actual);
                    /*
                    printf ("Row %3d, column %-10s: "
                            .   "Expected[ %-15s ] != [ %-15s ]\n",
                            $idex, $columnName,
                            $expected, $actual);
                    // */
                }
                catch (Exception $e)
                {
                    throw new Exception("Expected value of {$expected} "
                                        . "for row {$idex} "
                                        . "column {$columnName}, "
                                        . "has a value of {$actual}");
                }
            }
        }
    }

    /** @brief  Assert that the current page of a paginated model set is
     *          equivalent to an expected data set.
     *  @param  dsTable     The table-based dataset of expected data.
     *  @param  paginator   The Zend_Paginator wrapped Connexions_Model_Set.
     *
     */
    protected function assertPaginatedSetEquals(
                //PHHPUnit_Extensions_Database_DataSet_IDataSet   $dataSet,
                PHPUnit_Extensions_Database_DataSet_ITable  $dsTable,
                Zend_Paginator                              $paginator)
    {
        // Traverse all expected rows/columns
        $dsMeta   = $dsTable->getTableMetaData();
        $columns  = $dsMeta->getColumns();
        $rowCount = $dsTable->getRowCount();

        //$this->assertEquals($rowCount, $paginator->getCurrentItemCount());
        for ($idex = 0; $idex < $rowCount; $idex++)
        {
            $item = $paginator->getItem($idex + 1);

            foreach ($columns as $columnName)
            {
                $expected = $dsTable->getValue($idex, $columnName);
                $actual   = $item->{$columnName};
                try
                {
                    /*
                    printf ("Row %3d, column %-10s: "
                            .   "Expected[ %-15s ] != [ %-15s ]\n",
                            $idex, $columnName,
                            $expected, $actual);
                    // */
                    $this->assertEquals($expected, $actual);
                }
                catch (Exception $e)
                {
                    throw new Exception("Expected value of {$expected} "
                                        . "for row {$idex} "
                                        . "column {$columnName}, "
                                        . "has a value of {$actual}");
                }
            }
        }

        /* Now, traverse all items in the paginated set to ensure none exsit
         * that aren't in the expected set.
         */
        foreach ($paginator as $idex => $item)
        {
            if ($item === null)
                // The first empty item in this page
                break;

            foreach ($columns as $columnName)
            {
                $expected = $dsTable->getValue($idex, $columnName);
                $actual   = $item->{$columnName};
                try
                {
                    /*
                    printf ("Paginator: Row %3d, column %-10s: "
                            .   "Expected[ %-15s ] != [ %-15s ]\n",
                            $idex, $columnName,
                            $expected, $actual);
                    // */
                    //$this->assertEquals($expected, $actual);
                }
                catch (Exception $e)
                {
                    throw new Exception("Expected value of {$expected} "
                                        . "for row {$idex} "
                                        . "column {$columnName}, "
                                        . "has a value of {$actual}");
                }
            }
        }
    }
}
