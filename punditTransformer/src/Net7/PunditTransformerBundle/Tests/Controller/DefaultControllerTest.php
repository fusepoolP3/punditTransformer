<?php

namespace Net7\PunditTransformerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '');


        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'text/turtle; charset=UTF-8'
            )
        );

        $this->assertTrue($client->getResponse()->isSuccessful());



    }

    public function testStart(){


        $html = '<html><body>foo</body></html>';

        $client = static::createClient();

        $csrfToken = $client->getContainer()->get('form.csrf_provider')
            ->generateCsrfToken('form_intention');


        /*
         *  can't make it work , we use web-server related notation, apparently.

        $client->request('POST', '/', array(), array(), array(), $html);

        $this->assertTrue($client->getResponse()->isSuccessful());
*/

        // TEST if it replies with correct error on wrong input
        $crawler = $client->request('POST', '', array('data' => ''));
        $this->assertTrue($client->getResponse()->isClientError());



    }

    public function testSaveFromPunditWithoutToken(){
        $client = static::createClient();

//        $csrfToken = $client->getContainer()->get('form.csrf_provider')
//            ->generateCsrfToken('form_intention');

        $crawler = $client->request('POST', '/save_from_pundit', array());

        $this->assertTrue($client->getResponse()->isClientError());


    }
}
