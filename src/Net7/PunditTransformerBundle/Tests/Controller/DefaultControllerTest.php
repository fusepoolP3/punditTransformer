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

        $crawler = $client->request('POST', '/', array('data' => $html));

        echo $client->getResponse()->getStatusCode();

        $this->assertTrue($client->getResponse()->isSuccessful());



        // TEST it replies with correct error on wrong input

        $crawler = $client->request('POST', '', array('data' => ''));
        $this->assertTrue($client->getResponse()->isServerError());



    }

}
