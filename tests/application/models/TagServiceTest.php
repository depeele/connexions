<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Tag.php';

/**
 *  @group Services
 */
class TagServiceTest extends DbTestCase
{
    private     $_tag0  = array(
            'tagId'         => 0,
            'tag'           => 'Tag0',

            'userItemCount' => 0,
            'itemCount'     => 0,
            'userCount'     => 0,
    );
    private     $_tag1  = array(
            'tagId'         => 1,
            'tag'           => 'security',

            'userItemCount' => 0,
            'itemCount'     => 0,
            'userCount'     => 0,
    );
    private     $_tag2  = array(
            'tagId'         => 2,
            'tag'           => 'passwords',

            'userItemCount' => 0,
            'itemCount'     => 0,
            'userCount'     => 0,
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

    public function testTagServiceFactory()
    {
        $service1 = Connexions_Service::factory('Model_Tag');
        $this->assertTrue( $service1 instanceof Connexions_Service );
        $this->assertTrue( $service1 instanceof Service_Tag );

        $service2 = Connexions_Service::factory('Service_Tag');
        $this->assertTrue( $service2 instanceof Connexions_Service );
        $this->assertTrue( $service2 instanceof Service_Tag );
        $this->assertSame( $service1, $service2 );
    }

    public function testTagServiceGet()
    {
        $expected = $this->_tag0;
        $service  = Connexions_Service::factory('Model_Tag');

        $tag     = $service->get( $data = array(
            'tag'         => $expected['tag'],
        ));

        $this->assertTrue( $tag instanceof Model_Tag );

        $this->assertFalse(  $tag->isBacked() );
        $this->assertTrue(   $tag->isValid() );

        // The tag name should be lower-cased on insert
        $expected['tag'] = strtolower($expected['tag']);

        $this->assertEquals($tag->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagServiceCreateExistingReturnsBackedInstance()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $tag     = $service->get( $data = array(
            'tag'         => $expected['tag'],
        ));

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testTagServiceFind1()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $id       = array( 'tagId'  => $expected['tagId']);

        $tag      = $service->find( $id );

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagServiceFind2()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $id       = array( 'tag'    => $expected['tag']);

        $tag      = $service->find( $id );

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagServiceFind3()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $id       = $expected['tagId'];

        $tag      = $service->find( $id );

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    public function testTagServiceFind4()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $id       = $expected['tag'];

        $tag      = $service->find( $id );

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Set retrieval tests
     *
     */

    public function testTagServiceFetchSet()
    {
        $service  = Connexions_Service::factory('Model_Tag');
        $tags    = $service->fetch();

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/tagSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('tag'), $tags );
    }

    public function testTagServiceFetchPaginated()
    {
        $service  = Connexions_Service::factory('Model_Tag');
        $tags    = $service->fetchPaginated();

        /*
        printf ("%d tags of %d, %d pages with %d per page, current page %d\n",
                $tags->getTotalItemCount(),
                $tags->getCurrentItemCount(),
                $tags->count(),
                $tags->getItemCountPerPage(),
                $tags->getCurrentPageNumber());
        // */

        $this->assertEquals(72, $tags->getTotalItemCount());
        $this->assertEquals(10, $tags->getCurrentItemCount());
        $this->assertEquals(8,  count($tags));

        /*
        foreach ($tags as $idex => $item)
        {
            printf ("Row %2d: [ %s ]\n",
                    $idex,
                    Connexions::varExport( (is_object($item)
                                                ? $item->toArray()
                                                : $item)));
        }
        // */

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/tagPaginatedSetAssertion.xml');

        $this->assertPaginatedSetEquals( $ds->getTable('tag'), $tags );
    }

    public function testTagServicecsList2set()
    {
        //                  vv SHOULD BE ordered by 'tag ASC'
        //                  except for the tags that didn't already exist
        //                  these will be at the end of the list with id 0
        $expected   = array(10, 1, 49, 0, 0);// vv unknown tags
        $tags       = "security, ajax,  wii,    tag12345, tag91828";
        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->csList2set( $tags );
        $this->assertNotEquals(null, $tags);

        $ids        = $tags->getIds();

        /*
        printf ("Tags [ %s ]: [ %s ]\n",
                $tags, print_r($tags->toArray(), true) );

        printf ("Tag Ids: [ %s ]\n", implode(', ', $ids));
        // */

        $this->assertEquals($expected, $ids);
    }

    public function testTagServiceSetString()
    {
        //             vv SHOULD BE ordered by 'tag ASC'
        //                  except for the tags that didn't already exist
        //                  these will be at the end of the list.
        $expected   = "ajax,security,wii,tag12345,tag91828";
        $tags       = "security, ajax,  wii,   tag12345, tag91828";
        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->csList2set( $tags );
        $this->assertNotEquals(null, $tags);

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }

    public function testTagServiceFetchByUsers()
    {
        $expected   = 'for:user1,'          . 'ajax,'
                    . 'javascript,'         . 'web2.0,'
                    . 'framework,'          . 'oat,'
                    . 'widgets,'            . 'chip,'
                    . 'cooling,'            . 'cpu,'
                    . 'demo,'               . 'hardware,'
                    . 'ibm,'                . 'processor,'
                    . 'technology,'         . 'tiddlywiki,'
                    . 'library,'            . 'api,'
                    . 'art,'                . 'bed,'
                    . 'chart,'              . 'cryptography,'
                    . 'decoration,'         . 'desktop,'
                    . 'diagram,'            . 'documentation,'
                    . 'friend,'             . 'furniture,'
                    . 'generator,'          . 'graph,'
                    . 'graphics,'           . 'guide,'
                    . 'hacks,'              . 'howto,'
                    . 'identity,'           . 'java,'
                    . 'java3d,'             . 'manual,'
                    . 'mattress,'           . 'mediawiki,'
                    . 'nimbus,'             . 'online,'
                    . 'password,'           . 'passwords,'
                    . 'php,'                . 'portable,'
                    . 'privacy,'            . 'reference,'
                    . 'security,'           . 'storage,'
                    . 'swing,'              . 'test,'
                    . 'tiddlywikiplugin,'   . 'tools,'
                    . 'wii,'                .'wiki';

        $users      = array(1, 2, 3);
        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->fetchByUsers( $users /*, 'userCount DESC'*/ );
        $this->assertNotEquals(null, $tags);

        //printf ("Tags: [ %s ]\n", print_r($tags->toArray(), true));

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }

    public function testTagServiceFetchByItems()
    {
        //            vv ordered by 'itemCount DESC'
        $expected   = 'chip,'           . 'cooling,'
                    . 'cpu,'            . 'hardware,'
                    . 'ibm,'            . 'processor,'
                    . 'technology,'     . 'ajax,'
                    . 'blog,'           . 'cms,'
                    . 'cryptography,'   . 'for:user1,'
                    . 'identity,'       . 'mysql,'
                    . 'online,'         . 'password,'
                    . 'passwords,'      . 'php,'
                    . 'privacy,'        . 'security,'
                    . 'software,'       . 'storage,'
                    . 'test,'           . 'tools,'
                    . 'web2.0';

        $items      = array(1, 6, 12);
        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->fetchByItems( $items /*, 'itemCount DESC'*/ );
        $this->assertNotEquals(null, $tags);

        //printf ("Tags: [ %s ]\n", print_r($tags->toArray(), true));

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }

    public function testTagServiceFetchByBookmarks()
    {
        //            vv ordered by 'userItemCount DESC'
        $expected   = ''
                    . 'ajax,'
                    . 'blog,'
                    . 'chip,'
                    . 'cms,'
                    . 'cooling,'
                    . 'cpu,'
                    . 'cryptography,'
                    . 'hardware,'
                    . 'ibm,'
                    . 'identity,'
                    . 'mysql,'
                    . 'online,'
                    . 'password,'
                    . 'passwords,'
                    . 'php,'
                    . 'privacy,'
                    . 'processor,'
                    . 'security,'
                    . 'software,'
                    . 'storage,'
                    . 'technology,'
                    . 'test,'
                    . 'tools,'
                    . 'web2.0';

        $bookmarks  = array( array(1,1), array(3,6), array(4,12));
        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->fetchByBookmarks( $bookmarks,
                                                  'tag ASC' );
        $this->assertNotEquals(null, $tags);

        //printf ("Tags: [ %s ]\n", print_r($tags->toArray(), true));

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }

    public function testTagServiceFetchByBookmarksAll()
    {
        //            vv ordered by 'userItemCount DESC'
        $expected   = 'for:user1,'          . 'javascript,'
                    . 'ajax,'               . 'web2.0,'
                    . 'framework,'          . 'oat,'
                    . 'chip,'               . 'cooling,'
                    . 'cpu,'                . 'hardware,'
                    . 'ibm,'                . 'processor,'
                    . 'technology,'         . 'library,'
                    . 'widgets,'            . 'demo,'
                    . 'desktop,'            . 'java,'
                    . 'nimbus,'             . 'php,'
                    . 'reference,'          . 'swing,'
                    . 'tiddlywiki,'         . 'api,'
                    . 'art,'                . 'bed,'
                    . 'blog,'               . 'books,'
                    . 'chart,'              . 'cms,'
                    . 'collection,'         . 'cryptography,'
                    . 'decoration,'         . 'diagram,'
                    . 'documentation,'      . 'ebooks,'
                    . 'free,'               . 'friend,'
                    . 'furniture,'          . 'generator,'
                    . 'graph,'              . 'graphics,'
                    . 'guide,'              . 'hacks,'
                    . 'howto,'              . 'identity,'
                    . 'java3d,'             . 'lily,'
                    . 'literature,'         . 'lookandfeel,'
                    . 'manual,'             . 'mattress,'
                    . 'mediawiki,'          . 'mysql,'
                    . 'online,'             . 'password,'
                    . 'passwords,'          . 'pipes,'
                    . 'portable,'           . 'privacy,'
                    . 'programming,'        . 'reading,'
                    . 'security,'           . 'software,'
                    . 'storage,'            . 'synth,'
                    . 'test,'               . 'tiddlywikiplugin,'
                    . 'tools,'              . 'visual,'
                    . 'wii,'                . 'wiki';

        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->fetchByBookmarks( );
        $this->assertNotEquals(null, $tags);

        //printf ("Tags: [ %s ]\n", print_r($tags->toArray(), true));

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }

    public function testTagServiceNormalize1()
    {
        $tagStr   = 'Tag1,Tag Too Long123456789012345678901234, Tag  3  ';
        $expected = 'tag1,tag too long123456789012345678,tag 3';

        $service  = Connexions_Service::factory('Model_Tag');
        $tags     = $service->csList2set( $tagStr );
        $this->assertNotEquals(null, $tags);

        $this->assertEquals($expected, $tags->__toString());
    }

    public function testTagServiceNormalize2()
    {
        $tagStr   = "Tag1,Tag\nToo\r&nbsp;Long123456789012345678 &nbsp; with newlines and entities, Tag  3  ";
        $expected = 'tag1,tag too long123456789012345678,tag 3';

        $service  = Connexions_Service::factory('Model_Tag');
        $tags     = $service->csList2set( $tagStr );
        $this->assertNotEquals(null, $tags);

        $this->assertEquals($expected, $tags->__toString());
    }

    public function testTagServiceNormalize3()
    {
        $tagStr   = '<b>Tag1</b>,<b>Tag2,Tag3</b>';
        $expected = 'tag1,tag2,tag3';

        $service  = Connexions_Service::factory('Model_Tag');
        $tags     = $service->csList2set( $tagStr );
        $this->assertNotEquals(null, $tags);

        $this->assertEquals($expected, $tags->__toString());
    }

    public function testTagServiceNormalize4()
    {
        $tagStr   = '[Tag1],"Tag2",\\Tag3\,Tag(4), tag\'5\', tag&quot;6';
        $expected = '[tag1],tag2,tag3,tag(4),tag5,tag6';

        $service  = Connexions_Service::factory('Model_Tag');
        $tags     = $service->csList2set( $tagStr );
        $this->assertNotEquals(null, $tags);

        $this->assertEquals($expected, $tags->__toString());
    }

    public function testTagServiceNormalize5()
    {
        $tagStr   = 'Tag1&nbsp;&shy;';
        $expected = 'tag1';

        $service  = Connexions_Service::factory('Model_Tag');
        $tags     = $service->csList2set( $tagStr );
        $this->assertNotEquals(null, $tags);

        $this->assertEquals($expected, $tags->__toString());
    }
}
