<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $adminUser = new User();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setFullName('Admin User');
        $adminUser->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $adminUser->setStatus('active');
        $adminUser->setCreatedBy('System');
        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'password');
        $adminUser->setPassword($hashedPassword);
        $manager->persist($adminUser);

        // Create regular users
        $users = [
            ['email' => 'john.doe@example.com', 'name' => 'John Doe', 'password' => 'password123'],
            ['email' => 'jane.smith@example.com', 'name' => 'Jane Smith', 'password' => 'password123'],
            ['email' => 'bob.wilson@example.com', 'name' => 'Bob Wilson', 'password' => 'password123'],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFullName($userData['name']);
            $user->setRoles(['ROLE_USER']);
            $user->setStatus('active');
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            $manager->persist($user);
        }

        // Create sample customers
        $customers = [
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@email.com',
                'phoneNumber' => '+1234567890',
                'address' => '123 Main Street',
                'city' => 'New York',
                'postalCode' => '10001',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-01-15 10:30:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-01 14:20:00'),
            ],
            [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'email' => 'jane.smith@email.com',
                'phoneNumber' => '+1987654321',
                'address' => '456 Oak Avenue',
                'city' => 'Los Angeles',
                'postalCode' => '90001',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-02-10 09:15:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-05 11:45:00'),
            ],
            [
                'firstName' => 'Robert',
                'lastName' => 'Johnson',
                'email' => 'robert.j@email.com',
                'phoneNumber' => '+1555123456',
                'address' => '789 Pine Road',
                'city' => 'Chicago',
                'postalCode' => '60601',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-01-20 13:45:00'),
                'lastPurchaseDate' => new \DateTime('2024-02-28 16:30:00'),
            ],
            [
                'firstName' => 'Emily',
                'lastName' => 'Davis',
                'email' => 'emily.davis@email.com',
                'phoneNumber' => '+1555987654',
                'address' => '321 Elm Street',
                'city' => 'Houston',
                'postalCode' => '77001',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-02-05 08:20:00'),
                'lastPurchaseDate' => null,
            ],
            [
                'firstName' => 'Michael',
                'lastName' => 'Brown',
                'email' => 'michael.brown@email.com',
                'phoneNumber' => '+1555654321',
                'address' => '654 Maple Drive',
                'city' => 'Phoenix',
                'postalCode' => '85001',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-03-01 10:00:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-08 12:15:00'),
            ],
            [
                'firstName' => 'Sarah',
                'lastName' => 'Wilson',
                'email' => 'sarah.wilson@email.com',
                'phoneNumber' => '+1555111222',
                'address' => '987 Cedar Lane',
                'city' => 'Philadelphia',
                'postalCode' => '19101',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-01-10 15:30:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-10 09:45:00'),
            ],
            [
                'firstName' => 'David',
                'lastName' => 'Martinez',
                'email' => 'david.m@email.com',
                'phoneNumber' => '+1555333444',
                'address' => '147 Birch Avenue',
                'city' => 'San Antonio',
                'postalCode' => '78201',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-02-15 11:20:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-02 14:50:00'),
            ],
            [
                'firstName' => 'Lisa',
                'lastName' => 'Anderson',
                'email' => 'lisa.anderson@email.com',
                'phoneNumber' => '+1555222333',
                'address' => '258 Spruce Street',
                'city' => 'San Diego',
                'postalCode' => '92101',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-01-25 09:00:00'),
                'lastPurchaseDate' => null,
            ],
            [
                'firstName' => 'James',
                'lastName' => 'Taylor',
                'email' => 'james.taylor@email.com',
                'phoneNumber' => '+1555444555',
                'address' => '369 Walnut Road',
                'city' => 'Dallas',
                'postalCode' => '75201',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-02-20 14:15:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-11 10:30:00'),
            ],
            [
                'firstName' => 'Jennifer',
                'lastName' => 'Thomas',
                'email' => 'jennifer.thomas@email.com',
                'phoneNumber' => '+1555666777',
                'address' => '741 Ash Boulevard',
                'city' => 'San Jose',
                'postalCode' => '95101',
                'country' => 'USA',
                'registrationDate' => new \DateTime('2024-03-05 12:30:00'),
                'lastPurchaseDate' => new \DateTime('2024-03-09 15:20:00'),
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = new Customer();
            $customer->setFirstName($customerData['firstName']);
            $customer->setLastName($customerData['lastName']);
            $customer->setEmail($customerData['email']);
            $customer->setPhoneNumber($customerData['phoneNumber']);
            $customer->setAddress($customerData['address']);
            $customer->setCity($customerData['city']);
            $customer->setPostalCode($customerData['postalCode']);
            $customer->setCountry($customerData['country']);
            $customer->setRegistrationDate($customerData['registrationDate']);
            $customer->setLastPurchaseDate($customerData['lastPurchaseDate']);
            $manager->persist($customer);
        }

        $manager->flush();
    }
}
