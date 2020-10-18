<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\PHRETS;

class SessionBuilder implements SessionBuilderInterface
{
    /** @var ?string */
    private $login_url;

    /** @var ?string */
    private $username;

    /** @var ?string */
    private $password;

    /** @var ?string */
    private $user_agent;

    /** @var ?string */
    private $user_agent_password;

    /** @var ?string */
    private $rets_version;

    /** @var ?bool */
    private $option_use_post_method;

    /** @var ?string */
    private $option_http_authentication_method;


    public function build(): \PHRETS\Session
    {
        $phretsConfiguration = new \PHRETS\Configuration();
        $phretsConfiguration->setLoginUrl($this->getLoginUrl());
        $phretsConfiguration->setUsername($this->getUsername());
        $phretsConfiguration->setPassword($this->getPassword());
        if ($this->hasRetsVersion()) {
            $phretsConfiguration->setRetsVersion($this->getRetsVersion());
        }
        if ($this->hasUserAgent()) {
            $phretsConfiguration->setUserAgent($this->getUserAgent());
        }
        if ($this->hasUserAgentPassword()) {
            $phretsConfiguration->setUserAgentPassword($this->getUserAgentPassword());
        }
        if ($this->hasOptionUsePostMethod()) {
            $phretsConfiguration->setOption('use_post_method', $this->isOptionUsePostMethod());
        }
        if ($this->hasOptionHttpAuthenticationMethod()) {
            $phretsConfiguration->setHttpAuthenticationMethod($this->getOptionHttpAuthenticationMethod());
        }
        return new \PHRETS\Session($phretsConfiguration);
    }

    public function fromConfigurationArray(array $cofigurationArray) : \PHRETS\Session
    {
        $this->setLoginUrl($cofigurationArray['login_url'] ?? null);
        $this->setUsername($cofigurationArray['username'] ?? null);
        $this->setPassword($cofigurationArray['password'] ?? null);
        $this->setUserAgent($cofigurationArray['user_agent'] ?? null);
        $this->setUserAgentPassword($cofigurationArray['user_agent_password'] ?? null);
        $this->setRetsVersion($cofigurationArray['rets_version'] ?? null);
        $this->setOptionUsePostMethod($cofigurationArray['use_post_method'] ?? null);
        $this->setOptionHttpAuthenticationMethod($cofigurationArray['http_authentication_method'] ?? null);
        return $this->build();
    }

    private function getLoginUrl(): string
    {
        if ($this->login_url === null) {
            throw new \LogicException('SessionBuilder login_url has not been set.');
        }

        return $this->login_url;
    }

    public function setLoginUrl(?string $login_url): SessionBuilderInterface
    {
        if ($this->login_url !== null) {
            throw new \LogicException('SessionBuilder login_url already set.');
        }

        $this->login_url = $login_url;

        return $this;
    }

    private function hasLoginUrl() : bool
    {
        return $this->login_url !== null;
    }

    private function getUsername(): string
    {
        if ($this->username === null) {
            throw new \LogicException('SessionBuilder username has not been set.');
        }

        return $this->username;
    }

    public function setUsername(?string $username): SessionBuilderInterface
    {
        if ($this->username !== null) {
            throw new \LogicException('SessionBuilder username already set.');
        }

        $this->username = $username;

        return $this;
    }

    private function hasUsername() : bool
    {
        return $this->username !== null;
    }

    private function getPassword(): string
    {
        if ($this->password === null) {
            throw new \LogicException('SessionBuilder password has not been set.');
        }

        return $this->password;
    }

    public function setPassword(?string $password): SessionBuilderInterface
    {
        if ($this->password !== null) {
            throw new \LogicException('SessionBuilder password already set.');
        }

        $this->password = $password;

        return $this;
    }

    private function hasPassword() : bool
    {
        return $this->password !== null;
    }

    private function getUserAgent(): string
    {
        if ($this->user_agent === null) {
            throw new \LogicException('SessionBuilder user_agent has not been set.');
        }

        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): SessionBuilderInterface
    {
        if ($this->user_agent !== null) {
            throw new \LogicException('SessionBuilder user_agent already set.');
        }

        $this->user_agent = $user_agent;

        return $this;
    }

    private function hasUserAgent() : bool
    {
        return $this->user_agent !== null;
    }

    private function getUserAgentPassword(): string
    {
        if ($this->user_agent_password === null) {
            throw new \LogicException('SessionBuilder user_agent_password has not been set.');
        }

        return $this->user_agent_password;
    }

    public function setUserAgentPassword(?string $user_agent_password): SessionBuilderInterface
    {
        if ($this->user_agent_password !== null) {
            throw new \LogicException('SessionBuilder user_agent_password already set.');
        }

        $this->user_agent_password = $user_agent_password;

        return $this;
    }

    private function hasUserAgentPassword() : bool
    {
        return $this->user_agent_password !== null;
    }

    private function getRetsVersion(): string
    {
        if ($this->rets_version === null) {
            throw new \LogicException('SessionBuilder rets_version has not been set.');
        }

        return $this->rets_version;
    }

    public function setRetsVersion(?string $rets_version): SessionBuilderInterface
    {
        if ($this->rets_version !== null) {
            throw new \LogicException('SessionBuilder rets_version already set.');
        }

        $this->rets_version = $rets_version;

        return $this;
    }

    private function hasRetsVersion() : bool
    {
        return $this->rets_version !== null;
    }

    private function isOptionUsePostMethod(): bool
    {
        if ($this->option_use_post_method === null) {
            throw new \LogicException('SessionBuilder option_use_post_method has not been set.');
        }

        return $this->option_use_post_method;
    }

    public function setOptionUsePostMethod(?bool $option_use_post_method): SessionBuilderInterface
    {
        if ($this->option_use_post_method !== null) {
            throw new \LogicException('SessionBuilder option_use_post_method already set.');
        }

        $this->option_use_post_method = $option_use_post_method;

        return $this;
    }

    private function hasOptionUsePostMethod() : bool
    {
        return $this->option_use_post_method !== null;
    }

    private function getOptionHttpAuthenticationMethod(): string
    {
        if ($this->option_http_authentication_method === null) {
            throw new \LogicException('SessionBuilder option_http_authentication_method has not been set.');
        }

        return $this->option_http_authentication_method;
    }

    public function setOptionHttpAuthenticationMethod(
        ?string $option_http_authentication_method
    ): SessionBuilderInterface {
        if ($this->option_http_authentication_method !== null) {
            throw new \LogicException('SessionBuilder option_http_authentication_method already set.');
        }

        $this->option_http_authentication_method = $option_http_authentication_method;

        return $this;
    }

    private function hasOptionHttpAuthenticationMethod() : bool
    {
        return $this->option_http_authentication_method !== null;
    }
}
