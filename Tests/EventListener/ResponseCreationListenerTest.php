<?php

namespace Ma27\ApiKeyAuthenticationBundle\Tests\EventListener;

use Ma27\ApiKeyAuthenticationBundle\Event\OnAssembleResponseEvent;
use Ma27\ApiKeyAuthenticationBundle\EventListener\ResponseCreationListener;
use Ma27\ApiKeyAuthenticationBundle\Exception\CredentialException;
use Ma27\ApiKeyAuthenticationBundle\Service\Mapping\ClassMetadata;

class ResponseCreationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildAPIKeyResponse()
    {
        $user = new \stdClass();
        $key = uniqid();

        $metadata = $this->getMockBuilder('Ma27\ApiKeyAuthenticationBundle\Service\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $metadata->expects($this->once())
            ->method('getPropertyValue')
            ->with($user, ClassMetadata::API_KEY_PROPERTY)
            ->willReturn($key);

        $listener = new ResponseCreationListener(
            $this->createMock('Symfony\Component\Translation\TranslatorInterface'),
            $metadata,
            array('api_key_property' => 'apiKey', 'error_property' => 'message')
        );

        $event = new OnAssembleResponseEvent($user);

        $listener->onResponseCreation($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $data = json_decode($response->getContent(), true);
        $this->assertSame($data['apiKey'], $key);
    }

    public function testAssembleErrors()
    {
        $user = null;
        $ex = new CredentialException('Invalid username and password!');

        $translatedIntoGerman = 'Ungültige Zugangsdaten!';

        $metadata = $this->getMockBuilder('Ma27\ApiKeyAuthenticationBundle\Service\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->once())
            ->method('trans')
            ->with('Invalid username and password!')
            ->willReturn($translatedIntoGerman);

        $listener = new ResponseCreationListener(
            $translator,
            $metadata,
            array('api_key_property' => 'apiKey', 'error_property' => 'message')
        );

        $event = new OnAssembleResponseEvent($user, $ex);

        $listener->onResponseCreation($event);
        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $data = json_decode($response->getContent(), true);
        $this->assertSame($translatedIntoGerman, $data['message']);
    }
}
