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
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create/Update Admin
        $admin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@pawstuff.com']);

        if ($admin) {
            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setStatus('active');
            $admin->setVerifiedAt(new \DateTime());
            $this->entityManager->flush();
            $io->success('Admin user updated successfully!');
        } else {
            $admin = new User();
            $admin->setEmail('admin@pawstuff.com');
            $admin->setFullName('Admin User');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setStatus('active');
            $admin->setVerifiedAt(new \DateTime());

            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);

            $this->entityManager->persist($admin);
            $this->entityManager->flush();
            $io->success('Admin user created successfully!');
        }

        // Create/Update Staff
        $staff = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'staff@pawstuff.com']);

        if ($staff) {
            $hashedPassword = $this->passwordHasher->hashPassword($staff, 'staff123');
            $staff->setPassword($hashedPassword);
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setStatus('active');
            $staff->setVerifiedAt(new \DateTime());
            $this->entityManager->flush();
            $io->success('Staff user updated successfully!');
        } else {
            $staff = new User();
            $staff->setEmail('staff@pawstuff.com');
            $staff->setFullName('Staff User');
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setStatus('active');
            $staff->setCreatedBy('admin@pawstuff.com');
            $staff->setVerifiedAt(new \DateTime());

            $hashedPassword = $this->passwordHasher->hashPassword($staff, 'staff123');
            $staff->setPassword($hashedPassword);

            $this->entityManager->persist($staff);
            $this->entityManager->flush();
            $io->success('Staff user created successfully!');
        }

        $io->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@pawstuff.com', 'admin123', 'ROLE_ADMIN'],
                ['staff@pawstuff.com', 'staff123', 'ROLE_STAFF']
            ]
        );

        return Command::SUCCESS;
    }
}
