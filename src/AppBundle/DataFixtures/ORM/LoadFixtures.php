<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// @codingStandardsIgnoreFile
namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Category;
use AppBundle\Entity\User;
use AppBundle\Entity\Post;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Vote;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests. Execute this command to load the data:
 *
 *   $ php app/console doctrine:fixtures:load
 *
 * See http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class LoadFixtures implements FixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadPosts($manager);
    }

    private function loadUsers(ObjectManager $manager)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');

        $johnUser = new User();
        $johnUser->setUsername('dkiprushev');
        $johnUser->setDisplayName('Denis Kiprushev');
        $johnUser->setEmail('d.kiprushev@levi9.com');
        $encodedPassword = $passwordEncoder->encodePassword($johnUser, 'kitten');
        $johnUser->setPassword($encodedPassword);
        $johnUser->setUuid('0B353BD-A89E-475E-922E-FG26FC542824');
        $manager->persist($johnUser);

        $annaAdmin = new User();
        $annaAdmin->setUsername('Alex Martynenko');
        $annaAdmin->setDisplayName('Alex Martynenko');
        $annaAdmin->setEmail('a.martynenko@levi9.com');
        $annaAdmin->setRoles(array('ROLE_ADMIN'));
        $encodedPassword = $passwordEncoder->encodePassword($annaAdmin, 'kitten');
        $annaAdmin->setPassword($encodedPassword);
        $annaAdmin->setUuid('0B353BD-A89E-475E-922E-FG26FC542825');
        $manager->persist($annaAdmin);

        $manager->flush();
    }

    private function loadPosts(ObjectManager $manager)
    {
        $category = new Category();
        $category->setName('Improvements');

        $passwordEncoder = $this->container->get('security.password_encoder');

        $user = new User();
        $user->setUsername('vvasia');
        $user->setDisplayName('Vasia Vasin');
        $user->setEmail('v.vasin@levi9.com');
        $user->setUuid('uuid');
        $encodedPassword = $passwordEncoder->encodePassword($user, 'password');
        $user->setPassword($encodedPassword);
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);
        $manager->flush();

        foreach (range(1, 5) as $i) {
            $post = new Post();

            $post->setTitle($this->getRandomPostTitle());
            $post->setSummary($this->getRandomPostSummary());
            $post->setSlug($this->container->get('slugger')->slugify($post->getTitle()));
            $post->setContent($this->getPostContent());
            $post->setAuthorEmail('a.martynenko@levi9.com');
            $post->setPublishedAt(new \DateTime('now - '.$i.'days'));
            $post->setState($this->getRandomState());
            $post->setCategory($category);

            foreach (range(1, 5) as $j) {
                $comment = new Comment();
                $comment->setUser($user)
                    ->setPublishedAt(new \DateTime('now + '.($i + $j).'seconds'))
                    ->setContent($this->getRandomCommentContent())
                    ->setPost($post);

                $manager->persist($comment);
                $post->addComment($comment);
            }

            if (rand(0, 1)) {
                $vote = new Vote();
                $vote->setAuthorEmail(rand(0, 1) ? 'a.martynenko@levi9.com' : 'd.kiprushev@levi9.com');
                $vote->setPost($post);
                $vote->setVote(rand(0 ,1));
            }

            $manager->persist($post);
            $category->addPost($post);
        }

        $manager->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    private function getPostContent()
    {
        return <<<MARKDOWN
Всем привет!
Есть предложение покататься на байдарках в выходные.
Ночевка в палатке, комары и прочие радости отсутствия цивилизации!
Компания оплачивает услуги агенства, куда входят: дорога, питание, аренда байдарок, лодок и т.д.
От нас: желание отлично провести время не покалечившись.

![Image of Yaktocat](https://www.levi9.com/wp-content/themes/levi9/imgs/logo.png)

Просьба проголосовать за дату, которая бы подошла вам. Если обе даты вам подходят – ставьте крестик под обеими датами, соответственно.

![Image of Yaktocat](http://wildtraveler.com.ua/trash/statica/538/IMG_0528-1.jpg)

[Google form](https://docs.google.com/spreadsheets/d/19i2BAoHeGYNM9JczxDcXSYGYhBa7NiPfzmwcYy8p3A4/edit#gid=0)

Для того чтобы вы не путались в своих собственных отгулах, мы добавили запрет на создание пересекающихся реквестов. Мы надеемся что это окончательно упростит вашу работу с системой. Также внесли ясность в  использование дней категории «Personal events». Как вы уже знаете, у каждого из сотрудников есть три дополнительных дня отгула для важных событий в вашей жизни, такие как:  **Wedding/Child Birth/Death related**. Что значит, что вы можете использовать по одному дню каждого типа.
В системе они отображаются соответствующим образом: 1/1/1 .

Если есть вопросы – обращайтесь ко мне или Жене Черне.

MARKDOWN;
    }

    private function getPhrases()
    {
        return array(
            'Senior PHP Developer at Levi9: Diving into Dependency Injection',
            'Department Manager, PHP Architect at Levi9: Microservices',
            'Today is SystemAdmin day, We celebrate it with the best admins ever',
            'Knowledge sharing session is on at @levi9_rs.',
            'Jasmina Petrov is talking about the automation of mobile apps testing',
            'PHP developers, join to Levi9 team, send your CV on jobs-serbia@levi9.com ',
            'Arch9 Event by Levi9 22 april',
            'Levi9 Ukraine is the employer for specialists at all career levels',
            'Working at Levi9 is much more than a job',
            'You will be a part of a dynamic, qualified and motivated European team with a unique corporate spirit',
            'Want to know more about Levi9? You are welcome to contact our HR Team',
            'Levi9 IT services works with worldwide clients well-known clients and projects as well as start-ups',
            'Levi9 Kiev office is looking for Junior PHP Developer'
        );
    }

    private function getRandomPostTitle()
    {
        $titles = $this->getPhrases();

        return $titles[array_rand($titles)];
    }


    private function getSummaries()
    {
        return [
            'Всем привет!
            Есть предложение покататься на байдарках в выходные.
            Ночевка в палатке, комары и прочие радости отсутствия цивилизации!
            Компания оплачивает услуги агенства, куда входят: дорога, питание, аренда байдарок, лодок и т.д.
            От нас: желание отлично провести время не покалечившись.',
        'Давненько у нас не было повода что-нибудь хорошо отпраздновать!  Итак, 1 августа,в субботу мы отмечаем 10 - летие компании на одном из киевских пляжей.
Будем есть мясо (но не я), пить шампанское и поздравлять тех, кто сделал невозможное в IT мире и проработал в нашей компании более пяти и десяти лет!',
            'Всем привет!
Небольшое обновление по работе системы timeoff-ua.levi9.com/:
Для того чтобы вы не путались в своих собственных отгулах, мы добавили запрет на создание пересекающихся реквестов. Мы надеемся что это окончательно упростит вашу работу с системой. Также внесли ясность в  использование дней категории «Personal events». Как вы уже знаете, у каждого из сотрудников есть три дополнительных дня отгула для важных событий в вашей жизни, такие как:  Wedding/ Child Birth/Death related. Что значит, что вы можете использовать по одному дню каждого типа.
В системе они отображаются соответствующим образом: 1/1/1 .

Если есть вопросы – обращайтесь ко мне или Жене Черне.'

        ];
    }

    private function getRandomPostSummary()
    {
        $summaries = $this->getSummaries();

        $num = rand(0, 2);

        return $summaries[$num];
    }

    private function getRandomCommentContent()
    {
        $phrases = $this->getPhrases();

        $numPhrases = rand(2, 15);
        shuffle($phrases);

        return implode(' ', array_slice($phrases, 0, $numPhrases-1));
    }

    private function getRandomState()
    {
        return rand(Post::STATUS_DRAFT, Post::STATUS_REJECTED);
    }
}
