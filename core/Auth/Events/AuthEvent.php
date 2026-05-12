<?php

declare(strict_types=1);

namespace Fly\Auth\Events;

use Fly\Auth\AuthenticatableInterface;

abstract class AuthEvent
{
    public function __construct(public ?AuthenticatableInterface $user = null) {}
}

class Login extends AuthEvent 
{
    public function __construct(AuthenticatableInterface $user, public bool $remember = false)
    {
        parent::__construct($user);
    }
}

class Logout extends AuthEvent {}

class Failed extends AuthEvent
{
    public function __construct(?AuthenticatableInterface $user, public array $credentials)
    {
        parent::__construct($user);
    }
}

class Authenticated extends AuthEvent {}
