<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Bookmark.php';

/**
 *  @group Mappers
 */
class BookmarkDbTest extends DbTestCase
{
    protected   $_user1 = array(
            'userId'        => 1,
            'name'          => 'User1',
            'fullName'      => 'Random User 1',
            'email'         => 'User1@home.com',
            'apiKey'        => 'edOEMfwY6d',
            'pictureUrl'    => '/connexions/images/User1.png',
            'profile'       => null,
            'lastVisit'     => '2007-04-12 12:38:02',
            'lastVisitFor'  => '0000-00-00 00:00:00',

            'totalTags'     => 24,
            'totalItems'    => 5,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_item1 = array(
            'itemId'        => 1,
            'url'           => 'http://www.clipperz.com/',
            'urlHash'       => '383cb614a2cc9247b86cad9a315d02e3',

            'userCount'     => 1,
            'ratingCount'   => 1,
            'ratingSum'     => 1,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_item6 = array(
            'itemId'        => 6,
            'url'           => 'http://arstechnica.com/news.ars/post/20070325-ibm-doubles-cpu-cooling-capabilities-with-simple-manufacturing-change.html',
            'urlHash'       => 'ba7215776973fafa3f5b0bfd263e3ec2',

            'userCount'     => 3,
            'ratingCount'   => 2,
            'ratingSum'     => 7,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_tags1 = array(
            array('tagId' =>       10,
                  'tag'   => 'ajax',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       72,
                  'tag'   => 'cryptography',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        5,
                  'tag'   => 'identity',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        7,
                  'tag'   => 'online',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        8,
                  'tag'   => 'password',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        2,
                  'tag'   => 'passwords',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        4,
                  'tag'   => 'privacy',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        1,
                  'tag'   => 'security',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        9,
                  'tag'   => 'storage',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       71,
                  'tag'   => 'test',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>       11,
                  'tag'   => 'tools',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
            array('tagId' =>        6,
                  'tag'   => 'web2.0',
                                         'userItemCount' => 1,
                                         'userCount'     => 1,
                                         'itemCount'     => 1,
                                         'tagCount'      => 1),
    );
    protected   $_bookmark1 = array(
            'userId'        => null,    // $this->_user1,
            'itemId'        => null,    // $this->_item1,

            'name'          => 'More than a password manager | Clipperz',
            'description'   => 'Testing 1,2 3, 4...',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 1,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-07-22 10:00:00',
    );

    protected function getDataSet()
    {
        //Connexions::log("BookmarkDbTest::getDataSet()");

        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
    }

    protected function tearDown()
    {
        /* Since these tests setup and teardown the database for each new test,
         * we need to clean-up any Identity Maps that are used in order to 
         * maintain test validity.
         */
        $uMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $uMapper->flushIdentityMap();

        $iMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $iMapper->flushIdentityMap();

        $tMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tMapper->flushIdentityMap();

        $bMapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bMapper->flushIdentityMap();


        parent::tearDown();
    }

    public function testBookmarkRetrieveById1()
    {
        $expected  = $this->_bookmark1;
        $expected['userId']     = $this->_user1['userId'];
        $expected['itemId']     = $this->_item1['itemId'];
        $expected['user']       = $this->_user1;
        $expected['item']       = $this->_item1;
        $expected['tags']       = $this->_tags1;
        $expected['isFavorite'] = ($expected['isFavorite'] ? 1 : 0);
        $expected['isPrivate']  = ($expected['isPrivate']  ? 1 : 0);

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $expected['userId'],
                                          'itemId' => $expected['itemId']) );

        /*
        //Connexions::log("testBookmarkRetrieveById1: bookmark[ %s ]",
        //                Connexions::varExport( $bookmark->toArray() ));

        printf ("Bookmark( %d, %d ):\n%s\n",
                $expected['userId'], $expected['itemId'],
                $bookmark->debugDump());
        // */

        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_deep_all));
    }

    public function testBookmarkIdentityMap()
    {
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $id     = array( 'userId' => $this->_user1['userId'],
                         'itemId' => $this->_item1['itemId'] );

        $bookmark  = $mapper->find( $id );
        $bookmark2 = $mapper->find( $id );

        $this->assertSame( $bookmark, $bookmark2 );
    }

    public function testBookmarkInvalidate()
    {
        $expected  = $this->_bookmark1;
        $expected['userId'] = $this->_user1['userId'];
        $expected['itemId'] = $this->_item1['itemId'];


        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $expected['userId'],
                                          'itemId' => $expected['itemId']) );

        // Force retrievals
        $user = $bookmark->user;
        $this->assertEquals($this->_user1,
                            $user->toArray(self::$toArray_shallow_all));

        $item = $bookmark->item;
        $this->assertEquals($this->_item1,
                            $item->toArray(self::$toArray_shallow_all));

        $tags = $bookmark->tags;
        $tags2 = array();
        foreach ($tags as $idex => $tag)
        {
            array_push($tags2,
                       $tag->toArray(self::$toArray_shallow_all));
        }
        $this->assertEquals($this->_tags1, $tags2);

        // Invalidate our cache
        $bookmark->invalidateCache();

        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));
    }

    public function testBookmarkUser()
    {
        $expected = $this->_user1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $this->_user1['userId'],
                                          'itemId' => $this->_item1['itemId'] ) );

        $user = $bookmark->user;
        $this->assertEquals($expected,
                            $user->toArray(self::$toArray_shallow_all));
    }

    public function testBookmarkItem()
    {
        $expected = $this->_item1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $this->_user1['userId'],
                                          'itemId' => $this->_item1['itemId'] ) );

        $item = $bookmark->item;
        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testBookmarkTags()
    {
        $expected = $this->_tags1;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $this->_user1['userId'],
                                          'itemId' => $this->_item1['itemId'] ) );

        $tags = $bookmark->tags;

        $this->assertEquals(count($expected), count($tags));

        //printf ("\n %d tags:\n", count($tags));
        foreach ($tags as $idex => $tag)
        {
            /*
            printf (" #%2d: [ %4d, %-20s: ui:%3d, u:%3d, i:%3d ]\n",
                    $idex,
                    $tag->tagId, $tag->tag,
                    $tag->userItemCount, $tag->userCount,
                    $tag->itemCount);
            // */

            $this->assertEquals($expected[$idex],
                                $tag->toArray(self::$toArray_shallow_all));
        }
    }

    public function testBookmarkUpdate()
    {
        $expected                = $this->_bookmark1;
        $expected['userId']      = $this->_user1['userId'];
        $expected['itemId']      = $this->_item1['itemId'];
        $expected['name']        = 'Clipperz';
        $expected['description'] = 'More than a password manager';
        $expected['rating']      = 2;
        $expected['isFavorite']  = 1;
        $expected['isPrivate']   = 0;

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $expected['userId'],
                                          'itemId' => $expected['itemId']) );

        //printf ("Bookmark: [ %s ]\n", $bookmark->debugDump());

        $bookmark->name        = $expected['name'];
        $bookmark->description = $expected['description'];
        $bookmark->rating      = $expected['rating'];
        $bookmark->isFavorite  = $expected['isFavorite'];
        $bookmark->isPrivate   = $expected['isPrivate'];

        //printf ("Bookmark: [ %s ]\n", $bookmark->debugDump());

        $this->assertTrue(  $bookmark->isBacked() );
        $this->assertTrue(  $bookmark->isValid() );
        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));

        $bookmark = $bookmark->save();

        // bookmkark.updatedOn and user.lastVisit are dynamically updated
        $expected['updatedOn']         = $bookmark->updatedOn;
        //$expected['user']['lastVisit'] = $bookmark->user->lastVisit;

        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));

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


        /*********
         * Modify 'updateOn' in our expected set for the target row since
         * it's dynamic...
         */
        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/bookmarkUpdateAssertion.xml');
        $et = $es->getTable('userItem');
        $et->setValue(0, 'updatedOn', $expected['updatedOn']);

        /*
        $et = $es->getTable('user');
        $et->setValue(0, 'lastVisit', $bookmark->user->lastVisit);
        // */

        $this->assertDataSetsEqual($es, $ds);
    }

    public function testBookmarkFullyDeletedFromDatabase()
    {
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->find( array( 'userId' => $this->_user1['userId'],
                                          'itemId' => $this->_item1['itemId']));
        $user     = $bookmark->user;

        $bookmark->delete();

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

        // user.lastVisit is dynamic
        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/bookmarkDeleteFullAssertion.xml');
        /*
        $et = $es->getTable('user');
        $et->setValue(0, 'lastVisit', $user->lastVisit);
        // */

        $this->assertDataSetsEqual($es, $ds);
    }

    protected   $_newBookmark = array(
            'userId'        => null,
            'itemId'        => null,

            'name'          => 'New Bookmark',
            'description'   => 'This is a new bookmark',
            'rating'        => 3,
            'isFavorite'    => 1,
            'isPrivate'     => 0,
            'taggedOn'      => null,
            'updatedOn'     => null,
    );
    protected   $_tags2 = array(
            array('tagId'           => 1,
                  'tag'             => 'security',
                  'userItemCount'   => null,
                  'userCount'       => null,
                  'itemCount'       => null),
            array('tagId'           => 31,
                  'tag'             => 'cooling',
                  'userItemCount'   => null,
                  'userCount'       => null,
                  'itemCount'       => null),
    );

    public function testBookmarkCreateNoTagsShouldFail()
    {
        $expected             = $this->_newBookmark;
        $expected['userId']   = $this->_user1['userId'];
        $expected['itemId']   = $this->_item6['itemId'];
        $expected['taggedOn'] = date('Y-m-d h:i:s');

        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->getModel( array(
            'userId'      => $expected['userId'],
            'itemId'      => $expected['itemId'],
            'name'        => $expected['name'],
            'description' => $expected['description'],
            'rating'      => $expected['rating'],
            'isFavorite'  => $expected['isFavorite'],
            'isPrivate'   => $expected['isPrivate'],
            'taggedOn'    => $expected['taggedOn']
        ));


        /*
        $this->assertEquals($expected,
                            $bookmark->toArray(self::$toArray_shallow_all));
        */
        try
        {
            $bookmark = $bookmark->save();
        }
        catch (Exception $e)
        {
            $this->assertEquals('Bookmarks require at least one tag',
                                $e->getMessage());
        }
    }

    public function testBookmarkCreateNew()
    {
        $expected             = $this->_newBookmark;
        $expected['userId']   = $this->_user1['userId'];
        $expected['itemId']   = $this->_item6['itemId'];
        $expected['user']     = $this->_user1;
        $expected['item']     = $this->_item6;
        $expected['tags']     = $this->_tags2;
        $expected['taggedOn'] = date('Y-m-d h:i:s');

        // Assemble names of the tags to attach to this Bookmark
        $tagMapper = Connexions_Model_Mapper::factory('Model_Mapper_Tag');
        $tagNames  = array();
        foreach ($expected['tags'] as $tag)
        {
            array_push($tagNames, $tag['tag']);
        }

        // Create the new Bookmark
        $mapper   = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmark = $mapper->getModel( array(
            'name'        => $expected['name'],
            'description' => $expected['description'],
            'rating'      => $expected['rating'],
            'isFavorite'  => $expected['isFavorite'],
            'isPrivate'   => $expected['isPrivate'],
            'taggedOn'    => $expected['taggedOn'],
        ));

        $bookmark->userId = $expected['user']['userId'];
        $bookmark->itemId = $expected['item']['itemId'];
        $bookmark->tags   = $tagMapper->fetchBy('tag', $tagNames);

        $bookmark = $bookmark->save();

        /*
        Connexions::log("testBookmarkCreate: bookmark[ %s ]",
                        Connexions::varExport($bookmark->toArray()) );
        // */

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


        // Modify 'updateOn' and 'taggedOn' in our expected set for the target
        // row since it's dynamic...
        $expected['updatedOn'] = $bookmark->updatedOn;

        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/bookmarkInsertFullAssertion.xml');
        $et = $es->getTable('userItem');
        $et->setValue(5, 'updatedOn', $expected['updatedOn']);
        $et->setValue(5, 'taggedOn',  $expected['taggedOn']);

        /*
        // user.lastVisit is also dynamic
        $et = $es->getTable('user');
        $et->setValue(0, 'lastVisit', $bookmark->user->lastVisit);
        // */

        $this->assertDataSetsEqual($es, $ds);

        // Clear out identity maps so future tests have a clean slate
        $bookmark->user->invalidate();  // or just ->unsetIdentity();
        $bookmark->item->invalidate();  // or just ->unsetIdentity();
        $bookmark->invalidate();        // or just ->unsetIdentity();
    }

    public function testBookmarkSet()
    {
        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmarks = $mapper->fetch();

        // Retrieve the expected set
        $es = $this->createFlatXmlDataSet(
              dirname(__FILE__) .'/_files/bookmarkSetAssertion.xml');

        $this->assertModelSetEquals( $es->getTable('userItem'), $bookmarks );
    }

    public function testBookmarkSetOrder()
    {
        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmarks = $mapper->fetch(null,
                                    array('updatedOn DESC'));   // order

        // Retrieve the expected set
        $es = $this->createFlatXmlDataSet(
              dirname(__FILE__) .'/_files/bookmarkSetOrderAssertion.xml');

        $this->assertModelSetEquals( $es->getTable('userItem'), $bookmarks );
    }

    public function testBookmarkSetLimitCount()
    {
        $expected = 10;

        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmarks = $mapper->fetch(null,
                                    array('updatedOn DESC'),    // order
                                    $expected,                  // count
                                    5);                         // offset

        $this->assertEquals($expected, $bookmarks->count());
        $this->assertEquals($expected, count($bookmarks));
    }

    public function testBookmarkSetLimitTotalCount()
    {
        $expectedCount = 10;
        $expectedTotal = 20;

        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmarks = $mapper->fetch(null,
                                    array('updatedOn DESC'),    // order
                                    $expectedCount,             // count
                                    5);                         // offset

        $this->assertEquals($expectedTotal, $bookmarks->getTotalCount());
    }

    public function testBookmarkSetLimitOrder()
    {
        $expected = array(
            array('userId'      => 2,
                  'itemId'      => 7,

                  'name'        => "nimbus: Nimbus",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2007-03-24 11:45:44",
            ),
            array('userId'      => 2,
                  'itemId'      => 14,

                  'name'        => "Overview (Java 3D 1.5.0)",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "1",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2007-01-24 20:22:40",
            ),
            array('userId'      => 4,
                  'itemId'      => 16,

                  'name'        => "FullBooks.com - Thousands of Full Text Free Books",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "1",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2006-12-19 02:33:05",
            ),
            array('userId'      => 3,
                  'itemId'      => 9,

                  'name'        => "Home Decorators Collection: Custom framed art and wall decor for your Home Decorating solutions with a money back guarantee",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "2006-11-12 07:39:23",
                  'updatedOn'   => "2006-11-12 07:39:23",
            ),
            array('userId'      => 3,
                  'itemId'      => 4,

                  'name'        => "OAT Framework",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2006-09-10 00:04:32",
            ),
            array('userId'      => 4,
                  'itemId'      => 6,

                  'name'        => "IBM doubles CPU cooling capabilities with simple manufacturing change",
                  'description' => "",
                  'rating'      => "4",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "2006-06-30 18:21:47",
                  'updatedOn'   => "2006-06-30 18:21:47",
            ),
            array('userId'      => 2,
                  'itemId'      => 13,

                  'name'        => "TiddlyWiki Guides - TiddlyWikiGuides",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2006-06-26 15:54:56",
            ),
            array('userId'      => 4,
                  'itemId'      => 12,

                  'name'        => "Textpattern",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2006-05-21 06:32:33",
            ),
            array('userId'      => 3,
                  'itemId'      => 6,

                  'name'        => "IBM doubles CPU cooling capabilities with simple manufacturing change",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2006-05-18 14:31:22",
            ),
            array('userId'      => 4,
                  'itemId'      => 15,

                  'name'        => "Ajaxian » Lily: Graphical data-flow programming environment",
                  'description' => "",
                  'rating'      => "0",
                  'isFavorite'  => "0",
                  'isPrivate'   => "0",
                  'taggedOn'    => "0000-00-00 00:00:00",
                  'updatedOn'   => "2006-05-15 20:35:14",
            ),
        );

        $expectedCount = 10;
        $expectedTotal = 20;

        $mapper    = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $bookmarks = $mapper->fetch(null,
                                    array('updatedOn DESC'),    // order
                                    $expectedCount,             // count
                                    5);                         // offset

        $this->assertEquals($expectedCount, $bookmarks->count());
        $this->assertEquals($expectedCount, count($bookmarks));
        $this->assertEquals($expectedTotal, $bookmarks->getTotalCount());

        $actual = $bookmarks->toArray(self::$toArray_shallow_all);

        /*
        printf("testBookmarkSetLimitOrder: actual[ %s ]", $bookmarks);
        // */

        $this->assertEquals($expected, $actual);

        // Retrieve the expected set
        $ds = $this->createFlatXmlDataSet(
              dirname(__FILE__) .'/_files/bookmarkSetLimitOrderAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('userItem'), $bookmarks );
        // */
    }

    public function testBookmarkSetPaginator()
    {
        $expected = array(
            array(
                'userId'        => "1",
                'itemId'        => "1",

                'name'          => "More than a password manager | Clipperz",
                'description'   => "Testing 1,2 3, 4...",
                'rating'        => "1",
                'isFavorite'    => "0",
                'isPrivate'     => "1",
                'taggedOn'      => "2010-04-05 17:25:19",
                'updatedOn'     => "2010-07-22 10:00:00",
            ),
            array(
                'userId'        => "1",
                'itemId'        => "2",

                'name'          => "OAT Framework",
                'description'   => "",
                'rating'        => "2",
                'isFavorite'    => "1",
                'isPrivate'     => "0",
                'taggedOn'      => "2007-03-30 14:39:52",
                'updatedOn'     => "2007-03-30 14:39:52",
            ),
            array(
                'userId'        => "1",
                'itemId'        => "3",

                'name'          => "OAT: OpenAjax Alliance Compliant Toolkit (Live Links Version)",
                'description'   => "",
                'rating'        => "0",
                'isFavorite'    => "1",
                'isPrivate'     => "1",
                'taggedOn'      => "2007-03-30 14:35:51",
                'updatedOn'     => "2007-03-30 14:35:51",
            ),
            array(
                'userId'        => "1",
                'itemId'        => "4",

                'name'          => "OAT Framework Demo",
                'description'   => "",
                'rating'        => "0",
                'isFavorite'    => "0",
                'isPrivate'     => "0",
                'taggedOn'      => "2007-03-30 14:33:27",
                'updatedOn'     => "2007-03-30 14:33:27",
            ),
            array(
                'userId'        => "1",
                'itemId'        => "5",

                'name'          => "JavaScript Diagram Builder",
                'description'   => "",
                'rating'        => "0",
                'isFavorite'    => "0",
                'isPrivate'     => "0",
                'taggedOn'      => "2007-03-30 13:11:57",
                'updatedOn'     => "2007-03-30 13:11:57",
            ),
            array(
                'userId'        => "2",
                'itemId'        => "6",

                'name'          => "IBM doubles CPU cooling capabilities with simple manufacturing change",
                'description'   => "",
                'rating'        => "3",
                'isFavorite'    => "0",
                'isPrivate'     => "0",
                'taggedOn'      => "2006-04-09 23:59:27",
                'updatedOn'     => "2006-04-09 23:59:27",
            ),
            array(
                'userId'        => "2",
                'itemId'        => "7",

                'name'          => "nimbus: Nimbus",
                'description'   => "",
                'rating'        => "0",
                'isFavorite'    => "0",
                'isPrivate'     => "0",
                'taggedOn'      => "0000-00-00 00:00:00",
                'updatedOn'     => "2007-03-24 11:45:44",
            ),
            array(
                'userId'        => "2",
                'itemId'        => "11",

                'name'          => "The Wii Laptop! - Engadget",
                'description'   => "",
                'rating'        => "3",
                'isFavorite'    => "0",
                'isPrivate'     => "0",
                'taggedOn'      => "0000-00-00 00:00:00",
                'updatedOn'     => "2006-03-30 05:48:26",
            ),
            array(
                'userId'        => "2",
                'itemId'        => "13",

                'name'          => "TiddlyWiki Guides - TiddlyWikiGuides",
                'description'   => "",
                'rating'        => "0",
                'isFavorite'    => "0",
                'isPrivate'     => "0",
                'taggedOn'      => "0000-00-00 00:00:00",
                'updatedOn'     => "2006-06-26 15:54:56",
            ),
            array(
                'userId'        => "2",
                'itemId'        => "14",

                'name'          => "Overview (Java 3D 1.5.0)",
                'description'   => "",
                'rating'        => "0",
                'isFavorite'    => "1",
                'isPrivate'     => "0",
                'taggedOn'      => "0000-00-00 00:00:00",
                'updatedOn'     => "2007-01-24 20:22:40",
            ),
        );
        $mapper = Connexions_Model_Mapper::factory('Model_Mapper_Bookmark');
        $users  = $mapper->fetch();

        // Convert the Connexions_Model_Set to a Zend_Paginator
        $paginator = new Zend_Paginator( $users->getPaginatorAdapter() );

        $this->assertEquals(20, $paginator->getTotalItemCount());
        $this->assertEquals(10, $paginator->getCurrentItemCount());
        $this->assertEquals(2, count($paginator));

        foreach ($paginator as $idex => $item)
        {
            $this->assertEquals( $expected[$idex],
                                 $item->toArray(self::$toArray_shallow_all));
        }
    }
}
