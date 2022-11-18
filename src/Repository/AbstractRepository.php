<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractRepository extends ServiceEntityRepository
{
    public function withStatus(bool $status, $qb)
    {
        if(!$qb) {
            $qb = new ORMQueryBuilder($this->getEntityManager());
        }
        return $qb->andWhere("s.status = :status")->setParameter('status', $status);
    }
}