<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\VerificationToken;
use App\Repository\VerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VerificationTokenRepository $verificationTokenRepository,
        private MailerInterface $mailer,
        private string $emailFromAddress = 'noreply@khriciadivino.com',
        private string $appBaseUrl = 'http://localhost:8000',
    ) {}

    public function createAndSendVerificationEmail(User $user): void
    {
        // Generate a new verification token
        $token = new VerificationToken();
        $token->setToken(VerificationToken::generateToken());
        $token->setUser($user);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        // Send verification email
        $verificationUrl = sprintf(
            '%s/verify-email?token=%s',
            $this->appBaseUrl,
            urlencode($token->getToken())
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->emailFromAddress, 'Khriciadivino'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verification_url' => $verificationUrl,
                'token' => $token->getToken(),
            ]);

        $this->mailer->send($email);
    }

    public function generateVerificationToken(): string
    {
        return VerificationToken::generateToken();
    }

    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->emailFromAddress, 'Khriciadivino'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verification_url' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    public function verifyEmail(string $token): ?User
    {
        $verificationToken = $this->verificationTokenRepository->findByToken($token);

        if (!$verificationToken || !$verificationToken->isValid()) {
            return null;
        }

        $user = $verificationToken->getUser();

        // Mark user as verified
        $user->setVerifiedAt(new \DateTime());

        // Mark token as used
        $verificationToken->setUsedAt(new \DateTime());

        $this->entityManager->flush();

        return $user;
    }

    public function verifyToken(string $token): ?User
    {
        return $this->verifyEmail($token);
    }

    public function isEmailVerified(User $user): bool
    {
        return $user->isVerified();
    }

    public function generateAndGetVerificationUrl(User $user): string
    {
        // Check if user has a valid token already
        $existingToken = $this->verificationTokenRepository->findValidTokenByUser($user);

        if (!$existingToken) {
            $token = new VerificationToken();
            $token->setToken(VerificationToken::generateToken());
            $token->setUser($user);
            $this->entityManager->persist($token);
            $this->entityManager->flush();
        } else {
            $token = $existingToken;
        }

        return sprintf(
            '%s/verify-email?token=%s',
            $this->appBaseUrl,
            urlencode($token->getToken())
        );
    }

    public function resendVerificationEmail(User $user): void
    {
        // If user is already verified, don't send
        if ($user->isVerified()) {
            return;
        }

        // Delete old tokens and create new one
        $existingTokens = $this->entityManager
            ->getRepository(VerificationToken::class)
            ->findBy(['user' => $user, 'usedAt' => null]);

        foreach ($existingTokens as $existingToken) {
            $this->entityManager->remove($existingToken);
        }
        $this->entityManager->flush();

        // Send new verification email
        $this->createAndSendVerificationEmail($user);
    }
}
