<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\PHRETS;

interface SessionBuilderInterface
{
    public function setLoginUrl(?string $login_url) : SessionBuilderInterface;
    public function setUsername(?string $username) : SessionBuilderInterface;
    public function setPassword(?string $password) : SessionBuilderInterface;
    public function setUserAgent(?string $user_agent) : SessionBuilderInterface;
    public function setUserAgentPassword(?string $user_agent_password): SessionBuilderInterface;
    public function setRetsVersion(?string $rets_version): SessionBuilderInterface;
    public function setOptionUsePostMethod(?bool $option_use_post_method): SessionBuilderInterface;
    public function setOptionHttpAuthenticationMethod(?string $option_http_authentication_method): SessionBuilderInterface;

    public function build() : \PHRETS\Session;

    public function fromConfigurationArray(array $cofigurationArray) : \PHRETS\Session;
}
