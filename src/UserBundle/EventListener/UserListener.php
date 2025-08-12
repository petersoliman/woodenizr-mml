<?php

namespace App\UserBundle\EventListener;

use App\UserBundle\Model\UserInterface;
use App\UserBundle\Util\CanonicalFieldsUpdater;
use App\UserBundle\Util\PasswordUpdaterInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;

class UserListener implements EventSubscriberInterface
{
    private PasswordUpdaterInterface $passwordUpdater;
    private CanonicalFieldsUpdater $canonicalFieldsUpdater;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        CanonicalFieldsUpdater $canonicalFieldsUpdater
    ) {
        $this->passwordUpdater = $passwordUpdater;
        $this->canonicalFieldsUpdater = $canonicalFieldsUpdater;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    /**
     * Pre persist listener based on doctrine common.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        if ($object instanceof UserInterface) {
            $this->updateUserFields($object);
        }
    }

    /**
     * Pre update listener based on doctrine common.
     *
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        if ($object instanceof UserInterface) {
            $this->updateUserFields($object);
            $this->recomputeChangeSet($args->getObjectManager(), $object);
        }
    }

    /**
     * Updates the user properties.
     *
     * @param UserInterface $user
     */
    private function updateUserFields(UserInterface $user): void
    {
        $this->canonicalFieldsUpdater->updateCanonicalFields($user);
        $this->passwordUpdater->hashPassword($user);
    }

    /**
     * Recomputes change set for Doctrine implementations not doing it automatically after the event.
     *
     * @param ObjectManager $om
     * @param UserInterface $user
     */
    private function recomputeChangeSet(ObjectManager $om, UserInterface $user): void
    {
        $meta = $om->getClassMetadata(get_class($user));

        if ($om instanceof EntityManager) {
            $om->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $user);

            return;
        }
    }
}
