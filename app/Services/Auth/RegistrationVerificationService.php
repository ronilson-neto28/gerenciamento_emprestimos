<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;

class RegistrationVerificationService
{
    public function issue(User $user): void
    {
        $code = $this->generateCode();

        $user->forceFill([
            'verification_code' => $code,
            'verification_code_expires_at' => Carbon::now()->addMinutes(15),
        ])->save();

        $this->sendEmail($user, $code);
    }

    public function verify(User $user, string $code): bool
    {
        $expectedCode = trim((string) ($user->verification_code ?? ''));
        $expiresAt = $user->verification_code_expires_at;

        if ($expectedCode === '' || $expectedCode !== trim($code)) {
            return false;
        }

        if ($expiresAt instanceof Carbon && $expiresAt->isPast()) {
            return false;
        }

        $ownerId = trim((string) ($user->owner_id ?: $user->id ?: $user->getKey() ?: ''));

        $user->forceFill([
            'status' => 'ativo',
            'owner_id' => $ownerId,
            'email_verified_at' => Carbon::now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        return true;
    }

    public function canResend(User $user): bool
    {
        return $user->isPending();
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendEmail(User $user, string $code): void
    {
        $apiKey = trim((string) config('services.resend.key'));

        if ($apiKey === '') {
            throw new RuntimeException('RESEND_API_KEY não configurada.');
        }

        try {
            $resend = \Resend::client($apiKey);

            $resend->emails->send([
                'from' => 'onboarding@resend.dev',
                'to' => [$user->email],
                'subject' => 'Código de Verificação - Sistema de Empréstimos',
                'html' => '<p>Seu código de ativação é: <strong>' . e($code) . '</strong></p>',
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('Não foi possível enviar o código de verificação por e-mail.', previous: $e);
        }
    }
}
