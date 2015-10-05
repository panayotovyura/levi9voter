<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Vote
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Vote
{
    const LIKE = 1;
    const DISLIKE = -1;


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="vote", type="integer")
     */
    private $vote;

    /**
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $post;

    /**
     * @ORM\Column(type="string")
     * @Assert\Email()
     */
    private $authorEmail;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set vote
     *
     * @param int $vote
     * @return $this
     */
    public function setVote($vote)
    {
        $this->vote = ($vote == self::LIKE) ? self::LIKE : self::DISLIKE;
        return $this;
    }

    /**
     * Get vote
     *
     * @return integer
     */
    public function getVote()
    {
        return $this->vote;
    }

    public function getPost()
    {
        return $this->post;
    }
    public function setPost(Post $post = null)
    {
        $this->post = $post;
        return $this;
    }

    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }
    public function setAuthorEmail($authorEmail)
    {
        $this->authorEmail = $authorEmail;
        return $this;
    }
}
