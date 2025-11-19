<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/api/password/forgot', name: 'password_forgot', methods: ['POST'])]
    public function forgot(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'])) {
            return new JsonResponse(['message' => 'Email is required'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $token = bin2hex(random_bytes(32));

        $resetToken = new PasswordResetToken();
        $resetToken->setUser($user);
        $resetToken->setToken($token);
        $resetToken->setExpiresAt(new \DateTimeImmutable('+1 hour'));

        $em->persist($resetToken);
        $em->flush();

        // Send email
        $emailMessage = (new Email())
            ->from('noreply@wemotions.com')
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->text("Use this token to reset password: $token");

        $mailer->send($emailMessage);

        return new JsonResponse(['message' => 'Reset password email sent']);
    }

    #[Route('/api/password/reset', name: 'password_reset', methods: ['POST'])]
    public function reset(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || !isset($data['newPassword'])) {
            return new JsonResponse(['message' => 'Token and new password are required'], 400);
        }

        $repo = $em->getRepository(PasswordResetToken::class);
        $resetToken = $repo->findOneBy(['token' => $data['token']]);

        if (!$resetToken || $resetToken->isExpired()) {
            return new JsonResponse(['message' => 'Invalid or expired token'], 400);
        }

        $user = $resetToken->getUser();
        $user->setPassword($hasher->hashPassword($user, $data['newPassword']));
        $em->remove($resetToken);
        $em->flush();

        return new JsonResponse(['message' => 'Password reset successfully']);
    }
}
