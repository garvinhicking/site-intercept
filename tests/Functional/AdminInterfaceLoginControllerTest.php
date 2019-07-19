<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInterfaceLoginControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function loginFormIsRendered()
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Username:/', $client->getResponse()->getContent());
        $this->assertRegExp('/Password:/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function canLoginAsUserFails()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');

        $form = $crawler->selectButton('Login')->form();
        $form['_username'] = 'myUser';
        $form['_password'] = 'myPassword';
        $client->submit($form);
        $client->followRedirect();
        $this->assertRegExp('/Login not successful: Bad credentials/', $client->getResponse()->getContent());
    }
}
