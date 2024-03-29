<?php
require_once TESTS_PATH .'/application/DbTestCase.php';
require_once APPLICATION_PATH .'/services/Item.php';

/**
 *  @group Services
 */
class ItemServiceTest extends DbTestCase
{
    private     $_item0 = array(
            'itemId'        => 0,
            'url'           => 'http://www.google.com/',
            'urlHash'       => 'ff90821feeb2b02a33a6f9fc8e5f3fcd',

            'userCount'     => 0,
            'ratingCount'   => 0,
            'ratingSum'     => 0,

            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    private     $_item1 = array(
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
    protected   $_item3 = array(
            'itemId'        => 3,
            'url'           => 'http://demo.openlinksw.com/weblog/demo/?id=1',
            'urlHash'       => '052973b1ac311978abdc0413daa1d5db',

            'userCount'     => 1,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
    );
    protected   $_item4 = array(
            'itemId'        => 4,
            'url'           => 'http://demo.openlinksw.com/DAV/JS/demo/index.html',
            'urlHash'       => '52cda3e66df5938103c48725357c59ab',

            'userCount'     => 2,
            'ratingCount'   => 0,
            'ratingSum'     => 0,
            'userItemCount' => 0,
            'itemCount'     => 0,
            'tagCount'      => 0,
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

    public function testItemServiceFactory()
    {
        $service1 = Connexions_Service::factory('Model_Item');
        $this->assertTrue( $service1 instanceof Connexions_Service );
        $this->assertTrue( $service1 instanceof Service_Item );

        $service2 = Connexions_Service::factory('Service_Item');
        $this->assertTrue( $service2 instanceof Connexions_Service );
        $this->assertTrue( $service2 instanceof Service_Item );
        $this->assertSame( $service1, $service2 );
    }

    public function testItemServiceCreateNew()
    {
        $expected = $this->_item0;
        $service  = Connexions_Service::factory('Model_Item');

        $item    = $service->get( $data = array(
            'url'         => $expected['url'],
        ));

        $this->assertTrue( $item instanceof Model_Item );

        $this->assertFalse(  $item->isBacked() );
        $this->assertTrue(   $item->isValid() );

        $this->assertEquals($item->getValidationMessages(), array() );
        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemServiceCreateExistingReturnsBackedInstance()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $item     = $service->get( $data = array(
            'url'         => $expected['url'],
        ));

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Single Instance retrieval tests
     *
     */
    public function testItemServiceFind1()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $id       = array( 'itemId' => $expected['itemId']);

        $item     = $service->find( $id );

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemServiceFind2()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $id       = array( 'url'    => $expected['url']);

        $item     = $service->find( $id );

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemServiceFind3()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $id       = array( 'urlHash' => $expected['urlHash']);

        $item     = $service->find( $id );

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemServiceFind4()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $id       = $expected['itemId'];

        $item     = $service->find( $id );

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemServiceFind5()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $id       = $expected['url'];

        $item     = $service->find( $id );

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    public function testItemServiceFind6()
    {
        $expected = $this->_item1;
        $service  = Connexions_Service::factory('Model_Item');
        $id       = $expected['urlHash'];

        $item     = $service->find( $id );

        $this->assertTrue(  $item instanceof Model_Item );
        $this->assertTrue(  $item->isBacked() );
        $this->assertTrue(  $item->isValid() );

        $this->assertEquals($expected,
                            $item->toArray(self::$toArray_shallow_all));
    }

    /*************************************************************************
     * Set retrieval tests
     *
     */

    public function testItemServiceFetchSet()
    {
        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetch();

        // Fetch the expected set
        $ds = $this->createFlatXmlDataSet(
                  dirname(__FILE__) .'/_files/itemSetAssertion.xml');

        $this->assertModelSetEquals( $ds->getTable('item'), $items );
    }

    public function testItemServiceFetchPaginated()
    {
        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetchPaginated();

        /*
        printf ("%d items of %d, %d pages with %d per page, current page %d\n",
                $items->getTotalItemCount(),
                $items->getCurrentItemCount(),
                $items->count(),
                $items->getItemCountPerPage(),
                $items->getCurrentPageNumber());
        // */

        $this->assertEquals(16, $items->getTotalItemCount());
        $this->assertEquals(10, $items->getCurrentItemCount());
        $this->assertEquals(2,  count($items));

        /*
        foreach ($items as $idex => $item)
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
                  dirname(__FILE__) .'/_files/itemPaginatedSetAssertion.xml');

        $this->assertPaginatedSetEquals( $ds->getTable('item'), $items );
    }

    public function testItemServicecsList2set1()
    {
        $expected   = array(1, 6, 11, 15);
        $hashes     = '383cb614a2cc9247b86cad9a315d02e3, '
                    . 'ba7215776973fafa3f5b0bfd263e3ec2, '
                    . 'f95552d896f68ce2c7dca0624ce7e29f, '
                    . 'f6cbe8f4ff12275e776a401cf2679469';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->csList2set( $hashes );
        $this->assertNotEquals(null, $items);

        $ids        = $items->getIds();

        /*
        printf ("Items [ %s ]: [ %s ]\n",
                $items, print_r($items->toArray(), true) );

        printf ("Item Ids: [ %s ]\n", implode(', ', $ids));
        // */

        $this->assertEquals($expected, $ids);
    }

    public function testItemServicecsList2set2()
    {
        $expected   = '383cb614a2cc9247b86cad9a315d02e3,'
                    . 'ba7215776973fafa3f5b0bfd263e3ec2,'
                    . 'f95552d896f68ce2c7dca0624ce7e29f,'
                    . 'f6cbe8f4ff12275e776a401cf2679469';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->csList2set( $expected );
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsers1()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . 'ba7215776973fafa3f5b0bfd263e3ec2,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . '0ba1beb65991ba4d06fac047bf72df49,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . 'b79d82b1c3c6899f8f495b33bc93e687,'
                    . 'd0fa31da9e7c76a00320cf103609dcc5,'
                    . '7f07e1cadb025052d6988fc87d7a351a,'
                    . 'd9b473057c0c7486538a70e7b010f853,'
                    . 'f95552d896f68ce2c7dca0624ce7e29f,'
                    . '39735b7182723ad149214de14fc478d8';

        $users      = array(1, 2, 3);
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsers( $users,
                                              false );  // !exact
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsers2()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . 'ba7215776973fafa3f5b0bfd263e3ec2,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . '0ba1beb65991ba4d06fac047bf72df49,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . 'b79d82b1c3c6899f8f495b33bc93e687,'
                    . 'd0fa31da9e7c76a00320cf103609dcc5,'
                    . '7f07e1cadb025052d6988fc87d7a351a,'
                    . 'd9b473057c0c7486538a70e7b010f853,'
                    . 'f95552d896f68ce2c7dca0624ce7e29f,'
                    . '39735b7182723ad149214de14fc478d8';

        $users      = array('User1', 'User441', 'User83');
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsers( $users,
                                              false );  // !exact
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsers3()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . 'ba7215776973fafa3f5b0bfd263e3ec2,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . '0ba1beb65991ba4d06fac047bf72df49,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . 'b79d82b1c3c6899f8f495b33bc93e687,'
                    . 'd0fa31da9e7c76a00320cf103609dcc5,'
                    . '7f07e1cadb025052d6988fc87d7a351a,'
                    . 'd9b473057c0c7486538a70e7b010f853,'
                    . 'f95552d896f68ce2c7dca0624ce7e29f,'
                    . '39735b7182723ad149214de14fc478d8';

        $users      = 'User1,User441, User83';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsers( $users,
                                              false );  // !exact
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByTagsAny1()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . 'f6cbe8f4ff12275e776a401cf2679469';
        $tags       = array(6, 12);
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByTags( $tags,
                                             false );  // !exact
        $this->assertNotEquals(null, $items);

        //printf ("Items: [ %s ]\n", print_r($items->toArray(), true));

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByTagsAny2()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . 'f6cbe8f4ff12275e776a401cf2679469';
        $tags       = array('web2.0', 'javascript');
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByTags( $tags,
                                             false );  // !exact
        $this->assertNotEquals(null, $items);

        //printf ("Items: [ %s ]\n", print_r($items->toArray(), true));

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByTagsAny3()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . 'f6cbe8f4ff12275e776a401cf2679469';
        $tags       = 'web2.0, javascript';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByTags( $tags,
                                             false );  // !exact
        $this->assertNotEquals(null, $items);

        //printf ("Items: [ %s ]\n", print_r($items->toArray(), true));

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByTagsAny4()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3,'
                    . 'f6cbe8f4ff12275e776a401cf2679469';
        $tags       = '6, 12';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByTags( $tags,
                                             false );  // !exact
        $this->assertNotEquals(null, $items);

        //printf ("Items: [ %s ]\n", print_r($items->toArray(), true));

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }


    public function testItemServiceFetchByTagsExact()
    {
        //            vv ordered by 'tagCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae';
        $tags       = array(6, 12);
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByTags( $tags,
                                             true  );  // exact
        $this->assertNotEquals(null, $items);

        //printf ("Items: [ %s ]\n", print_r($items->toArray(), true));

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsersAndTagsAny1()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3';

        $users      = array(1, 2, 3);
        $tags       = array(6, 12);
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsersAndTags( $users,
                                                     $tags,
                                                     false,     // !exactUsers
                                                     false);    // !exactTags
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsersAndTagsAny2()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3';

        $users      = array('User1','User441','User83');
        $tags       = array('web2.0','javascript');
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsersAndTags( $users,
                                                     $tags,
                                                     false,     // !exactUsers
                                                     false);    // !exactTags
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsersAndTagsAny3()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3';

        $users      = 'User1,User441,User83';
        $tags       = 'web2.0,javascript';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsersAndTags( $users,
                                                     $tags,
                                                     false,     // !exactUsers
                                                     false);    // !exactTags
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceFetchByUsersAndTagsAny4()
    {
                    // vv ordered by 'userCount DESC'
        $expected   = '52cda3e66df5938103c48725357c59ab,'
                    . '052973b1ac311978abdc0413daa1d5db,'
                    . 'd78f0feda6386fb621bdc0ffe30c55ae,'
                    . '383cb614a2cc9247b86cad9a315d02e3,'
                    . '3df00b4987258758e9921d07eace89c3';

        $users      = '1,2,3';
        $tags       = '6,12';
        $service    = Connexions_Service::factory('Model_Item');
        $items      = $service->fetchByUsersAndTags( $users,
                                                     $tags,
                                                     false,     // !exactUsers
                                                     false);    // !exactTags
        $this->assertNotEquals(null, $items);

        $items      = $items->__toString();

        //printf ("Items: [ %s ]\n", $items);

        $this->assertEquals($expected, $items);
    }

    public function testItemServiceSimilar1()
    {
        $expected = $this->_item4['urlHash'];

        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetchSimilar( $this->_item3['itemId'] );

        $this->assertEquals($expected,
                            $items->__toString());
    }

    public function testItemServiceSimilar2()
    {
        $url      = 'http://demo.openlinksw.com/';
        $expected = $this->_item3['urlHash']
                  . ','. $this->_item4['urlHash'];

        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetchSimilar( $url );

        //printf ("similar items[ %s ]", $items);

        $this->assertEquals($expected,
                            $items->__toString());
    }

    public function testItemServiceSimilar3()
    {
        $expected = $this->_item4['urlHash'];

        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetchSimilar( array(
                                'itemId' => $this->_item3['itemId']
                    ));

        $this->assertEquals($expected,
                            $items->__toString());
    }

    public function testItemServiceSimilar4()
    {
        $expected = $this->_item4['urlHash'];

        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetchSimilar( array(
                                'url' => $this->_item3['url']
                    ));

        $this->assertEquals($expected,
                            $items->__toString());
    }

    public function testItemServiceSimilar5()
    {
        $expected = $this->_item4['urlHash'];

        $service  = Connexions_Service::factory('Model_Item');
        $items    = $service->fetchSimilar( array(
                                'urlHash' => $this->_item3['urlHash']
                    ));

        $this->assertEquals($expected,
                            $items->__toString());
    }
}
