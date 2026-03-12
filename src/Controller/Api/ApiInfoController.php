<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiInfoController extends AbstractController
{
    #[Route('/api', name: 'api_info', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'name' => 'Customer Management API',
            'version' => '1.0',
            'documentation' => 'See API_DOCUMENTATION.md for full documentation',
            'endpoints' => [
                'authentication' => [
                    'POST /api/login' => 'Get JWT token (Body: {"email": "...", "password": "..."})',
                ],
                'customers' => [
                    'GET /api/customers' => 'List all customers (requires JWT)',
                    'GET /api/customers/{id}' => 'Get single customer (requires JWT)',
                    'POST /api/customers' => 'Create customer (requires JWT)',
                    'PUT /api/customers/{id}' => 'Update customer (requires JWT)',
                    'PATCH /api/customers/{id}' => 'Partial update customer (requires JWT)',
                    'DELETE /api/customers/{id}' => 'Delete customer (requires JWT)',
                ]
            ],
            'usage' => [
                '1. POST to /api/login with email and password to get JWT token',
                '2. Include token in Authorization header: Bearer YOUR_TOKEN',
                '3. Access customer endpoints with the token'
            ]
        ]);
    }
}
