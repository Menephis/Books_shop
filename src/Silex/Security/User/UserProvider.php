<?php
namespace src\Silex\Security\User;

use src\Silex\Security\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\DBAL\Connection;

class UserProvider implements UserProviderInterface
{
    protected $connection;
    public function __construct(Connection $connection){
        $this->connection = $connection;
    }
    public function loadUserByUsername($userName)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('u.password', 'u.role')
            ->from('users', 'u')
            ->where('email = :user_name');
            $stmt = $this->connection->prepare($qb);
            $stmt->bindValue(':user_name', $userName);
            $stmt->execute();
        $userData = $stmt->fetch();
        if ($userData) {
            return new User($userName, $userData['password'], array('ROLE_' . strtoupper($userData['role'])));
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $userName)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if ( ! $user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return UserProvider::class === $class;
    }
}
?>