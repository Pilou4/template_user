<?php

namespace App\EventSubscriber;

// use App\Entity\Post;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UserSubscriber implements EventSubscriber
{

    public function __construct(private Security $security)
    {
        
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            
        ];
    }

    public  function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getObject();
        
        $user  = $this->security->getUser();
        
        // if ($entity instanceof Post) {
        //     $entity->setUser($user);
        //     return $entity;
        // }
        
    }
}