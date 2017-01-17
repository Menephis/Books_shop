<?php
/*
 * Extends BCrypt for add local parametr
 *
 *  Kurapov Pavel <spawnrus56@gmail.com>
 */
namespace src\Silex\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class BCryptEncodeWithParameter extends BCryptPasswordEncoder
{
    const MAX_PASSWORD_LENGTH = 60;
    /**
    * @var string - local parametr
    */
    protected $parameter;
    /**
    * set local parameter
    *
    * @param string $const - const BCrypt algorithm 
    * @param string $localParameter - local parameter for crypting
    *
    * @throws \InvalidArgumentException if length of local parametr is not 12 symbols
    */
    public function __construct($cost, $localParameter)
    {
        parent::__construct($cost);
        if(strlen($localParameter) !== 12){
            throw new \InvalidArgumentException('Localparameter string length must be 12 symbols.');
        }
        $this->parameter = $localParameter;
    }
    /**
    * If you want read more about this function please go to parent class
    *
    * @param string $raw
    * @param string $salt
    *
    * @throws BadCredentialsException when the given password is too long
    */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        $options = array('cost' => $this->cost);
        $raw = $raw . $this->parameter;
        return password_hash($raw, PASSWORD_BCRYPT, $options);
    }
    /**
     * @param string $encoded 
     * @param string $raw
     * @param string $salt
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return !$this->isPasswordTooLong($raw) && password_verify($raw . $this->parameter, $encoded);
    }
} 
?>