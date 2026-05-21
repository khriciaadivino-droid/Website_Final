<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminCommand extends Command
{
    private const DEFAULT_ADMIN_EMAIL = 'admin@pawstuff.com';
    private const DEFAULT_ADMIN_PASSWORD = 'admin123';
    private const DEFAULT_STAFF_EMAIL = 'staff@pawstuff.com';
    private const DEFAULT_STAFF_PASSWORD = 'staff123';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $adminEmail = $this->readEnv('BOOTSTRAP_ADMIN_EMAIL', self::DEFAULT_ADMIN_EMAIL);
        $adminPassword = $this->readEnv('BOOTSTRAP_ADMIN_PASSWORD', self::DEFAULT_ADMIN_PASSWORD);
        $staffEmail = $this->readEnv('BOOTSTRAP_STAFF_EMAIL', self::DEFAULT_STAFF_EMAIL);
        $staffPassword = $this->readEnv('BOOTSTRAP_STAFF_PASSWORD', self::DEFAULT_STAFF_PASSWORD);

        // Create/Update Admin
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $adminEmail]);

        if ($admin) {
            $hashedPassword = $this->passwordHasher->hashPassword($admin, $adminPassword);
            $admin->setPassword($hashedPassword);
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setStatus('active');
            $admin->setVerifiedAt(new \DateTime());
            $this->entityManager->flush();
            $io->success(sprintf('Admin user updated successfully for %s.', $adminEmail));
        } else {
            $admin = new User();
            $admin->setEmail($adminEmail);
            $admin->setFullName('Admin User');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setStatus('active');
            $admin->setVerifiedAt(new \DateTime());

            $hashedPassword = $this->passwordHasher->hashPassword($admin, $adminPassword);
            $admin->setPassword($hashedPassword);

            $this->entityManager->persist($admin);
            $this->entityManager->flush();
            $io->success(sprintf('Admin user created successfully for %s.', $adminEmail));
        }

        // Create/Update Staff
        $staff = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $staffEmail]);

        if ($staff) {
            $hashedPassword = $this->passwordHasher->hashPassword($staff, $staffPassword);
            $staff->setPassword($hashedPassword);
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setStatus('active');
            $staff->setVerifiedAt(new \DateTime());
            $this->entityManager->flush();
            $io->success(sprintf('Staff user updated successfully for %s.', $staffEmail));
        } else {
            $staff = new User();
            $staff->setEmail($staffEmail);
            $staff->setFullName('Staff User');
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setStatus('active');
            $staff->setCreatedBy($adminEmail);
            $staff->setVerifiedAt(new \DateTime());

            $hashedPassword = $this->passwordHasher->hashPassword($staff, $staffPassword);
            $staff->setPassword($hashedPassword);

            $this->entityManager->persist($staff);
            $this->entityManager->flush();
            $io->success(sprintf('Staff user created successfully for %s.', $staffEmail));
        }

        $io->note(sprintf('Bootstrap admin email: %s', $adminEmail));
        $io->note(sprintf('Bootstrap staff email: %s', $staffEmail));

        return Command::SUCCESS;
    }

    private function readEnv(string $key, string $default): string
    {
        $value = getenv($key);

        if (!is_string($value)) {
            return $default;
        }

        $normalizedValue = trim($value);

        return $normalizedValue !== '' ? $normalizedValue : $default;
    }
}
