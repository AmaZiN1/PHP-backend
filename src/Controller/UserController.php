<?php

namespace App\Controller;

use App\Attribute\RequireRole;
use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\Entity\Token;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\AuditLogService;
use App\Service\EntityMapperService;
use App\Service\PasswordHasher;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users', name: 'users_')]
class UserController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogService $auditLogService,
        private EntityMapperService $mapper,
        private ValidationService $validationService,
        private PasswordHasher $passwordHasher
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function list(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        $usersData = array_map(
            fn(User $user) => $this->mapper->mapUser($user),
            $users
        );

        return new JsonResponse([
            'users' => $usersData,
            'total' => count($usersData)
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function show(#[MapEntity] User $user): JsonResponse
    {
        return new JsonResponse([
            'user' => $this->mapper->mapUser($user)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, CreateUserDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $dto->email]);

        if ($existingUser) {
            return new JsonResponse(['error' => 'User with this email already exists'], 409);
        }

        $role = UserRole::from($dto->role);

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hash($dto->password));
        $user->setFirstname($dto->firstname);
        $user->setLastname($dto->lastname);
        $user->setRole($role);
        $user->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $this->auditLogService->log(
            eventType: 'user.created',
            entityType: 'user',
            entityId: $user->getId(),
            actor: $authUser,
            request: $request,
            newValue: [
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'role' => $user->getRole()->value,
                'active' => $user->isActive()
            ],
            status: 'success'
        );

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => $this->mapper->mapUser($user)
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function update(
        #[MapEntity] User $user,
        Request $request
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = $this->validationService->mapRequestToDTO($data, UpdateUserDTO::class);

        if ($error = $this->validationService->validate($dto)) {
            return $error;
        }

        $oldValues = [
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'active' => $user->isActive(),
            'role' => $user->getRole()->value
        ];
        $oldActive = $user->isActive();

        $hasChanges = false;

        if ($dto->email !== null) {
            $existingUser = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $dto->email]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Email is already taken'], 409);
            }

            $user->setEmail($dto->email);
            $hasChanges = true;
        }

        if ($dto->firstname !== null) {
            $user->setFirstname($dto->firstname);
            $hasChanges = true;
        }

        if ($dto->lastname !== null) {
            $user->setLastname($dto->lastname);
            $hasChanges = true;
        }

        if ($dto->role !== null) {
            $user->setRole(UserRole::from($dto->role));
            $hasChanges = true;
        }

        if ($dto->active !== null) {
            $user->setActive($dto->active);
            $hasChanges = true;
        }

        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');

        if ($hasChanges) {
            $newValues = [
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'active' => $user->isActive(),
                'role' => $user->getRole()->value
            ];

            $this->auditLogService->log(
                eventType: 'user.updated',
                entityType: 'user',
                entityId: $user->getId(),
                actor: $authUser,
                request: $request,
                oldValue: $oldValues,
                newValue: $newValues,
                status: 'success'
            );

            if ($oldActive !== $user->isActive()) {
                $eventType = $user->isActive() ? 'user.activated' : 'user.deactivated';
                $this->auditLogService->log(
                    eventType: $eventType,
                    entityType: 'user',
                    entityId: $user->getId(),
                    actor: $authUser,
                    request: $request,
                    oldValue: ['active' => $oldActive],
                    newValue: ['active' => $user->isActive()],
                    status: 'success'
                );
            }
        }

        return new JsonResponse([
            'message' => 'User updated successfully',
            'user' => $this->mapper->mapUser($user)
        ]);
    }

    #[Route('/{id}/password', name: 'change_password', methods: ['PUT'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function changePassword(
        #[MapEntity] User $user,
        Request $request
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['password']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Password is required'], 400);
        }

        $user->setPassword($this->passwordHasher->hash($data['password']));
        $this->entityManager->flush();

        $authUser = $request->attributes->get('auth_user');
        $this->auditLogService->log(
            eventType: 'user.password_changed_by_admin',
            entityType: 'user',
            entityId: $user->getId(),
            actor: $authUser,
            request: $request,
            newValue: ['password_changed' => true],
            status: 'success'
        );

        return new JsonResponse(['message' => 'Password changed successfully']);
    }

    #[Route('/{id}/logout-all', name: 'logout_all', methods: ['POST'])]
    #[RequireRole(UserRole::ADMINISTRATOR)]
    public function logoutAll(#[MapEntity] User $user): JsonResponse
    {
        $tokenRepository = $this->entityManager->getRepository(Token::class);
        $tokens = $tokenRepository->findBy(['user' => $user]);

        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'All user sessions logged out successfully',
            'sessions_removed' => count($tokens)
        ]);
    }
}
