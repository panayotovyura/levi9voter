<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PostRepository")
 *
 * Defines the properties of the Post entity to represent the blog posts.
 * See http://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See http://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Post
{
    /**
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See http://symfony.com/doc/current/best_practices/configuration.html#constants-vs-configuration-options
     */
    const NUM_ITEMS = 10;

    const STATUS_DRAFT = 0;
    const STATUS_REVIEW = 1;
    const STATUS_VOTING = 2;
    const STATUS_APPROVED = 3;
    const STATUS_REJECTED = 4;

    const VOTING_MOST_RATED = 0;
    const VOTING_MOST_POPULAR = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\Column(type="string")
     */
    private $slug;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="post.blank_summary")
     */
    private $summary;

    /**
     * @ORM\Column(type="text")
     * @Assert\Length(min = "10", minMessage = "post.too_short_content")
     */
    private $content;

    /**
     * @ORM\Column(type="string")
     * @Assert\Email()
     */
    private $authorEmail;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $publishedAt;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Comment",
     *      mappedBy="post",
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"publishedAt" = "DESC"})
     */
    private $comments;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="posts", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Vote",
     *      mappedBy="post",
     *      orphanRemoval=true
     * )
     */
    private $votes;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $state = 0;

    public function __construct()
    {
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail($authorEmail)
    {
        $this->authorEmail = $authorEmail;
    }

    /**
     * Is the given User the author of this Post?
     *
     * @param User $user
     *
     * @return bool
     */
    public function isAuthor(User $user = null)
    {
        return $user->getEmail() == $this->getAuthorEmail();
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
        $comment->setPost($this);
    }

    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
        $comment->setPost(null);
    }

    public function getVotes()
    {
        return $this->votes;
    }

    public function addVote(Vote $vote)
    {
        $this->votes->add($vote);
        $vote->setPost($this);
    }

    public function removeVote(Vote $vote)
    {
        $this->votes->removeElement($vote);
        $vote->setPost(null);
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getAgreeCount()
    {
        $agree = 0;
        foreach ($this->votes as $vote) {
            if ($vote->getVote() == Vote::LIKE) {
                $agree++;
            }
        }
        return $agree;
    }

    public function getNotAgreeCount()
    {
        $notAgree = 0;
        foreach ($this->votes as $vote) {
            if ($vote->getVote() == Vote::DISLIKE) {
                $notAgree++;
            }
        }

        return $notAgree;
    }
}
