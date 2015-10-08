<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Enum\FlashbagTypeEnum;
use AppBundle\Entity\Post;
use AppBundle\Form\CommentType;
use AppBundle\Form\StateType;
use AppBundle\Security\Authorization\Voter\PostVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Intl;
use AppBundle\Entity\Vote;
use AppBundle\Entity\Category;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/blog")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends Controller
{
    /**
     * @Route("/", name="blog_index")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository('AppBundle:Post')->findLatest();
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        return $this->render('blog/index.html.twig', array(
            'posts' => $posts,
            'categories' => $categories,
        ));
    }

    /**
     * @Route("/state/{state}", name="blog_by_state")
     */
    public function byStateAction($state)
    {
        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository('AppBundle:Post')->findLatest(Post::NUM_ITEMS, $state);
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        return $this->render('blog/index.html.twig', array(
            'posts' => $posts,
            'categories' => $categories,
        ));
    }

    /**
     * @Route("/category/{id}", name="blog_by_category")
     */
    public function byCategoryAction(Category $category)
    {
        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository('AppBundle:Post')->findLatest(Post::NUM_ITEMS, null, $category);
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        return $this->render('blog/index.html.twig', array(
            'posts' => $posts,
            'categories' => $categories,
        ));
    }


    /**
     * @Route("/voting/{type}", name="blog_by_voting")
     */
    public function byVotingAction($type)
    {
        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository('AppBundle:Post')->findByVoting($type, Post::NUM_ITEMS);

        $categories = $em->getRepository('AppBundle:Category')->findAll();
        return $this->render('blog/index.html.twig', array(
            'posts' => $posts,
            'categories' => $categories,
        ));
    }

    /**
     * @Route("/posts/{slug}", name="blog_post")
     *
     * NOTE: The $post controller argument is automatically injected by Symfony
     * after performing a database query looking for a Post with the 'slug'
     * value given in the route.
     * See http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
     */
    public function postShowAction(Post $post)
    {
        $this->denyAccessUnlessGranted(PostVoter::VIEW, $post);

        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        return $this->render('blog/post_show.html.twig', array(
            'post' => $post,
            'categories' => $categories,
        ));
    }

    /**
     * @Route("/comment/{postSlug}/new", name = "comment_new")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @Method("POST")
     * @ParamConverter("post", options={"mapping": {"postSlug": "slug"}})
     */
    public function commentNewAction(Request $request, Post $post)
    {
        $form = $this->createCommentForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // todo: we need to have user from database in session
            $user = $this->getDoctrine()
                ->getRepository('AppBundle:User')
                ->findOneByEmail($this->getUser()->getEmail());

            /** @var Comment $comment */
            $comment = $form->getData();
            $comment->setUser($user)
                ->setPost($post)
                ->setPublishedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('blog_post', array('slug' => $post->getSlug()));
        }

        return $this->render('blog/comment_form_error.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    /**
     * This controller is called directly via the render() function in the
     * blog/post_show.html.twig template. That's why it's not needed to define
     * a route name for it.
     *
     * The "id" of the Post is passed in and then turned into a Post object
     * automatically by the ParamConverter.
     *
     * @param Post $post
     *
     * @return Response
     */
    public function commentFormAction(Post $post)
    {
        $form = $this->createCommentForm();

        return $this->render('blog/comment_form.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/post/{postSlug}/state", name = "change_state")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     * @Method("POST")
     * @ParamConverter("post", options={"mapping": {"postSlug": "slug"}})
     */
    public function changeStateAction(Request $request, Post $post)
    {
        $this->denyAccessUnlessGranted(PostVoter::EDIT, $post);

        $form = $this->createStateForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $post->setState($data['state']);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('blog_post', array('slug' => $post->getSlug()));
        }

        return $this->render('blog/comment_form_error.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    public function stateFormAction(Post $post)
    {
        $form = $this->createStateForm();

        return $this->render('blog/state_form.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }



    /**
     * @Route("/posts/vote/{id}/{agree}", name="blog_post_vote")
     */
    public function voteAction(Post $post, $agree)
    {
        $this->denyAccessUnlessGranted(PostVoter::VIEW, $post);

        $userEmail = $this->getUser()->getEmail();

        $voteEntity = $this->getDoctrine()
            ->getRepository('AppBundle:Vote')
            ->findOneBy([
                'authorEmail' => $userEmail,
                'post' => $post,
            ]);

        if (!$voteEntity) {
            $voteEntity = new Vote();
        }

        $voteEntity->setAuthorEmail($userEmail)
            ->setPost($post)
            ->setVote($agree);

        $em = $this->getDoctrine()->getManager();
        $em->persist($voteEntity);
        $em->flush();

        $flashMessage = $agree == Vote::LIKE ? 'flash.vote.agree' : 'flash.vote.not-agree';
        $this->addFlash(FlashbagTypeEnum::SUCCESS, $this->get('translator')->trans($flashMessage));

        return $this->redirectToRoute('blog_post', array('slug' => $post->getSlug()));
    }

    /**
     * This is a utility method used to create comment forms. It's recommended
     * to not define this kind of methods in a controller class, but sometimes
     * is convenient for defining small methods.
     */
    private function createCommentForm()
    {
        $form = $this->createForm(new CommentType());
        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    private function createStateForm()
    {
        $form = $this->createForm(new StateType());
        $form->add('submit', 'submit', array('label' => 'Change State'));

        return $form;
    }
}
