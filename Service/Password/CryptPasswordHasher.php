<?php

namespace Ma27\ApiKeyAuthenticationBundle\Service\Password;

/**
 * Hasher which uses the crypt algorithm.
 */
class CryptPasswordHasher implements PasswordHasherInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateHash($password)
    {
        return crypt($password, sprintf('$6$rounds=3000$%s$', $this->generateSalt()));
    }

    /**
     * {@inheritdoc}
     */
    public function compareWith($password, $raw)
    {
        return crypt($raw, $password) === $password;
    }

    /**
     * Generates a salt using the openssl API.
     *
     * @return string
     */
    protected function generateSalt()
    {
        return bin2hex(random_bytes(10));
    }
}
