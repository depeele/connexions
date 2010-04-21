<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/models/Bookmark.php';

class BookmarkDbTest extends DbTestCase
{
    protected   $_user1 = array(
            'userId'        => 1,
            'name'          => 'User1',
            'fullName'      => 'Random User 1',
            'email'         => 'User1@home',
            'apiKey'        => null,
            'pictureUrl'    => '/connexions/images/User1.png',
            'profile'       => null,
            'lastVisit'     => '2007-04-12 12:38:02',
            'totalTags'     => 24,
            'totalItems'    => 5,

            'userItemCount' => null,
            'userCount'     => null,
            'itemCount'     => null,
            'tagCount'      => null,
    );
    protected   $_item1 = array(
            'itemId'        => 1,
            'url'           => 'http://www.clipperz.com/',
            'urlHash'       => '383cb614a2cc9247b86cad9a315d02e3',
            'userCount'     => 1,
            'ratingCount'   => 1,
            'ratingSum'     => 1,

            'userItemCount' => null,
            //'userCount'     => 1,
            'itemCount'     => null,
            'tagCount'      => null,
    );
    protected   $_item6 = array(
            'itemId'        => 6,
            'url'           => 'http://arstechnica.com/news.ars/post/20070325-ibm-doubles-cpu-cooling-capabilities-with-simple-manufacturing-change.html',
            'urlHash'       => 'ba7215776973fafa3f5b0bfd263e3ec2',
            'userCount'     => 3,
            'ratingCount'   => 2,
            'ratingSum'     => 7,

            'userItemCount' => null,
            //'userCount'     => 1,
            'itemCount'     => null,
            'tagCount'      => null,
    );
    protected   $_tags1 = array(
            array('tagId' =>        1,
                  'tag'   => 'security',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        2,
                  'tag'   => 'passwords',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        4,
                  'tag'   => 'privacy',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        5,
                  'tag'   => 'identity',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        6,
                  'tag'   => 'web2.0',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        7,
                  'tag'   => 'online',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        8,
                  'tag'   => 'password',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>        9,
                  'tag'   => 'storage',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>       10,
                  'tag'   => 'ajax', 'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>       11,
                  'tag'   => 'tools','userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>       71,
                  'tag'   => 'test', 'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
            array('tagId' =>       72,
                  'tag'   => 'cryptography',
                                     'userItemCount' => 1, 'userCount' => 1,
                                     'itemCount'     => 1, 'tagCount'  => 1),
    );
    protected   $_bookmark1 = array(
            'user'          => null,    // $this->_user1,
            'item'          => null,    // $this->_item1,
            'tags'          => null,    // $this->_tags1,

            'name'          => 'More than a password manager | Clipperz',
            'description'   => 'Testing 1,2 3, 4...',
            'rating'        => 1,
            'isFavorite'    => 0,
            'isPrivate'     => 1,
            'taggedOn'      => '2010-04-05 17:25:19',
            'updatedOn'     => '2010-02-22 10:00:00',

            'userItemCount' => null,
            'userCount'     => null,
            'itemCount'     => null,
            'tagCount'      => null,
    );

    protected function getDataSet()
    {
        Connexions::log("BookmarkDbTest::getDataSet()");

        return $this->createFlatXmlDataSet(
                        dirname(__FILE__) .'/_files/5users.xml');
    }

    public function testBookmarkRetrieveById1()
    {
        $expected  = $this->_bookmark1;
        $expected['user'] = $this->_user1;
        $expected['item'] = $this->_item1;
        $expected['tags'] = $this->_tags1;


        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $expected['user']['userId'],
                                          $expected['item']['itemId']) );
        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                                Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkIdentityMap()
    {
        $mapper = new Model_Mapper_Bookmark( );
        $id     = array( $this->_user1['userId'],
                         $this->_item1['itemId'] );

        $bookmark  = $mapper->find( $id );
        $bookmark2 = $mapper->find( $id );

        $this->assertSame( $bookmark, $bookmark2 );
    }

    public function testBookmarkInvalidate()
    {
        $expected  = $this->_bookmark1;
        $expected['user'] = $this->_user1['userId'];
        $expected['item'] = $this->_item1['itemId'];


        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $expected['user'],
                                          $expected['item']) );

        // Force retrievals
        $user = $bookmark->user;
        $this->assertEquals($this->_user1,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        $item = $bookmark->item;
        $this->assertEquals($this->_item1,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));

        $tags = $bookmark->tags;
        $tags2 = array();
        foreach ($tags as $idex => $tag)
        {
            array_push($tags2,
                       $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                      Connexions_Model::FIELDS_ALL ));
        }
        $this->assertEquals($this->_tags1, $tags2);

        // Invalidate our cache
        $bookmark->invalidateCache();

        $this->assertEquals($expected,
                            $bookmark->toArray(Connexions_Model::DEPTH_SHALLOW,
                                               Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkUser()
    {
        $expected = $this->_user1;

        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $this->_user1['userId'],
                                          $this->_item1['itemId'] ) );

        $user = $bookmark->user;
        $this->assertEquals($expected,
                            $user->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkItem()
    {
        $expected = $this->_item1;

        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $this->_user1['userId'],
                                          $this->_item1['itemId'] ) );

        $item = $bookmark->item;
        $this->assertEquals($expected,
                            $item->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testBookmarkTags()
    {
        $expected = $this->_tags1;

        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $this->_user1['userId'],
                                          $this->_item1['itemId'] ) );

        $tags = $bookmark->tags;

        $this->assertEquals(count($expected), count($tags));

        //printf ("\n %d tags:\n", count($tags));
        foreach ($tags as $idex => $tag)
        {
            /*
            printf (" #%2d: [ %4d, %-20s: ui:%3d, u:%3d, i:%3d, t:%3d ]\n",
                    $idex,
                    $tag->tagId, $tag->tag,
                    $tag->userItemCount, $tag->userCount,
                    $tag->itemCount, $tag->tagCount);
            // */

            $this->assertEquals($expected[$idex],
                                $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                               Connexions_Model::FIELDS_ALL ));
        }
    }

    public function testBookmarkUpdate()
    {
        $expected                = $this->_bookmark1;
        $expected['user']        = $this->_user1['userId'];
        $expected['item']        = $this->_item1['itemId'];
        $expected['name']        = 'Clipperz';
        $expected['description'] = 'More than a password manager';
        $expected['rating']      = 2;
        $expected['isFavorite']  = 1;
        $expected['isPrivate']   = 0;
        $expected['updatedOn']   = '2010-04-15 08:15:21';

        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $expected['user'],
                                          $expected['item']) );

        $bookmark->name        = $expected['name'];
        $bookmark->description = $expected['description'];
        $bookmark->rating      = $expected['rating'];
        $bookmark->isFavorite  = $expected['isFavorite'];
        $bookmark->isPrivate   = $expected['isPrivate'];
        $bookmark->updatedOn   = $expected['updatedOn'];

        $this->assertTrue(  $bookmark->isBacked() );
        $this->assertTrue(  $bookmark->isValid() );
        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_SHALLOW,
                                                Connexions_Model::FIELDS_ALL ));

        $bookmark = $bookmark->save();

        // The 'updatedOn' value is dynamic (Model_Mapper_Bookmark)
        $expected['updatedOn'] = date('Y-m-d h:i:00');

        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_SHALLOW,
                                                Connexions_Model::FIELDS_ALL ));

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

        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId FROM userTagItem'
                                     .  ' ORDER BY userId,itemId,tagId ASC');


        /*********
         * Modify 'updateOn' in our expected set for the target row since
         * it's dynamic...
         */
        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/bookmarkUpdateAssertion.xml');
        $et = $es->getTable('userItem');
        $et->setValue(0, 'updatedOn', $expected['updatedOn']);

        $this->assertDataSetsEqual($es, $ds);
    }

    public function testBookmarkFullyDeletedFromDatabase()
    {
        $mapper   = new Model_Mapper_Bookmark( );
        $bookmark = $mapper->find( array( $this->_user1['userId'],
                                          $this->_item1['itemId']) );
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

        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId FROM userTagItem'
                                     .  ' ORDER BY userId,itemId,tagId ASC');

        $this->assertDataSetsEqual(
            $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/bookmarkDeleteFullAssertion.xml'),
            $ds);
    }

    protected   $_newBookmark = array(
            'user'          => null,
            'item'          => null,
            'tags'          => null,

            'name'          => 'New Bookmark',
            'description'   => 'This is a new bookmark',
            'rating'        => 3,
            'isFavorite'    => 1,
            'isPrivate'     => 0,
            'taggedOn'      => null,
            'updatedOn'     => null,

            'userItemCount' => null,
            'userCount'     => null,
            'itemCount'     => null,
            'tagCount'      => null,
    );
    protected   $_tags2 = array(
            array('tagId'           => 1,
                  'tag'             => 'security',
                  'userItemCount'   => null,
                  'userCount'       => null,
                  'itemCount'       => null,
                  'tagCount'        => null),
            array('tagId'           => 31,
                  'tag'             => 'cooling',
                  'userItemCount'   => null,
                  'userCount'       => null,
                  'itemCount'       => null,
                  'tagCount'        => null),
    );

    public function testBookmarkCreateNoTagsShouldFail()
    {
        $expected             = $this->_newBookmark;
        $expected['user']     = $this->_user1['userId'];
        $expected['item']     = $this->_item6['itemId'];
        $expected['taggedOn'] = date('Y-m-d h:i:s');

        $data       = array(
            'user'        => $expected['user'],
            'item'        => $expected['item'],
            'name'        => $expected['name'],
            'description' => $expected['description'],
            'rating'      => $expected['rating'],
            'isFavorite'  => $expected['isFavorite'],
            'isPrivate'   => $expected['isPrivate'],
            'taggedOn'    => $expected['taggedOn']
        );

        $bookmark = new Model_Bookmark( $data );

        /*
        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_SHALLOW,
                                                Connexions_Model::FIELDS_ALL ));
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

    public function testBookmarkCreate()
    {
        $expected             = $this->_newBookmark;
        $expected['user']     = $this->_user1;  //['userId'];
        $expected['item']     = $this->_item6;  //['itemId'];
        $expected['tags']     = $this->_tags2;
        $expected['taggedOn'] = date('Y-m-d h:i:s');

        // Clear out identity maps
        $userMapper = Connexions_Model_Mapper::factory('Model_Mapper_User');
        $userMapper->_unsetIdentity($expected['user']['userId']);

        $itemMapper = Connexions_Model_Mapper::factory('Model_Mapper_Item');
        $itemMapper->_unsetIdentity($expected['item']['itemId']);

        // Retrieve the set of tags to attach to this Bookmark
        $tagMapper = new Model_Mapper_Tag( );
        $tagNames  = array();
        foreach ($expected['tags'] as $tag)
        {
            array_push($tagNames, $tag['tag']);
        }

        // Create the new Bookmark
        $bookmark = new Model_Bookmark( array(
            'user'        => $expected['user']['userId'],
            'item'        => $expected['item']['itemId'],
            'tags'        => $tagMapper->fetchBy('tag', $tagNames),

            'name'        => $expected['name'],
            'description' => $expected['description'],
            'rating'      => $expected['rating'],
            'isFavorite'  => $expected['isFavorite'],
            'isPrivate'   => $expected['isPrivate'],
            'taggedOn'    => $expected['taggedOn'],
        ));

        Connexions::log("testBookmarkCreate(): 1:user[ %s ]",
                        Connexions::varExport($bookmark->user->toArray()));

        $bookmark = $bookmark->save();

        Connexions::log("testBookmarkCreate(): 2:user[ %s ]",
                        Connexions::varExport($bookmark->user->toArray()));

        /* Several things in the expected set are dynamic.  Update them now:
         *      - The updatedOn timestamp is dynamically updated on save
         *          Bookmark.updatedOn          should be within 1 minute;
         *
         *      - We've added a new bookmark with a rating and used a tag not
         *        yet used by this user
         *          Bookmark.user.totalItems,
         *          Bookmark.user.totalTags     both increase by 1;
         *          Bookmark.item.userCount,
         *          Bookmark.item.ratingCount   both increase by 1;
         *          Bookmark.item.ratingSum     increases by Bookmark.rating;
         *
         *      - The counts on each tag in the tag set are filled on retrieve.
         *        Since there is only 1 user, item, tag involved in the
         *        retrieval, counts should be 1
         *          Bookmark.tags[].userItemCount,
         *                         .userCount,
         *                         .itemCount,
         *                         .tagCount    should all be 1
         */
        $expected['updatedOn'] = date('Y-m-d h:i:00');
        $expected['user']['totalItems']++;
        $expected['user']['totalTags']++;
        $expected['item']['userCount']++;
        $expected['item']['ratingCount']++;
        $expected['item']['ratingSum'] += $bookmark->rating;
        foreach ($expected['tags'] as &$tag)
        {
            $tag['userItemCount'] = 1;
            $tag['userCount']     = 1;
            $tag['itemCount']     = 1;
            $tag['tagCount']      = 1;
        }

        $this->assertEquals($expected,
                            $bookmark->toArray( Connexions_Model::DEPTH_DEEP,
                                                Connexions_Model::FIELDS_ALL ));

        /* Check the database consistency
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

        $ds->addTable('userTagItem', 'SELECT userId,itemId,tagId FROM userTagItem'
                                     .  ' ORDER BY userId,itemId,tagId ASC');


        // Modify 'updateOn' in our expected set for the target row since
        // it's dynamic...
        $es = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/bookmarkUpdateAssertion.xml');
        $et = $es->getTable('userItem');
        $et->setValue(0, 'updatedOn', $expected['updatedOn']);

        $this->assertDataSetsEqual($es, $ds);
        // */
    }
}
