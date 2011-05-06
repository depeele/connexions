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

    protected function tearDown()
    {
        /* Since these tests setup and teardown the database for each new test,
         * we need to clean-up any Identity Maps that are used in order to 
         * maintain test validity.
         */
        $tMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tMapper->flushIdentityMap();

        parent::tearDown();
    }


    public function testTagInsertedIntoDatabase()
    {
        $expected = $this->_tag74;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->getModel( array( 'tag' => $expected['tag'] ) );
        $this->assertTrue  ($tag instanceof Model_Tag);

        $tag    = $tag->save();
        $this->assertTrue  ($tag instanceof Model_Tag);
        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));

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
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => 80) );

        $this->assertEquals(null, $tag);
    }

    public function testTagRetrieveById1()
    {
        $expected = $this->_tag1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => $this->_tag1['tagId']) );

        $this->assertTrue  ( $tag->isBacked() );
        $this->assertTrue  ( $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagIdentityMap()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => $this->_tag1['tagId']) );
        $tag2   = $mapper->find( array('tagId' => $this->_tag1['tagId']) );

        $this->assertSame  ( $tag, $tag2 );
    }

    public function testTagIdentityMap2()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => $this->_tag1['tagId'] ));
        $tag2   = $mapper->find( array('tagId' => $this->_tag1['tag'] ));

        $this->assertSame  ( $tag, $tag2 );
    }

    public function testTagInsertUpdatedIdentityMap()
    {
        $expected = $this->_tag74;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->getModel( array( 'tag' => $expected['tag'] ) );
        $tag    = $tag->save();

        $tag2   = $tag->getMapper()->find( array('tagId' => $tag->tagId ));

        $this->assertSame  ( $tag, $tag2 );
    }

    public function testTagGetId()
    {
        $expected = $this->_tag1['tagId'];

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => $expected ));

        $this->assertEquals($expected, $tag->getId());
    }

    public function testTagRetrieveById2()
    {
        $expected = $this->_tag1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => $expected['tagId']) );
        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagRetrieveByName1()
    {
        $expected = $this->_tag1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tag' => $expected['tag'] ));
        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagRetrieveByName2()
    {
        $expected = $this->_tag1;

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tag' => $expected['tag']) );
        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagDeletedFromDatabase()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => 1 ));
        $this->assertTrue ( $tag->isBacked() );
        $this->assertTrue ( $tag->isValid() );

        $tag->delete();

        // Make sure the tag instance has been invalidated
        $this->assertFalse( $tag->isBacked() );
        $this->assertFalse( $tag->isValid() );

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
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tag    = $mapper->find( array('tagId' => 1 ));

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
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch();

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/tagSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('tag'), $tags );
    }

    public function testTagSetCount()
    {
        $expectedCount = 72;    // Remember, 1 was deleted above
        $expectedTotal = 72;    // Remember, 1 was deleted above

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch();

        $this->assertEquals($expectedCount, $tags->count());
        $this->assertEquals($expectedTotal, $tags->getTotalCount());
    }

    public function testTagSetLimitCount()
    {
        $offset        = 25;
        $expectedCount = 20;
        $expectedTotal = 72;    // Remember, 1 was deleted above

        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch(null,
                                 array('tag ASC'),  // order
                                 $expectedCount,    // count
                                 $offset);          // offset

        $this->assertEquals($expectedCount, $tags->count());
        $this->assertEquals($expectedTotal, $tags->getTotalCount());
    }

    /*
    public function testTagSetAsTag_ItemList()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch();

        $tagList = $tags->toTag_ItemList();

        $tagList->spreadWeightValues(range(1,10));


        $this->assertEquals($expectedCount, $tagList->count());
        $this->assertEquals($expectedTotal, $tags->getTotalCount());
    }
    */

    public function testTagSetUnset()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch();

        //$this->_printTags($tags);   echo "\n";

        // Attempt to unset a few tags.
        unset($tags[15]);
        //$this->_printTags($tags);   echo "\n";

        unset($tags[20]);
        //$this->_printTags($tags);   echo "\n";

        unset($tags[25]);
        //$this->_printTags($tags);   echo "\n";

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/tagSetUnsetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('tag'), $tags );
    }

    public function testTagSetPush()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch();

        //Connexions::log("testTagSetPush: 1: tags[ %s ]", $tags->debugDump());

        // Attempt to add a few tags -- raw and instance.
        $tags->append( array('tag' => 'New Tag 1') );
        //Connexions::log("testTagSetPush: 2: tags[ %s ]", $tags->debugDump());

        $newTag = $mapper->getModel( array('tag' => 'New Tag 2') );
        $tags->append( $newTag );
        //Connexions::log("testTagSetPush: 3: tags[ %s ]", $tags->debugDump());

        /*
        printf ("new Tag { %s }\n", $newTag->debugDump());
        $this->_printTags($tags);   echo "\n";
        // */

        // Save any unsaved tags from this set
        $tags->save();

        //echo $tags->debugDump();

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/tagSetAppendAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('tag'), $tags );
    }

    public function testTagSetSort()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tags   = $mapper->fetch();

        function __sort_by_tag($item1, $item2)
        {
            // Incoming items SHOULD be Connexions_Model instances
            return strcasecmp($item1->tag, $item2->tag);
        }

        /*
        $this->_printTags($tags);   echo "\n";
        // */

        // We could also use 'Service_Tag::sort_by_tag'
        $tags->usort('__sort_by_tag');

        /*
        echo "Sorted by tag:\n";
        $this->_printTags($tags);   echo "\n";
        // */

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/tagSetSortAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('tag'), $tags );
    }

    protected function _printTags(Model_Set_Tag $tags)
    {
        printf ("%d tags: [ ", count($tags));
        foreach ($tags as $idex => $tag)
        {
            printf ("%s%02d:%s", ($idex > 0 ? ', ' : ''), $idex, $tag->tag);
        }
        echo " ]\n";
    }
}
