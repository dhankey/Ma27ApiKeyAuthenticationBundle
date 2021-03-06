<?php

namespace Ma27\ApiKeyAuthenticationBundle\Tests\Controller;

use Ma27\ApiKeyAuthenticationBundle\Tests\Resources\Entity\TestUser;
use Ma27\ApiKeyAuthenticationBundle\Tests\WebTestCase;

class ApiKeyControllerTest extends WebTestCase
{
    /**
     * @dataProvider getEnvs
     */
    public function testRefusedCredentials($env)
    {
        $this->prepare($env);
        $client = $this->client($env);

        $client->request('POST', '/api-key.json', array('login' => 'foo', 'password' => 'foo'));
        $response = $client->getResponse();

        $bareResponse = json_decode($response->getContent(), true);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\JsonResponse', $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame($bareResponse['message'], 'Credentials refused!');
    }

    /**
     * @dataProvider getEnvs
     */
    public function testLogin($env)
    {
        $this->prepare($env);
        $client = $this->client($env);

        // clear the last action to prove that it will be reloaded properly during the login
        $testUser = $this->getFixtureUser();
        $testUser->clearLastAction();
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $em->persist($testUser);
        $em->flush();

        $client->request('POST', '/api-key.json', array('login' => 'Ma27', 'password' => '123456'));
        $response = $client->getResponse();

        $content = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('apiKey', $content);

        $client->request('GET', '/restricted.html', array(), array(), array('HTTP_X-API-KEY' => $content['apiKey']));

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $user = $this->getFixtureUser();
        $this->assertNotEmpty($user->getLastAction());
    }

    /**
     * @dataProvider getEnvs
     */
    public function testLogoutWithMissingApiKey($env)
    {
        $this->prepare($env);
        $client = $this->client($env);

        $client->request('DELETE', '/api-key.json');
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());

        $bareResponse = json_decode($response->getContent(), true);
        $this->assertSame('Missing api key header!', $bareResponse['message']);
    }

    /**
     * @dataProvider getEnvs
     */
    public function testLogout($env)
    {
        $this->prepare($env);
        $client = $this->client($env);

        $client->request('POST', '/api-key.json', array('login' => 'Ma27', 'password' => '123456'));
        $response = json_decode($client->getResponse()->getContent(), true);

        $apiKey = $response['apiKey'];
        $client->request('GET', '/restricted.html', array(), array(), array('HTTP_X-API-KEY' => $apiKey));
        $testResponse = $client->getResponse();
        $this->assertSame(200, $testResponse->getStatusCode());

        $client->request('DELETE', '/api-key.json', array(), array(), array('HTTP_X-API-KEY' => $apiKey));
        $logoutResponse = $client->getResponse();

        $this->assertSame(204, $logoutResponse->getStatusCode());
    }

    /**
     * @dataProvider getEnvs
     */
    public function testLoginWithEmptyCredentials($env)
    {
        $this->prepare($env);

        $client = static::createClient(array('environment' => $env));
        $client->request('POST', '/api-key.json', array('login' => null, 'password' => null));
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider getEnvs
     */
    public function testMultipleLogins($env)
    {
        $this->prepare($env);
        $client = $this->client($env);

        $data = array();
        for ($i = 0; $i < 2; $i++) {
            $client->request('POST', '/api-key.json', array('login' => 'Ma27', 'password' => '123456'));

            $response = $client->getResponse();
            $content = json_decode($response->getContent(), true);
            $data[] = $content['apiKey'];
        }

        $this->assertSame($data[0], $data[1]);
    }

    /**
     * Prepares the kernel.
     *
     * @param string $env
     */
    private function prepare($env)
    {
        $login = 'Ma27';
        $password = '123456';

        $kernel = static::createKernel(array('environment' => $env));
        $kernel->boot();

        $container = $kernel->getContainer();
        $container->get('doctrine.dbal.default_connection')->exec('DELETE FROM TestUser');

        $user = new TestUser();
        $user->setUsername($login);
        $user->setPassword($container->get('ma27_api_key_authentication.password.strategy')->generateHash($password));
        $user->setEmail('foo@example.org');

        $em = $container->get('doctrine.orm.default_entity_manager');
        $em->persist($user);
        $em->flush();
    }
}
