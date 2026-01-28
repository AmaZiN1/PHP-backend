<?php

namespace App\Controller;

use App\Attribute\RequireAuth;
use App\Entity\AuditLog;
use App\Entity\Domain;
use App\Entity\Mailbox;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\EntityMapperService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/audit-logs')]
class AuditLogController
{
    private const PAGE_SIZE = 50;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EntityMapperService $mapper
    ) {
    }

    #[Route('', name: 'audit_logs_list', methods: ['GET'])]
    #[RequireAuth]
    public function list(Request $request): JsonResponse
    {
        $authEntity = $request->attributes->get('auth_user');

        if ($authEntity instanceof Mailbox) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $user = $authEntity;

        $page = max(1, (int) $request->query->get('page', 1));
        $domainId = $request->query->get('domain_id');

        if ($user->getRole() === UserRole::ADMINISTRATOR) {
            return $this->getAdminLogs($page);
        }

        if (!$domainId) {
            return new JsonResponse([
                'error' => 'Parameter "domain_id" is required for non-admin users'
            ], 400);
        }

        $domain = $this->entityManager->getRepository(Domain::class)->find((int)$domainId);
        if (!$domain) {
            return new JsonResponse(['error' => 'Domain not found'], 404);
        }

        if (!$user->getDomains()->contains($domain)) {
            return new JsonResponse(['error' => 'Access denied to this domain'], 403);
        }

        return $this->getDomainLogs($domain, $page);
    }

    private function getAdminLogs(int $page): JsonResponse
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(AuditLog::class, 'a')
            ->orderBy('a.created_at', 'DESC')
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE);

        $query = $qb->getQuery();
        $paginator = new Paginator($query);

        $total = count($paginator);
        $totalPages = (int) ceil($total / self::PAGE_SIZE);

        $logs = array_map(
            fn($log) => $this->mapper->mapAuditLog($log),
            iterator_to_array($paginator)
        );

        return new JsonResponse([
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'page_size' => self::PAGE_SIZE,
                'total_items' => $total,
                'total_pages' => $totalPages
            ]
        ]);
    }

    private function getDomainLogs(Domain $domain, int $page): JsonResponse
    {
        $domainId = $domain->getId();

        $aliasIds = $this->entityManager->createQueryBuilder()
            ->select('a.id')
            ->from(\App\Entity\Alias::class, 'a')
            ->where('a.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleColumnResult();

        $mailboxIds = $this->entityManager->createQueryBuilder()
            ->select('m.id')
            ->from(Mailbox::class, 'm')
            ->where('m.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleColumnResult();

        $userIds = $this->entityManager->createQueryBuilder()
            ->select('u.id')
            ->from(User::class, 'u')
            ->join('u.domains', 'd')
            ->where('d.id = :domainId')
            ->setParameter('domainId', $domainId)
            ->getQuery()
            ->getSingleColumnResult();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(AuditLog::class, 'a')
            ->where($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.entity_type', ':domainType'),
                    $qb->expr()->eq('a.entity_id', ':domainId')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('a.entity_type', ':aliasType'),
                    $qb->expr()->in('a.entity_id', ':aliasIds')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('a.entity_type', ':mailboxType'),
                    $qb->expr()->in('a.entity_id', ':mailboxIds')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('a.entity_type', ':userDomainType'),
                    $qb->expr()->eq('a.entity_id', ':domainId')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('a.entity_type', ':userType'),
                    $qb->expr()->in('a.entity_id', ':userIds')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('a.entity_type', ':autoresponderType'),
                    $qb->expr()->in('a.entity_id', ':mailboxIds')
                )
            ))
            ->orderBy('a.created_at', 'DESC')
            ->setParameter('domainType', 'domain')
            ->setParameter('domainId', $domainId)
            ->setParameter('aliasType', 'alias')
            ->setParameter('aliasIds', $aliasIds ?: [0])
            ->setParameter('mailboxType', 'mailbox')
            ->setParameter('mailboxIds', $mailboxIds ?: [0])
            ->setParameter('userDomainType', 'user_domain')
            ->setParameter('userType', 'user')
            ->setParameter('userIds', $userIds ?: [0])
            ->setParameter('autoresponderType', 'autoresponder')
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE);

        $query = $qb->getQuery();
        $paginator = new Paginator($query);

        $total = count($paginator);
        $totalPages = (int) ceil($total / self::PAGE_SIZE);

        $logs = array_map(
            fn($log) => $this->mapper->mapAuditLog($log),
            iterator_to_array($paginator)
        );

        return new JsonResponse([
            'domain_id' => $domainId,
            'domain_name' => $domain->getName(),
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'page_size' => self::PAGE_SIZE,
                'total_items' => $total,
                'total_pages' => $totalPages
            ]
        ]);
    }
}
