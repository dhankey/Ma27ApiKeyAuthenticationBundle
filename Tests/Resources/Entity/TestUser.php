<?php

namespace Ma27\ApiKeyAuthenticationBundle\Tests\Resources\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ma27\ApiKeyAuthenticationBundle\Annotation as Auth;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity()
 */
class TestUser implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="email", unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="api_key", unique=true, nullable=true, length=255)
     * @Auth\ApiKey
     */
    private $apiKey;

    /**
     * @ORM\Column(name="password", length=500)
     * @Auth\Password
     */
    private $password;

    /**
     * @ORM\Column(name="username", unique=true)
     * @Auth\Login
     */
    private $username;

    /**
     * @ORM\Column(name="last_action", type="datetime", nullable=true)
     * @Auth\LastAction()
     */
    private $lastAction;

    public function eraseCredentials()
    {
    }

    public function getEmail()
    {
        return $this->getEmail();
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getRoles()
    {
        return array();
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function removeApiKey()
    {
        $this->apiKey = null;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getLastAction()
    {
        return $this->lastAction;
    }

    public function clearLastAction()
    {
        // only for testing reasons
        $this->lastAction = null;
    }
}
