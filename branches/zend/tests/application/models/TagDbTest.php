<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Tag.php';

class TagDbTest extends DbTestCase
{
    private $_tag1  = array(
            'tagId'         => 1,
            'tag'           => 'security',

            'userItemCount' => 0,
            'userCount'     => 0,
            'itemCount'     => 0,
    );
    private $_tag74 = array(
            'tagId'         => 74,
            'tag'           => 'yat',

            'userItemCount' => 0,
            'userCount'     => 0,
            'itemCount'     => 0,
    );

    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
    }

    public function testTagInsertedIntoDatabase()
    {
        $expected = $this->_tag74;

        $tag = new Model_Tag( array( 'tag' => $expected['tag'] ) );
        $tag = $tag->save();

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('tag', 'SELECT * FROM tag');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/tagInsertAssertion.xml'),
            $ds);
    }

    public function testTagRetrieveByUnknownId()
    {
        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( 80 );

        $this->assertEquals(null, $tag);
    }

    public function testTagRetrieveById1()
    {
        $expected = $this->_tag1;

        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( $this->_tag1['tagId'] );

        $this->assertTrue  ( $tag->isBacked() );
        $this->assertTrue  ( $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagIdentityMap()
    {
        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( $this->_tag1['tagId'] );
        $tag2  = $mapper->find( $this->_tag1['tagId'] );

        $this->assertSame  ( $tag, $tag2 );
    }

    public function testTagIdentityMap2()
    {
        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( $this->_tag1['tagId'] );
        $tag2  = $mapper->find( $this->_tag1['tag'] );

        $this->assertSame  ( $tag, $tag2 );
    }

    public function testTagInsertUpdatedIdentityMap()
    {
        $expected = $this->_tag74;

        $tag = new Model_Tag( array( 'tag' => $expected['tag'] ) );
        $tag = $tag->save();

        $tag2 = $tag->getMapper()->find( $tag->tagId );

        $this->assertSame  ( $tag, $tag2 );
    }

    public function testTagGetId()
    {
        $expected = $this->_tag1['tagId'];

        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( $expected );

        $this->assertEquals($expected, $tag->getId());
    }

    public function testTagRetrieveById2()
    {
        $expected = $this->_tag1;

        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( array('tagId' => $expected['tagId']) );
        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagRetrieveByName1()
    {
        $expected = $this->_tag1;

        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( $expected['tag'] );
        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagRetrieveByName2()
    {
        $expected = $this->_tag1;

        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( array('tag' => $expected['tag']) );
        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagDeletedFromDatabase()
    {
        $mapper = new Model_Mapper_Tag( );
        $tag   = $mapper->find( 1 );

        $tag->delete();

        // Make sure the tag instance has been invalidated
        $this->assertTrue( ! $tag->isBacked() );
        $this->assertTrue( ! $tag->isValid() );

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('tag', 'SELECT * FROM tag');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/tagDeleteAssertion.xml'),
            $ds);
    }

    public function testTagFullyDeletedFromDatabase()
    {
        $mapper = new Model_Mapper_Tag( );
        $tag    = $mapper->find( 1 );

        $tag->delete();

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('user',        'SELECT * FROM user'
                                     .  ' ORDER BY userId ASC');
        $ds->addTable('item',        'SELECT * FROM item'
                                     .  ' ORDER BY itemId ASC');
        $ds->addTable('tag',         'SELECT * FROM tag'
                                     .  ' ORDER BY tagId ASC');

        $ds->addTable('userAuth',    'SELECT * FROM userAuth'
                                     .  ' ORDER BY userId,authType ASC');
        $ds->addTable('userItem',    'SELECT * FROM userItem'
                                     .  ' ORDER BY userId,itemId ASC');
        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId'
                                     .  ' FROM userTagItem'
                                     .  ' ORDER BY userId,itemId,tagId ASC');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                      dirname(__FILE__) .'/_files/tagDeleteFullAssertion.xml'),
            $ds);
    }

    public function testTagSet()
    {
        $mapper = new Model_Mapper_Tag( );
        $tags   = $mapper->fetch();

        // Check the database consistency
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
                    $this->getConnection()
        );

        $ds->addTable('tag',        'SELECT * FROM tag'
                                     .  ' ORDER BY tagId ASC');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                      dirname(__FILE__) .'/_files/tagSetAssertion.xml'),
            $ds);
    }

    public function testTagSetCount()
    {
        $expectedCount = 72;    // Remember, 1 was deleted above
        $expectedTotal = 72;    // Remember, 1 was deleted above

        $mapper = new Model_Mapper_Tag( );
        $tags  = $mapper->fetch();

        $this->assertEquals($expectedCount, $tags->count());
        $this->assertEquals($expectedTotal, $tags->getTotalCount());
    }

    public function testTagSetLimitCount()
    {
        $offset        = 25;
        $expectedCount = 20;
        $expectedTotal = 72;    // Remember, 1 was deleted above

        $mapper = new Model_Mapper_Tag( );
        $tags  = $mapper->fetch(null,
                                 array('tag ASC'),  // order
                                 $expectedCount,    // count
                                 $offset);          // offset

        $this->assertEquals($expectedCount, $tags->count());
        $this->assertEquals($expectedTotal, $tags->getTotalCount());
    }

    /*
    public function testTagSetAsTag_ItemList()
    {
        $mapper = new Model_Mapper_Tag( );
        $tags  = $mapper->fetch();

        $tagList = $tags->toTag_ItemList();

        $tagList->spreadWeightValues(range(1,10));


        $this->assertEquals($expectedCount, $tagList->count());
        $this->assertEquals($expectedTotal, $tags->getTotalCount());
    }
    */
}
