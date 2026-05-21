<?php

namespace App\OpenApi\Api;

use App\Application\User\UserApplicationService;
use App\Domain\User\DTO\RegisterUserDTO;
use App\Domain\User\DTO\UpdateProfileDTO;
use App\OpenApi\Model\RestapiPhpActionUpdateProfilePutRequest;
use App\OpenApi\Model\RestapiPhpActionWriteUserPostRequest;
use App\OpenApi\Model\ApiUsersRegisterPostRequest;
use App\Shared\Exception\DomainException;

/**
 * UserApi Implementation
 *
 * Implementiert die Geschäftslogik für alle User-Endpoints
 */
class UserApi implements UserApiInterface
{
    public function __construct(
        private UserApplicationService $userApplicationService
    ) {}

    /**
     * Neuen Benutzer registrieren (RESTful API)
     */
    public function apiUsersRegisterPost(
        ApiUsersRegisterPostRequest $apiUsersRegisterPostRequest,
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null
    {
        try {
            $dto = RegisterUserDTO::fromRequest([
                'email' => $apiUsersRegisterPostRequest->getEmail(),
                'passwort' => $apiUsersRegisterPostRequest->getPasswort(),
                'name' => $apiUsersRegisterPostRequest->getName(),
                'vorname' => $apiUsersRegisterPostRequest->getVorname(),
                'klassenname' => $apiUsersRegisterPostRequest->getKlassenname()
            ]);

            $result = $this->userApplicationService->registerUser($dto);
            $responseCode = 200;
            return $result;
        } catch (DomainException $e) {
            $responseCode = 400;
            return [
                'benutzerAngelegt' => false,
                'grund' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $responseCode = 500;
            return [
                'benutzerAngelegt' => false,
                'grund' => $e->getMessage()
            ];
        }
    }

    // ...existing code...
    public function restapiPhpactiongetUserGet(
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null
    {
        try {
            $data = $this->userApplicationService->getUser();
            $responseCode = 200;
            return $data;
        } catch (DomainException $e) {
            $responseCode = 401;
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            $responseCode = 500;
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Profil des Benutzers abrufen
     */
    public function restapiPhpactionprofileGet(
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null
    {
        try {
            $data = $this->userApplicationService->getProfile();
            $responseCode = 200;
            return $data;
        } catch (DomainException $e) {
            $responseCode = 401;
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            $responseCode = 500;
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Benutzer-Profil aktualisieren
     */
    public function restapiPhpactionupdateProfilePut(
        RestapiPhpActionUpdateProfilePutRequest $restapiPhpActionUpdateProfilePutRequest,
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null
    {
        try {
            $dto = UpdateProfileDTO::fromRequest([
                'name' => $restapiPhpActionUpdateProfilePutRequest->getName(),
                'vorname' => $restapiPhpActionUpdateProfilePutRequest->getVorname(),
                'email' => $restapiPhpActionUpdateProfilePutRequest->getEmail(),
                'passwort' => $restapiPhpActionUpdateProfilePutRequest->getPasswort(),
                'passwort_confirm' => $restapiPhpActionUpdateProfilePutRequest->getPasswortConfirm(),
                'klassen_id' => $restapiPhpActionUpdateProfilePutRequest->getKlassenId()
            ]);

            $data = $this->userApplicationService->updateProfile($dto);
            $responseCode = 200;
            return $data;
        } catch (DomainException $e) {
            $responseCode = 400;
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            $responseCode = 500;
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Neuen Benutzer registrieren (alte Query-API)
     */
    public function restapiPhpactionwriteUserPost(
        ApiUsersRegisterPostRequest $apiUsersRegisterPostRequest,
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null
    {
        // Delegiere zur Registriermethode
        return $this->apiUsersRegisterPost($apiUsersRegisterPostRequest, $responseCode, $responseHeaders);
    }
}

