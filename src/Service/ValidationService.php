<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validate(object $dto): ?JsonResponse
    {
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return new JsonResponse([
                'error' => 'Validation failed',
                'details' => $errorMessages
            ], 400);
        }

        return null;
    }

    public function mapRequestToDTO(array $data, string $dtoClass): object
    {
        $dto = new $dtoClass();

        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        return $dto;
    }
}
