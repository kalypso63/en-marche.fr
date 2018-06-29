<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\AdherentChangeEmailToken;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AdherentChangeEmailTokenRepository extends AbstractAdherentTokenRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AdherentChangeEmailToken::class);
    }

    public function findLastUnusedByAdherent(Adherent $adherent): ?AdherentChangeEmailToken
    {
        return $this
            ->createQueryBuilder('token')
            ->where('token.adherentUuid = :uuid')
            ->andWhere('token.usedAt IS NULL')
            ->andWhere('token.expiredAt >= :date')
            ->setParameters([
                'uuid' => $adherent->getUuidAsString(),
                'date' => new \DateTime(),
            ])
            ->orderBy('token.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function invalidateOtherActiveToken(Adherent $adherent, AdherentChangeEmailToken $token): void
    {
        $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->update(AdherentChangeEmailToken::class, 'token')
            ->set('token.expiredAt', ':date')
            ->where('token.adherentUuid = :uuid AND token.usedAt IS NULL AND token.id != :last_token')
            ->getQuery()
            ->execute([
                'date' => new \DateTime('-1 second'),
                'uuid' => $adherent->getUuidAsString(),
                'last_token' => $token->getId(),
            ])
        ;
    }

    public function findOneUnusedByEmail(string $emailAddress): ?AdherentChangeEmailToken
    {
        return $this
            ->createQueryBuilder('token')
            ->where('token.email = :email')
            ->andWhere('token.usedAt IS NULL')
            ->andWhere('token.expiredAt >= :date')
            ->setParameters([
                'email' => $emailAddress,
                'date' => new \DateTime(),
            ])
            ->orderBy('token.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
