<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class Category
 * @package AppBundle\Entity
 * @ORM\Entity
 */
class Category
{

    /**
     *
     */
    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }


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
    private $name;


    /**
     * @ORM\OneToMany(
     *      targetEntity="Post",
     *      mappedBy="category",
     *      orphanRemoval=true
     * )
     */
    private $posts;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPosts()
    {
        return $this->posts;
    }


    /**
     * @param Post $post
     */
    public function addPost(Post $post)
    {
        $this->posts->add($post);
        $post->setCategory($this);
    }

    /**
     * @param Post $post
     */
    public function removeComment(Post $post)
    {
        $this->posts->removeElement($post);
        $post->setCategory(null);
    }
}