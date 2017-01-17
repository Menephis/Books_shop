<?php

namespace src\Silex\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private $username;
    private $password;
    private $roles;
    
    public function __construct($username, $password, $role)
    {
        $this->username = $username;
        $this->password = $password;
        $this->roles = $role;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return;
    }

    public function getUsername()
    {
        return $this->username;
    } 
    public function eraseCredentials(){
        $this->password = null;
    }
}
?>