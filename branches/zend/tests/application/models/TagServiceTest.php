<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Tag.php';

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

    public function testTagServiceCreate()
    {
        $expected = $this->_tag0;
        $service  = Connexions_Service::factory('Model_Tag');

        $tag     = $service->create( $data = array(
            'tag'         => $expected['tag'],
        ));

        $this->assertTrue( $tag instanceof Model_Tag );

        $this->assertFalse(  $tag->isBacked() );
        $this->assertTrue(   $tag->isValid() );

        // The tag name should be lower-cased on insert
        $expected['tag'] = strtolower($expected['tag']);

        $this->assertEquals($tag->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                           Connexions_Model::FIELDS_ALL ));
    }

    public function testTagServiceCreateExistingReturnsBackedInstance()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $tag     = $service->create( $data = array(
            'tag'         => $expected['tag'],
        ));

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testTagServiceFind1()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $tag     = $service->find( array(
                                        'tagId'=> $expected['tagId'],
                    ));

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagServiceFind2()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $tag     = $service->find( array(
                                        'tag' => $expected['tag'],
                    ));

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagServiceFind3()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $tag     = $service->find( $expected['tagId'] );

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
    }

    public function testTagServiceFind4()
    {
        $expected = $this->_tag1;
        $service  = Connexions_Service::factory('Model_Tag');
        $tag     = $service->find( $expected['tag'] );

        $this->assertTrue(  $tag instanceof Model_Tag );
        $this->assertTrue(  $tag->isBacked() );
        $this->assertTrue(  $tag->isValid() );

        $this->assertEquals($expected,
                            $tag->toArray( Connexions_Model::DEPTH_SHALLOW,
                                            Connexions_Model::FIELDS_ALL ));
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
        $expected   = array(10, 1, 49);      // vv unknown tags
        $tags       = "security, ajax,  wii,    tag12345, tag91828";
        $service    = Connexions_Service::factory('Model_Tag');
        $tags      = $service->csList2set( $tags );
        $this->assertNotEquals(null, $tags);

        $ids        = $tags->idArray();

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
        $expected   = "ajax,security,wii";  // vv unknown tags
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
        //            vv ordered by 'userCount DESC'
        $expected   = 'processor,'          . 'framework,'
                    . 'cooling,'            . 'tiddlywiki,'
                    . 'chip,'               . 'hardware,'
                    . 'for:dep,'            . 'oat,'
                    . 'technology,'         . 'ajax,'
                    . 'widgets,'            . 'ibm,'
                    . 'web2.0,'             . 'demo,'
                    . 'cpu,'                . 'javascript,'
                    . 'security,'           . 'graph,'
                    . 'howto,'              . 'wii,'
                    . 'password,'           . 'bed,'
                    . 'cryptography,'       . 'passwords,'
                    . 'chart,'              . 'reference,'
                    . 'library,'            . 'hacks,'
                    . 'storage,'            . 'tiddlywikiplugin,'
                    . 'mattress,'           . 'privacy,'
                    . 'diagram,'            . 'art,'
                    . 'guide,'              . 'portable,'
                    . 'mediawiki,'          . 'java,'
                    . 'identity,'           . 'graphics,'
                    . 'decoration,'         . 'manual,'
                    . 'wiki,'               . 'tools,'
                    . 'for:busbeytheelder,' . 'swing,'
                    . 'generator,'          . 'desktop,'
                    . 'documentation,'      . 'java3d,'
                    . 'api,'                . 'nimbus,'
                    . 'online,'             . 'php,'
                    . 'furniture,test';



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
        $expected   = 'security,'               . 'cpu,'
                    . 'software,'               . 'password,'
                    . 'test,'               . 'passwords,'
                    . 'processor,'              . 'cooling,'
                    . 'storage,'                . 'cryptography,'
                    . 'privacy,'                . 'chip,'
                    . 'hardware,'               . 'ajax,'
                    . 'for:dep,'                . 'identity,'
                    . 'cms,'                . 'technology,'
                    . 'tools,'              . 'web2.0,'
                    . 'blog,'               . 'ibm,'
                    . 'php,'                . 'online,'
                    . 'mysql';

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
        $expected   = 'cpu,'            . 'processor,'
                    . 'cooling,'        . 'chip,'
                    . 'hardware,'       . 'technology,'
                    . 'ibm,'            . 'security,'
                    . 'software,'       . 'password,'
                    . 'test,'           . 'passwords,'
                    . 'storage,'        . 'cryptography,'
                    . 'privacy,'        . 'ajax,'
                    . 'identity,'       . 'cms,'
                    . 'tools,'          . 'web2.0,'
                    . 'blog,'           . 'php,'
                    . 'online,'         . 'mysql';

        $bookmarks  = array( array(1,1), array(3,6), array(4,12));
        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->fetchByBookmarks( $bookmarks /*,
                                                  'userItemCount DESC'*/ );
        $this->assertNotEquals(null, $tags);

        //printf ("Tags: [ %s ]\n", print_r($tags->toArray(), true));

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }

    public function testTagServiceFetchByBookmarksAll()
    {
        //            vv ordered by 'userItemCount DESC'
        $expected   = 'for:dep,'            . 'javascript,'
                    . 'ajax,'               . 'web2.0,'
                    . 'framework,'          . 'oat,'
                    . 'ibm,'                . 'cpu,'
                    . 'library,'            . 'processor,'
                    . 'cooling,'            . 'chip,'
                    . 'widgets,'            . 'hardware,'
                    . 'technology,'         . 'swing,'
                    . 'desktop,'            . 'nimbus,'
                    . 'tiddlywiki,'         . 'reference,'
                    . 'demo,'               . 'java,'
                    . 'php,'                . 'security,'
                    . 'graph,'              . 'hacks,'
                    . 'collection,'         . 'for:busbeytheelder,'
                    . 'documentation,'      . 'password,'
                    . 'programming,'        . 'java3d,'
                    . 'cryptography,'       . 'passwords,'
                    . 'chart,'              . 'portable,'
                    . 'books,'              . 'api,'
                    . 'lookandfeel,'        . 'pipes,'
                    . 'storage,'            . 'software,'
                    . 'furniture,'          . 'howto,'
                    . 'privacy,'            . 'diagram,'
                    . 'cms,'                . 'ebooks,'
                    . 'lily,'               . 'bed,'
                    . 'identity,'           . 'graphics,'
                    . 'blog,'               . 'free,'
                    . 'synth,'              . 'visual,'
                    . 'tools,'              . 'tiddlywikiplugin,'
                    . 'mattress,'           . 'guide,'
                    . 'generator,'          . 'art,'
                    . 'mysql,'              . 'literature,'
                    . 'wii,'                . 'reading,'
                    . 'mediawiki,'          . 'manual,'
                    . 'online,'             . 'decoration,'
                    . 'wiki,'               . 'test';

        $service    = Connexions_Service::factory('Model_Tag');
        $tags       = $service->fetchByBookmarks( );
        $this->assertNotEquals(null, $tags);

        //printf ("Tags: [ %s ]\n", print_r($tags->toArray(), true));

        $tags       = $tags->__toString();

        //printf ("Tags: [ %s ]\n", $tags);

        $this->assertEquals($expected, $tags);
    }
}
