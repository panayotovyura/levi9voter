<?php

namespace AppBundle\Security\Authorization\Voter;

use AppBundle\Entity\Post;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @todo: cover with unit tests
 */
class PostVoter extends AbstractVoter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const VOTE = 'vote';

    /**
     * {@inheritdoc}
     */
    protected function getSupportedClasses()
    {
        return [
            Post::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes()
    {
        return [
            self::VIEW,
            self::EDIT,
            self::VOTE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isGranted($attribute, $object, $user = null)
    {
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->isViewGranted($object, $user);
            case self::EDIT:
                return $this->isEditGranted($object, $user);
            case self::VOTE:
                return Post::STATUS_VOTING === $object->getState();
        }

        return false;
    }

    /**
     * @param Post $post
     * @param User $user
     *
     * @return bool
     */
    private function isViewGranted(Post $post, User $user)
    {
        switch ($post->getState()) {
            case Post::STATUS_DRAFT:
                return $post->isAuthor($user);
            case Post::STATUS_REVIEW:
                return $post->isAuthor($user) || $user->isAdmin();
            case Post::STATUS_VOTING:
                return true;
            case Post::STATUS_APPROVED:
                return true;
            case Post::STATUS_REJECTED:
                return true;
        }

        return false;
    }

    /**
     * @param Post $post
     * @param User $user
     *
     * @return bool
     */
    private function isEditGranted(Post $post, User $user)
    {
        switch ($post->getState()) {
            case Post::STATUS_DRAFT:
                return $post->isAuthor($user);
            case Post::STATUS_REVIEW:
                return $user->isAdmin();
        }

        return false;
    }
}
