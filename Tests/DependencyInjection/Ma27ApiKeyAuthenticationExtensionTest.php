<?php

namespace Ma27\ApiKeyAuthenticationBundle\Tests\DependencyInjection;

use Ma27\ApiKeyAuthenticationBundle\DependencyInjection\Ma27ApiKeyAuthenticationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class Ma27ApiKeyAuthenticationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The user model properties must be unique! Duplicated items found: foo
     */
    public function testDuplicatedModelProperties()
    {
        $container = new ContainerBuilder();
        $extension = new Ma27ApiKeyAuthenticationExtension();

        $extension->load(
            array(
                'ma27_api_key_authentication' => array(
                    'user' => array(
                        'object_manager' => 'om',
                        'properties' => array(
                            'username' => 'foo',
                            'apiKey' => 'foo',
                            'password' => array(
                                'strategy' => 'sha512',
                                'property' => 'password'
                            )
                        )
                    )
                )
            ),
            $container
        );
    }

    public function testFullConfig()
    {
        $container = new ContainerBuilder();
        $extension = new Ma27ApiKeyAuthenticationExtension();

        $extension->load(
            array(
                'ma27_api_key_authentication' => array(
                    'user' => array(
                        'object_manager' => 'om',
                        'properties' => array(
                            'username' => 'username',
                            'apiKey' => 'apiKey',
                            'password' => array(
                                'strategy' => 'sha512',
                                'property' => 'password'
                            )
                        )
                    ),
                    'api_key_purge' => array(
                        'enabled' => true,
                        'last_active_property' => 'lastActivation',
                        'log_state' => true
                    ),
                    'services' => array(
                        'auth_handler' => 'foo.bar'
                    )
                )
            ),
            $container
        );

        $logger = $this->getMock('Psr\\Log\\LogInterface');

        $container->setDefinition('foo.bar', new Definition('stdClass'));
        $container->setDefinition('logger', new Definition(get_class($logger)));
        $container->setDefinition('event_dispatcher', new Definition('Symfony\\Component\\EventDispatcher\\EventDispatcher'));

        $container->compile();

        $this->assertSame((string) $container->getAlias('ma27.auth.service.auth_handler'), 'foo.bar');
        $this->assertSame(
            'Ma27\\ApiKeyAuthenticationBundle\\Model\\Password\\Sha512PasswordHasher',
            $container->getDefinition('ma27.auth.password.strategy')->getClass()
        );
    }
}