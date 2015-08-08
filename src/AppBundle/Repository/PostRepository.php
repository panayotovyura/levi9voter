<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\Post;
use Doctrine\ORM\Query\Expr\Join;

/**
 * This custom Doctrine repository contains some methods which are useful when
 * querying for blog post information.
 * See http://symfony.com/doc/current/book/doctrine.html#custom-repository-classes
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class PostRepository extends EntityRepository
{
    public function findLatest($limit = Post::NUM_ITEMS, $state = null, $category = null)
    {
        $builder =  $this
            ->createQueryBuilder('p')
            ->select('p')
            ->where('p.publishedAt <= :now')->setParameter('now', new \DateTime())
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit);

        if (null !== $state) {
            $builder->andWhere('p.state = :state')
                ->setParameter('state', intval($state));
        }

        if (null !== $category) {
            $builder->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        return $builder
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByVoting($limit = Post::NUM_ITEMS, $type)
    {
        if ($type == Post::VOTING_MOST_RATED) {
            $postsCollection =  $this->findMostRated($limit);
        } else {
            $postsCollection = $this->findMostPopular($limit);
        }

        $formattedOutput = [];

        foreach($postsCollection as $postItem) {
            $post = reset($postItem);
            $formattedOutput[] = $post;
        }

        return $formattedOutput;
    }


    protected function findMostRated($limit)
    {
        $builder = $this
            ->createQueryBuilder('p')
            ->select('p')
            ->addSelect('SUM(v.vote) as votesRate')
            ->leftJoin('AppBundle:Vote', 'v', Join::WITH, 'v.post = p.id')
            ->orderBy('votesRate', 'DESC')
            ->groupBy('p.id')
            ->setMaxResults($limit);

        return $builder
            ->getQuery()
            ->getResult();
    }

    protected function findMostPopular($limit)
    {
        $builder = $this
            ->createQueryBuilder('p')
            ->select('p')
            ->addSelect('COUNT(v.vote) as votesCount')
            ->leftJoin('AppBundle:Vote', 'v', Join::WITH, 'v.post = p.id')
            ->orderBy('votesCount', 'DESC')
            ->groupBy('p.id')
            ->setMaxResults($limit);

        return $builder
            ->getQuery()
            ->getResult();
    }
}
