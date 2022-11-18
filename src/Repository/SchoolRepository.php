<?php

namespace App\Repository;

use App\Entity\School;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends ServiceEntityRepository<School>
 *
 * @method School|null find($id, $lockMode = null, $lockVersion = null)
 * @method School|null findOneBy(array $criteria, array $orderBy = null)
 * @method School[]    findAll()
 * @method School[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, School::class);
    }

    public function save(School $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(School $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllSchools(bool $status = true): array {
        return $this->withStatus($status, $this->createQueryBuilder('s'))->getQuery()->getResult();
    }

    public function findBetweenDates(DateTimeImmutable $startDate, DateTimeImmutable $endDate, int $page, int $limit) {
        $startDate = $startDate ? $startDate : new DateTimeImmutable();
        $qb = $this->createQueryBuilder("s");
        $qb->add(
            'where',
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->gte("s.dateStart", ":startdate"),
                    $qb->expr()->lte("s.dateStart", ":enddate")
                ),
                $qb->expr()->andX(
                    $qb->expr()->gte("s.dateEnd", ":startdate"),
                    $qb->expr()->lte("s.dateEnd", ":enddate")
                )
            )
        )->setParameters(
            new ArrayCollection(
                [
                    new Parameter('startdate', $startDate, Types::DATETIME_IMMUTABLE),
                    new Parameter('enddate', $endDate, Types::DATETIME_IMMUTABLE)
                ]
            )
        );
    }

//    /**
//     * @return School[] Returns an array of School objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?School
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
