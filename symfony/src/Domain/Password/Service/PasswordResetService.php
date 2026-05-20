<?php

namespace App\Domain\Password\Service;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Password\DTO\RequestPasswordResetDTO;
use App\Domain\Password\DTO\ResetPasswordDTO;
use App\Shared\Exception\EntityNotFoundException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordResetService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer
    ) {}

    /**
     * Fordert einen Passwort-Reset an
     */
    public function requestPasswordReset(RequestPasswordResetDTO $dto): array
    {
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::missingField('Ungültige E-Mail-Adresse');
        }

        $user = $this->userRepository->findByEmail($dto->email);
        if (!$user) {
            // Sicherheit: Nicht verraten, ob Email existiert
            return ['success' => 'Link wurde gesendet'];
        }

        // Generiere Token
        $token = bin2hex(random_bytes(32));
        $expires = new \DateTime('+1 hour');

        // Speichere Token
        $user->setResetToken($token, $expires);
        $this->userRepository->update($user);

        // Sende E-Mail
        $this->sendResetEmail($dto->email, $token);

        return ['success' => 'Link wurde gesendet'];
    }

    /**
     * Setzt das Passwort zurück
     */
    public function resetPassword(ResetPasswordDTO $dto): array
    {
        if (empty($dto->token) || empty($dto->password)) {
            throw ValidationException::missingField('Token und Passwort sind erforderlich');
        }

        // Finde Benutzer mit Token
        $user = null;
        foreach ([$this->userRepository] as $repo) {
            // Da wir kein direktes Verfahren haben, müssen wir hier implementieren
            // Dies ist nur ein Beispiel - die konkrete Implementierung hängt vom Repository ab
        }

        // TODO: Implementiere Suche nach Token in Repository
        // Für jetzt: Exception
        throw new \Exception('Token-basierte Suche muss im Repository implementiert werden', 500);
    }

    /**
     * Sendet eine Reset-E-Mail
     */
    private function sendResetEmail(string $email, string $token): void
    {
        $resetLink = "https://your-domain.de/reset-password.html?token=" . $token;

        $emailMessage = (new Email())
            ->from('noreply@your-domain.de')
            ->to($email)
            ->subject('Passwort zurücksetzen')
            ->html("
                <p>Klicken Sie auf den Link unten, um Ihr Passwort zurückzusetzen:</p>
                <a href=\"{$resetLink}\">{$resetLink}</a>
                <p>Dieser Link ist 1 Stunde lang gültig.</p>
            ");

        try {
            $this->mailer->send($emailMessage);
        } catch (\Exception $e) {
            // Logging
            throw new \Exception('E-Mail konnte nicht gesendet werden', 500);
        }
    }
}

