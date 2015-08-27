<?php

namespace Ma27\ApiKeyAuthenticationBundle\Model\Login\ApiToken;

use Doctrine\Common\Persistence\ObjectManager;
use Ma27\ApiKeyAuthenticationBundle\Event\Events;
use Ma27\ApiKeyAuthenticationBundle\Event\OnAuthenticationEvent;
use Ma27\ApiKeyAuthenticationBundle\Event\OnInvalidCredentialsEvent;
use Ma27\ApiKeyAuthenticationBundle\Event\OnLogoutEvent;
use Ma27\ApiKeyAuthenticationBundle\Exception\CredentialException;
use Ma27\ApiKeyAuthenticationBundle\Model\Key\KeyFactoryInterface;
use Ma27\ApiKeyAuthenticationBundle\Model\Login\AuthorizationHandlerInterface;
use Ma27\ApiKeyAuthenticationBundle\Model\Password\PasswordHasherInterface;
use Ma27\ApiKeyAuthenticationBundle\Model\User\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Concrete handler for api key authorization
 */
class ApiKeyAuthorizationHandler implements AuthorizationHandlerInterface
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var PasswordHasherInterface
     */
    private $passwordHasher;

    /**
     * @var KeyFactoryInterface
     */
    private $keyFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var string
     */
    private $passwordProperty;

    /**
     * @var string
     */
    private $userProperty;

    /**
     * @var string
     */
    private $emailProperty;

    /**
     * Constructor
     *
     * @param ObjectManager $om
     * @param PasswordHasherInterface $passwordHasher
     * @param KeyFactoryInterface $keyFactory
     * @param EventDispatcherInterface $dispatcher
     * @param string $modelName
     * @param string $passwordProperty
     * @param string $userProperty
     * @param string $emailProperty
     */
    public function __construct(
        ObjectManager $om,
        PasswordHasherInterface $passwordHasher,
        KeyFactoryInterface $keyFactory,
        EventDispatcherInterface $dispatcher,
        $modelName,
        $passwordProperty,
        $userProperty = null,
        $emailProperty = null
    ) {
        $this->om = $om;
        $this->passwordHasher = $passwordHasher;
        $this->keyFactory = $keyFactory;
        $this->eventDispatcher = $dispatcher;
        $this->modelName = (string) $modelName;
        $this->passwordProperty = (string) $passwordProperty;
        $this->userProperty = $userProperty;
        $this->emailProperty = $emailProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $credentials)
    {
        if (null === $this->userProperty && null === $this->emailProperty) {
            throw new \InvalidArgumentException('Username property and email property must not be null!');
        }

        $query = array();
        if (null !== $this->userProperty) {
            if (!isset($credentials[$this->userProperty])) {
                throw new \InvalidArgumentException(
                    sprintf('Unable to required find property "%s" in credential array!', $this->userProperty)
                );
            }

            $query[$this->userProperty] = $credentials[$this->userProperty];
        }

        if (null !== $this->emailProperty) {
            if (!isset($credentials[$this->emailProperty])) {
                throw new \InvalidArgumentException(
                    sprintf('Unable to required find property "%s" in credential array!', $this->emailProperty)
                );
            }

            $query[$this->emailProperty] = $credentials[$this->emailProperty];
        }

        if (!isset($credentials[$this->passwordProperty])) {
            throw new \InvalidArgumentException(
                sprintf('Unable to find password property "%s" in credential set!', $this->passwordProperty)
            );
        }

        $objectRepository = $this->om->getRepository($this->modelName);
        /** @var UserInterface $object */
        $object = $objectRepository->findOneBy($query);

        if (
            null === $object
            || !$this->passwordHasher->compareWith(
                $object->getPassword(), $credentials[$this->passwordProperty]
            )
        ) {
            $this->eventDispatcher->dispatch(Events::CREDENTIAL_FAILURE, new OnInvalidCredentialsEvent($object));

            throw new CredentialException;
        }

        $this->eventDispatcher->dispatch(Events::AUTHENTICATION, new OnAuthenticationEvent($object));

        $object->setApiKey($this->keyFactory->getKey());
        $this->om->merge($object);

        $this->om->flush();

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function removeSession(UserInterface $user, $purgeJob = false)
    {
        $user->removeApiKey();

        $event = new OnLogoutEvent($user);
        if ($purgeJob) {
            $event->markAsPurgeJob();
        }

        $this->eventDispatcher->dispatch(Events::LOGOUT, $event);

        $this->om->merge($user);

        // on purge jobs one big flush will be commited to the db after the whole action
        if (!$purgeJob) {
            $this->om->flush();
        }
    }
}
