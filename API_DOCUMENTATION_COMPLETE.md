# KHRICIADIVINO API - Complete Endpoint Documentation

**Version:** 1.0.0  
**Last Updated:** May 2026  
**Base URL:** `http://localhost:8000/api` (local) | `https://khriciadivino.example.com/api` (production)

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Authentication](#authentication)
3. [Error Handling](#error-handling)
4. [Endpoints Overview](#endpoints-overview)
5. [Authentication Endpoints](#1-authentication-endpoints)
6. [Customer Endpoints](#2-customer-endpoints)
7. [Product Endpoints](#3-product-endpoints)
8. [Stock Endpoints](#4-stock-endpoints)
9. [Order Endpoints](#5-order-endpoints)
10. [Category Endpoints](#6-category-endpoints)
11. [Pet Owners Endpoints](#7-pet-owners-endpoints)
12. [Pet Profiles Endpoints](#8-pet-profiles-endpoints)
13. [Contact Message Endpoints](#9-contact-message-endpoints)
14. [API Response Standards](#api-response-standards)

---

## Quick Start

### 1. Installation & Setup

```bash
# Clone the repository
git clone https://github.com/khrings/Webdev2Finals.git khriciadivino
cd khriciadivino

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Setup environment
cp .env.example .env
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Start the server
symfony server:start
# OR
php -S localhost:8000 -t public
```

### 2. Get Your First JWT Token

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MTA5MzI4MDAsImV4cCI6MTcxMDk0NjQwMCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiYWRtaW4ifQ.aBcdEf..."
}
```

### 3. Make Your First Request

```bash
curl -X GET http://localhost:8000/api/customers \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Authentication

### JWT (JSON Web Token) Authentication

All protected API endpoints require a valid JWT token in the `Authorization` header.

**Header Format:**
```
Authorization: Bearer <your_jwt_token>
```

### Token Lifespan

- **Access Token Duration:** 1 hour
- **Refresh:** Get a new token by logging in again

### Authentication Endpoints

#### POST `/api/login` - User Login
**Description:** Authenticate user and receive JWT token  
**Auth Required:** No

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Success Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "roles": ["ROLE_USER"]
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "code": "INVALID_CREDENTIALS"
}
```

---

#### POST `/api/register` - User Registration
**Description:** Register a new user account  
**Auth Required:** No

**Request Body:**
```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "firstName": "John",
  "lastName": "Doe"
}
```

**Validation Rules:**
- Email must be unique and valid
- Password minimum 8 characters
- Username 3-20 characters

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful. Please check your email to verify your account.",
  "data": {
    "id": 2,
    "email": "john@example.com",
    "username": "john_doe",
    "isVerified": false,
    "createdAt": "2024-03-10T15:30:00+00:00"
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Email already registered",
  "code": "EMAIL_EXISTS"
}
```

---

#### GET/POST `/api/verify-email` - Verify Email Address
**Description:** Verify user email using token from email  
**Auth Required:** No

**Query Parameter (GET):**
```
GET /api/verify-email?token=abc123xyz
```

**Request Body (POST):**
```json
{
  "token": "abc123xyz"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Email verified successfully",
  "user": {
    "id": 2,
    "email": "john@example.com",
    "isVerified": true
  }
}
```

---

#### POST `/api/resend-verification` - Resend Verification Email
**Description:** Resend verification email to user  
**Auth Required:** Yes (JWT Token)

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Verification email sent successfully"
}
```

---

#### GET `/api/users/me` - Get Current User Profile
**Description:** Get authenticated user's profile information  
**Auth Required:** Yes (JWT Token)

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@example.com",
    "username": "user123",
    "firstName": "John",
    "lastName": "Doe",
    "fullName": "John Doe",
    "roles": ["ROLE_USER", "ROLE_STAFF"],
    "isVerified": true,
    "lastLogin": "2024-03-10T15:30:00+00:00",
    "createdAt": "2024-01-15T10:30:00+00:00"
  }
}
```

---

## Error Handling

### Standard Error Response Format

All errors follow this standardized format:

```json
{
  "success": false,
  "message": "Human-readable error message",
  "code": "ERROR_CODE",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "timestamp": "2024-03-10T15:30:00+00:00"
}
```

### HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK - Request successful | GET /api/customers |
| 201 | Created - Resource created | POST /api/orders |
| 400 | Bad Request - Invalid input | Missing required field |
| 401 | Unauthorized - Missing/invalid token | No JWT token provided |
| 403 | Forbidden - Insufficient permissions | Non-admin accessing admin endpoint |
| 404 | Not Found - Resource doesn't exist | GET /api/customers/999 |
| 409 | Conflict - Resource conflict | Duplicate email on registration |
| 422 | Unprocessable - Validation errors | Invalid email format |
| 500 | Server Error - Internal error | Database connection failure |

### Common Error Codes

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| INVALID_CREDENTIALS | 401 | Email/password incorrect |
| UNAUTHORIZED | 401 | Missing/expired JWT token |
| FORBIDDEN | 403 | User lacks required role |
| NOT_FOUND | 404 | Resource doesn't exist |
| VALIDATION_ERROR | 422 | Input validation failed |
| EMAIL_EXISTS | 409 | Email already registered |
| INSUFFICIENT_STOCK | 400 | Product out of stock |
| INVALID_TOKEN | 401 | Token malformed/expired |

---

## Endpoints Overview

| Method | Endpoint | Auth | Role | Purpose |
|--------|----------|------|------|---------|
| POST | `/api/login` | No | - | Login & get JWT token |
| POST | `/api/register` | No | - | Register new account |
| GET/POST | `/api/verify-email` | No | - | Verify email address |
| POST | `/api/resend-verification` | Yes | User+ | Resend verification email |
| GET | `/api/users/me` | Yes | User+ | Get current user profile |
| GET | `/api/customers` | Yes | User+ | List all customers |
| GET | `/api/customers/{id}` | Yes | User+ | Get specific customer |
| POST | `/api/customers` | Yes | Staff+ | Create new customer |
| PUT/PATCH | `/api/customers/{id}` | Yes | Staff+ | Update customer |
| DELETE | `/api/customers/{id}` | Yes | Admin | Delete customer |
| GET | `/api/products` | No | - | List products (public) |
| GET | `/api/products/{id}` | No | - | Get product details |
| POST | `/api/products` | Yes | Staff+ | Create product |
| PUT/PATCH | `/api/products/{id}` | Yes | Staff+ | Update product |
| DELETE | `/api/products/{id}` | Yes | Admin | Delete product |
| GET | `/api/stocks` | Yes | Staff+ | List stock movements |
| POST | `/api/stocks` | Yes | Staff+ | Create stock entry |
| PUT/PATCH | `/api/stocks/{id}` | Yes | Staff+ | Update stock |
| DELETE | `/api/stocks/{id}` | Yes | Admin | Delete stock |
| GET | `/api/orders` | Yes | User+ | List orders |
| POST | `/api/orders` | Yes | User+ | Create order |
| PUT/PATCH | `/api/orders/{id}` | Yes | Staff+ | Update order |
| DELETE | `/api/orders/{id}` | Yes | Admin | Delete order |
| GET | `/api/categories` | No | - | List categories |
| POST | `/api/categories` | Yes | Staff+ | Create category |
| PUT/PATCH | `/api/categories/{id}` | Yes | Staff+ | Update category |
| DELETE | `/api/categories/{id}` | Yes | Admin | Delete category |

---

## 1. Authentication Endpoints

(See [Authentication](#authentication) section above)

---

## 2. Customer Endpoints

**Base Path:** `/api/customers`  
**Auth Required:** Yes (JWT Token on all endpoints)  
**Minimum Role:** `ROLE_USER` (read), `ROLE_STAFF` (write), `ROLE_ADMIN` (delete)

### GET `/api/customers` - List All Customers

**Description:** Retrieve all customers with pagination and filtering

**Query Parameters:**
```
?page=1&limit=20&search=john&sortBy=firstName&sortOrder=asc
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| limit | integer | No | Items per page (default: 20, max: 100) |
| search | string | No | Search by firstName, lastName, email |
| sortBy | string | No | Sort field (firstName, lastName, email, registrationDate) |
| sortOrder | string | No | asc or desc (default: asc) |

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Customers retrieved successfully",
  "data": [
    {
      "id": 1,
      "firstName": "John",
      "lastName": "Doe",
      "fullName": "John Doe",
      "email": "john@example.com",
      "phoneNumber": "+1234567890",
      "address": "123 Main St",
      "city": "New York",
      "state": "NY",
      "postalCode": "10001",
      "country": "USA",
      "registrationDate": "2024-01-15T10:30:00+00:00",
      "lastPurchaseDate": "2024-03-01T14:20:00+00:00",
      "totalSpent": 456.78,
      "ordersCount": 5
    },
    {
      "id": 2,
      "firstName": "Jane",
      "lastName": "Smith",
      "fullName": "Jane Smith",
      "email": "jane@example.com",
      "phoneNumber": "+1987654321",
      "address": "456 Oak Ave",
      "city": "Los Angeles",
      "state": "CA",
      "postalCode": "90001",
      "country": "USA",
      "registrationDate": "2024-02-20T08:15:00+00:00",
      "lastPurchaseDate": "2024-03-08T11:45:00+00:00",
      "totalSpent": 234.56,
      "ordersCount": 3
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 1,
    "totalItems": 2,
    "itemsPerPage": 20
  },
  "timestamp": "2024-03-10T15:30:00+00:00"
}
```

---

### GET `/api/customers/{id}` - Get Single Customer

**Description:** Retrieve details of a specific customer

**Path Parameters:**
- `id` (integer, required): Customer ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "fullName": "John Doe",
    "email": "john@example.com",
    "phoneNumber": "+1234567890",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postalCode": "10001",
    "country": "USA",
    "registrationDate": "2024-01-15T10:30:00+00:00",
    "lastPurchaseDate": "2024-03-01T14:20:00+00:00",
    "totalSpent": 456.78,
    "ordersCount": 5,
    "recentOrders": [
      {
        "id": 1,
        "orderNumber": "ORD-001",
        "orderDate": "2024-03-01T14:20:00+00:00",
        "totalAmount": 129.95,
        "status": "completed"
      }
    ]
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Customer not found",
  "code": "NOT_FOUND"
}
```

---

### POST `/api/customers` - Create New Customer

**Description:** Create a new customer record

**Request Body:**
```json
{
  "firstName": "Jane",
  "lastName": "Smith",
  "email": "jane.smith@example.com",
  "phoneNumber": "+1987654321",
  "address": "456 Oak Ave",
  "city": "Los Angeles",
  "state": "CA",
  "postalCode": "90001",
  "country": "USA"
}
```

**Validation Rules:**
- `firstName`: required, 1-50 characters
- `lastName`: required, 1-50 characters
- `email`: required, valid email, unique
- `phoneNumber`: optional, valid format
- `address`: optional, max 200 characters
- `city`: optional, 1-50 characters
- `postalCode`: optional, valid format
- `country`: optional, 1-50 characters

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Customer created successfully",
  "data": {
    "id": 3,
    "firstName": "Jane",
    "lastName": "Smith",
    "fullName": "Jane Smith",
    "email": "jane.smith@example.com",
    "phoneNumber": "+1987654321",
    "address": "456 Oak Ave",
    "city": "Los Angeles",
    "state": "CA",
    "postalCode": "90001",
    "country": "USA",
    "registrationDate": "2024-03-10T15:30:00+00:00",
    "lastPurchaseDate": null,
    "totalSpent": 0,
    "ordersCount": 0
  }
}
```

**Error Response (422 Unprocessable):**
```json
{
  "success": false,
  "message": "Validation failed",
  "code": "VALIDATION_ERROR",
  "errors": {
    "email": ["Email already exists"],
    "phoneNumber": ["Invalid phone number format"]
  }
}
```

---

### PUT `/api/customers/{id}` - Update Customer (Full)

**Description:** Update all customer fields (full replacement)

**Path Parameters:**
- `id` (integer, required): Customer ID

**Request Body:** (all fields required)
```json
{
  "firstName": "Jane",
  "lastName": "Smith",
  "email": "jane.smith@example.com",
  "phoneNumber": "+1987654321",
  "address": "456 Oak Ave",
  "city": "Los Angeles",
  "state": "CA",
  "postalCode": "90001",
  "country": "USA"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Customer updated successfully",
  "data": { }
}
```

---

### PATCH `/api/customers/{id}` - Update Customer (Partial)

**Description:** Partially update customer fields

**Path Parameters:**
- `id` (integer, required): Customer ID

**Request Body:** (only fields to update)
```json
{
  "phoneNumber": "+1111111111",
  "city": "San Francisco",
  "state": "CA"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Customer updated successfully",
  "data": { }
}
```

---

### DELETE `/api/customers/{id}` - Delete Customer

**Description:** Delete a customer record  
**Required Role:** `ROLE_ADMIN`

**Path Parameters:**
- `id` (integer, required): Customer ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Customer deleted successfully"
}
```

**Error Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "You do not have permission to delete customers",
  "code": "FORBIDDEN"
}
```

---

## 3. Product Endpoints

**Base Path:** `/api/products`  
**Auth Required:** GET endpoints (No), POST/PUT/DELETE (Yes)  
**Minimum Role:** `ROLE_STAFF` (write), `ROLE_ADMIN` (delete)

### GET `/api/products` - List All Products

**Description:** Retrieve all products (public endpoint)

**Query Parameters:**
```
?page=1&limit=20&category=1&minPrice=0&maxPrice=100&search=food&sortBy=price&sortOrder=asc
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| limit | integer | No | Items per page (default: 20, max: 100) |
| category | integer | No | Filter by category ID |
| minPrice | number | No | Minimum price filter |
| maxPrice | number | No | Maximum price filter |
| search | string | No | Search by product name/description |
| sortBy | string | No | price, name, createdAt (default: name) |
| sortOrder | string | No | asc or desc (default: asc) |
| inStock | boolean | No | Filter by stock availability |

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Dog Food Premium",
      "description": "High quality organic dog food with chicken and rice",
      "price": 25.99,
      "quantity": 150,
      "inStock": true,
      "category": {
        "id": 1,
        "name": "Pet Food"
      },
      "image": "dog-food-premium.jpg",
      "imageUrl": "https://khriciadivino.example.com/images/dog-food-premium.jpg",
      "rating": 4.5,
      "reviews": 23,
      "createdAt": "2024-01-10T08:00:00+00:00",
      "updatedAt": "2024-03-10T15:30:00+00:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 5,
    "totalItems": 95,
    "itemsPerPage": 20
  }
}
```

---

### GET `/api/products/{id}` - Get Product Details

**Description:** Retrieve detailed information for a specific product

**Path Parameters:**
- `id` (integer, required): Product ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Dog Food Premium",
    "description": "High quality organic dog food with chicken and rice",
    "price": 25.99,
    "quantity": 150,
    "inStock": true,
    "category": {
      "id": 1,
      "name": "Pet Food"
    },
    "image": "dog-food-premium.jpg",
    "imageUrl": "https://khriciadivino.example.com/images/dog-food-premium.jpg",
    "rating": 4.5,
    "reviews": 23,
    "createdAt": "2024-01-10T08:00:00+00:00",
    "updatedAt": "2024-03-10T15:30:00+00:00",
    "sku": "DFP-001",
    "manufacturer": "PetCare Corp",
    "specifications": {
      "weight": "5kg",
      "ingredients": ["Chicken", "Rice", "Vegetables"],
      "bestFor": "Adult dogs"
    }
  }
}
```

---

### GET `/api/mobile/products` - List Products (Mobile)

**Description:** Mobile-optimized product listing with image URLs

**Query Parameters:** Same as `/api/products`

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Dog Food Premium",
      "price": 25.99,
      "quantity": 150,
      "image": "dog-food-premium.jpg",
      "imageUrl": "https://khriciadivino.example.com/images/dog-food-premium.jpg",
      "category": "Pet Food",
      "rating": 4.5
    }
  ]
}
```

---

### POST `/api/products` - Create Product

**Description:** Create a new product  
**Auth Required:** Yes  
**Minimum Role:** `ROLE_STAFF`

**Request Body:**
```json
{
  "name": "Cat Toys Set",
  "description": "Interactive cat toys including feather wand and laser toy",
  "price": 15.99,
  "quantity": 200,
  "category_id": 2,
  "image": "cat-toys-set.jpg",
  "sku": "CTS-001",
  "manufacturer": "FunPets Inc"
}
```

**Validation Rules:**
- `name`: required, 1-255 characters, unique
- `description`: required, max 1000 characters
- `price`: required, positive number, 2 decimals
- `quantity`: required, non-negative integer
- `category_id`: required, must exist
- `image`: optional, valid file
- `sku`: optional, must be unique

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 95,
    "name": "Cat Toys Set",
    "description": "Interactive cat toys including feather wand and laser toy",
    "price": 15.99,
    "quantity": 200,
    "category": {
      "id": 2,
      "name": "Pet Toys"
    },
    "image": "cat-toys-set.jpg",
    "sku": "CTS-001",
    "createdAt": "2024-03-10T15:30:00+00:00"
  }
}
```

---

### PUT/PATCH `/api/products/{id}` - Update Product

**Description:** Update product information  
**Auth Required:** Yes  
**Minimum Role:** `ROLE_STAFF`

**Path Parameters:**
- `id` (integer, required): Product ID

**Request Body:** (PATCH allows partial fields)
```json
{
  "name": "Cat Toys Set - Premium",
  "price": 17.99,
  "quantity": 180
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Product updated successfully"
}
```

---

### DELETE `/api/products/{id}` - Delete Product

**Description:** Delete a product  
**Auth Required:** Yes  
**Minimum Role:** `ROLE_ADMIN`

**Path Parameters:**
- `id` (integer, required): Product ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

---

## 4. Stock Endpoints

**Base Path:** `/api/stocks`  
**Auth Required:** Yes (all endpoints)  
**Minimum Role:** `ROLE_STAFF` (read/write), `ROLE_ADMIN` (delete)

### GET `/api/stocks` - List Stock Movements

**Description:** Retrieve stock transaction history

**Query Parameters:**
```
?page=1&limit=20&product_id=1&sortBy=createdAt&sortOrder=desc
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Stocks fetched successfully",
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Dog Food Premium"
      },
      "quantityChange": -10,
      "stockChangeLog": "Order #ORD-001 created via API",
      "stockType": "outbound",
      "createdAt": "2024-03-10T15:30:00+00:00",
      "updatedAt": "2024-03-10T15:30:00+00:00",
      "createdBy": "staff@example.com"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 52
  },
  "meta": {
    "totalInbound": 500,
    "totalOutbound": 200,
    "netChange": 300,
    "timestamp": "2024-03-10T15:30:00+00:00"
  }
}
```

---

### POST `/api/stocks` - Create Stock Entry

**Description:** Record a stock movement (inbound or outbound)

**Request Body:**
```json
{
  "product_id": 1,
  "quantity_change": 50,
  "stock_change_log": "Restocking delivery from supplier",
  "stock_type": "inbound"
}
```

**Validation:**
- `product_id`: required, must exist
- `quantity_change`: required, non-zero integer
- `stock_change_log`: required, max 500 characters
- `stock_type`: required, "inbound" or "outbound"

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Stock entry created successfully",
  "data": {
    "id": 52,
    "product": {
      "id": 1,
      "name": "Dog Food Premium",
      "newQuantity": 200
    },
    "quantityChange": 50,
    "stockChangeLog": "Restocking delivery from supplier",
    "stockType": "inbound",
    "createdAt": "2024-03-10T15:30:00+00:00"
  }
}
```

---

### PUT/PATCH `/api/stocks/{id}` - Update Stock Entry

**Path Parameters:**
- `id` (integer, required): Stock entry ID

**Request Body:**
```json
{
  "stock_change_log": "Updated reason for stock change"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Stock entry updated successfully"
}
```

---

### DELETE `/api/stocks/{id}` - Delete Stock Entry

**Auth Required:** Yes  
**Minimum Role:** `ROLE_ADMIN`

**Path Parameters:**
- `id` (integer, required): Stock entry ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Stock entry deleted successfully"
}
```

---

## 5. Order Endpoints

**Base Path:** `/api/orders`  
**Auth Required:** Yes (all endpoints)  
**Minimum Role:** `ROLE_USER` (read own orders), `ROLE_STAFF` (read all), `ROLE_ADMIN` (delete)

### GET `/api/orders` - List Orders

**Description:** Retrieve orders with pagination and filtering

**Query Parameters:**
```
?page=1&limit=20&status=pending&customer_id=1&sortBy=orderDate&sortOrder=desc
```

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number |
| limit | integer | No | Items per page |
| status | string | No | pending, processing, completed, cancelled |
| customer_id | integer | No | Filter by customer |
| dateFrom | string | No | Filter from date (YYYY-MM-DD) |
| dateTo | string | No | Filter to date (YYYY-MM-DD) |
| sortBy | string | No | orderDate, totalAmount, status |

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Orders fetched successfully",
  "data": [
    {
      "id": 1,
      "orderNumber": "ORD-001",
      "product": {
        "id": 1,
        "name": "Dog Food Premium"
      },
      "customer": {
        "id": 1,
        "fullName": "John Doe",
        "email": "john@example.com"
      },
      "quantity": 5,
      "unitPrice": 25.99,
      "totalAmount": 129.95,
      "status": "completed",
      "orderDate": "2024-03-01T14:20:00+00:00",
      "deliveryDate": "2024-03-05T10:00:00+00:00",
      "notes": "Leave at front door",
      "createdAt": "2024-03-01T14:20:00+00:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 2,
    "totalItems": 25
  },
  "meta": {
    "totalRevenue": 3245.75,
    "averageOrderValue": 129.83,
    "timestamp": "2024-03-10T15:30:00+00:00"
  }
}
```

---

### GET `/api/orders/{id}` - Get Order Details

**Path Parameters:**
- `id` (integer, required): Order ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "orderNumber": "ORD-001",
    "product": {
      "id": 1,
      "name": "Dog Food Premium",
      "sku": "DFP-001"
    },
    "customer": {
      "id": 1,
      "fullName": "John Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "address": "123 Main St, New York, NY 10001"
    },
    "quantity": 5,
    "unitPrice": 25.99,
    "totalAmount": 129.95,
    "status": "completed",
    "orderDate": "2024-03-01T14:20:00+00:00",
    "deliveryDate": "2024-03-05T10:00:00+00:00",
    "notes": "Leave at front door",
    "timeline": [
      {
        "status": "pending",
        "timestamp": "2024-03-01T14:20:00+00:00"
      },
      {
        "status": "processing",
        "timestamp": "2024-03-02T08:00:00+00:00"
      },
      {
        "status": "shipped",
        "timestamp": "2024-03-03T16:30:00+00:00"
      },
      {
        "status": "completed",
        "timestamp": "2024-03-05T10:00:00+00:00"
      }
    ]
  }
}
```

---

### POST `/api/orders` - Create Order

**Description:** Create a new order

**Request Body:**
```json
{
  "product_id": 1,
  "customer_id": 1,
  "quantity": 3,
  "notes": "Standard delivery"
}
```

**Validation:**
- `product_id`: required, must exist and have stock
- `customer_id`: required, must exist
- `quantity`: required, positive integer, cannot exceed available stock
- `notes`: optional, max 500 characters

**Business Logic:**
1. Checks stock availability
2. Creates order with "pending" status
3. Automatically deducts from product quantity
4. Creates stock movement log
5. Returns order details

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Order created successfully. Stock has been deducted.",
  "data": {
    "id": 26,
    "orderNumber": "ORD-026",
    "product": {
      "id": 1,
      "name": "Dog Food Premium",
      "newQuantity": 147
    },
    "customer": {
      "id": 1,
      "fullName": "John Doe"
    },
    "quantity": 3,
    "unitPrice": 25.99,
    "totalAmount": 77.97,
    "status": "pending",
    "orderDate": "2024-03-10T15:30:00+00:00",
    "notes": "Standard delivery"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Insufficient stock available",
  "code": "INSUFFICIENT_STOCK",
  "data": {
    "requested": 10,
    "available": 5
  }
}
```

---

### PUT/PATCH `/api/orders/{id}` - Update Order

**Description:** Update order details. Only admins can change the order status.  
**Minimum Role:** `ROLE_STAFF` for non-status fields, `ROLE_ADMIN` for status changes

**Path Parameters:**
- `id` (integer, required): Order ID

**Request Body:**
```json
{
  "status": "processing",
  "notes": "Updated delivery notes"
}
```

**Status Rule:**
- Non-admin requests may update order details, but any submitted `status` value is ignored.
- Admin requests may set `Pending`, `Processing`, `Completed`, or `Cancelled`.

**Allowed Status Transitions:**
- pending → processing, cancelled
- processing → shipped, cancelled
- shipped → completed, cancelled
- completed (final)
- cancelled (final)

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Order updated successfully"
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Invalid status transition",
  "code": "INVALID_STATUS_TRANSITION",
  "data": {
    "current": "completed",
    "requested": "processing"
  }
}
```

---

### DELETE `/api/orders/{id}` - Delete Order

**Description:** Delete an order  
**Minimum Role:** `ROLE_ADMIN`

**Note:** Deletion restores stock to product quantity

**Path Parameters:**
- `id` (integer, required): Order ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Order deleted successfully. Stock has been restored."
}
```

---

## 6. Category Endpoints

**Base Path:** `/api/categories`  
**Auth Required:** GET (No), POST/PUT/PATCH/DELETE (Yes)  
**Minimum Role:** `ROLE_STAFF` (write), `ROLE_ADMIN` (delete)

### GET `/api/categories` - List Categories

**Description:** List all product categories

**Query Parameters:**
```
?page=1&limit=50&search=pet&sortBy=name
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Categories fetched successfully",
  "data": [
    {
      "id": 1,
      "name": "Pet Food",
      "description": "Food and treats for pets",
      "productCount": 25,
      "createdAt": "2024-01-10T08:00:00+00:00"
    },
    {
      "id": 2,
      "name": "Pet Toys",
      "description": "Toys and accessories",
      "productCount": 15,
      "createdAt": "2024-01-10T08:00:00+00:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 1,
    "totalItems": 8
  }
}
```

---

### GET `/api/categories/{id}` - Get Category Details

**Path Parameters:**
- `id` (integer, required): Category ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Pet Food",
    "description": "Food and treats for pets",
    "productCount": 25,
    "products": [
      {
        "id": 1,
        "name": "Dog Food Premium",
        "price": 25.99
      }
    ],
    "createdAt": "2024-01-10T08:00:00+00:00"
  }
}
```

---

### POST `/api/categories` - Create Category

**Auth Required:** Yes  
**Minimum Role:** `ROLE_STAFF`

**Request Body:**
```json
{
  "name": "Pet Accessories",
  "description": "Collars, leashes, and other pet accessories"
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 9,
    "name": "Pet Accessories",
    "description": "Collars, leashes, and other pet accessories",
    "productCount": 0
  }
}
```

---

### PUT/PATCH `/api/categories/{id}` - Update Category

**Auth Required:** Yes  
**Minimum Role:** `ROLE_STAFF`

**Path Parameters:**
- `id` (integer, required): Category ID

**Request Body:**
```json
{
  "name": "Pet Accessories & Gear",
  "description": "Updated description"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Category updated successfully"
}
```

---

### DELETE `/api/categories/{id}` - Delete Category

**Auth Required:** Yes  
**Minimum Role:** `ROLE_ADMIN`

**Path Parameters:**
- `id` (integer, required): Category ID

**Note:** Can only delete empty categories (no products)

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Cannot delete category with existing products",
  "code": "CATEGORY_NOT_EMPTY",
  "data": {
    "productCount": 5
  }
}
```

---

## 7. Pet Owners Endpoints

**Base Path:** `/api/pet-owners`  
**Auth Required:** Yes (all endpoints)  
**Minimum Role:** `ROLE_USER` (read), `ROLE_STAFF` (write), `ROLE_ADMIN` (delete)

### GET `/api/pet-owners` - List Pet Owners

**Query Parameters:**
```
?page=1&limit=20&search=john&sortBy=fullName
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Pet owners fetched successfully",
  "data": [
    {
      "id": 1,
      "fullName": "John Doe",
      "email": "john@example.com",
      "phoneNumber": "+1234567890",
      "address": "123 Main St",
      "city": "New York",
      "state": "NY",
      "postalCode": "10001",
      "registrationDate": "2024-03-10T15:30:00+00:00",
      "petCount": 2,
      "pets": [
        {
          "id": 1,
          "name": "Buddy",
          "species": "Dog",
          "breed": "Golden Retriever"
        }
      ]
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 1,
    "totalItems": 1
  }
}
```

---

### POST `/api/pet-owners` - Create Pet Owner

**Request Body:**
```json
{
  "fullName": "Jane Smith",
  "email": "jane.smith@example.com",
  "phoneNumber": "+1987654321",
  "address": "456 Oak Ave",
  "city": "Los Angeles",
  "state": "CA",
  "postalCode": "90001"
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Pet owner created successfully",
  "data": {
    "id": 2,
    "fullName": "Jane Smith",
    "email": "jane.smith@example.com"
  }
}
```

---

## 8. Pet Profiles Endpoints

**Base Path:** `/api/pet-profiles`  
**Auth Required:** Yes (all endpoints)  
**Minimum Role:** `ROLE_USER` (read/write own), `ROLE_STAFF` (read all)

### GET `/api/pet-profiles` - List Pet Profiles

**Query Parameters:**
```
?petOwnerId=1&species=Dog&sortBy=name
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Pet profiles fetched successfully",
  "data": [
    {
      "id": 1,
      "name": "Buddy",
      "species": "Dog",
      "breed": "Golden Retriever",
      "age": 3,
      "weight": 32.5,
      "color": "Golden",
      "microchipId": "987654321",
      "petOwner": {
        "id": 1,
        "fullName": "John Doe"
      },
      "profileImage": "buddy.jpg",
      "healthNotes": "Allergic to chicken",
      "lastCheckup": "2024-02-15T10:00:00+00:00"
    }
  ]
}
```

---

### POST `/api/pet-profiles` - Create Pet Profile

**Request Body:**
```json
{
  "name": "Whiskers",
  "species": "Cat",
  "breed": "Persian",
  "age": 2,
  "weight": 4.5,
  "color": "White",
  "microchipId": "123456789",
  "petOwnerId": 1,
  "healthNotes": "Indoor cat, no allergies"
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Pet profile created successfully",
  "data": {
    "id": 2,
    "name": "Whiskers",
    "species": "Cat",
    "breed": "Persian"
  }
}
```

---

## 9. Contact Message Endpoints

**Base Path:** `/api/contact`  
**Auth Required:** POST (No), GET/DELETE (Yes)  
**Minimum Role:** `ROLE_USER` (read own), `ROLE_ADMIN` (delete)

### POST `/api/contact` - Submit Contact Message

**Auth Required:** No

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "subject": "Product Inquiry",
  "message": "I would like more information about dog food premium",
  "phoneNumber": "+1234567890"
}
```

**Validation:**
- `name`: required, 1-100 characters
- `email`: required, valid email
- `subject`: required, 1-200 characters
- `message`: required, 10-5000 characters
- `phoneNumber`: optional

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Thank you for your message. We will get back to you soon.",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "subject": "Product Inquiry",
    "status": "new",
    "createdAt": "2024-03-10T15:30:00+00:00"
  }
}
```

---

### GET `/api/contact` - List Contact Messages

**Auth Required:** Yes  
**Minimum Role:** `ROLE_ADMIN`

**Query Parameters:**
```
?page=1&limit=20&status=new&sortBy=createdAt
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "subject": "Product Inquiry",
      "message": "I would like more information about dog food premium",
      "phoneNumber": "+1234567890",
      "status": "new",
      "createdAt": "2024-03-10T15:30:00+00:00"
    }
  ]
}
```

---

## API Response Standards

### Success Response Format
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { },
  "pagination": { },
  "meta": { },
  "timestamp": "2024-03-10T15:30:00+00:00"
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "code": "ERROR_CODE",
  "errors": {
    "field": ["error message"]
  },
  "timestamp": "2024-03-10T15:30:00+00:00"
}
```

### Response Headers
```
Content-Type: application/json
X-Total-Items: 25
X-Total-Pages: 2
X-Current-Page: 1
X-Items-Per-Page: 20
```

---

## Rate Limiting

- **Limit:** 100 requests per minute per IP
- **Header:** `X-RateLimit-Remaining: 99`
- **Error Response (429 Too Many Requests):**

```json
{
  "success": false,
  "message": "Rate limit exceeded. Please try again later.",
  "code": "RATE_LIMIT_EXCEEDED",
  "retryAfter": 60
}
```

---

## CORS & Security Headers

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000
```

---

## Role Hierarchy & Permissions

```
ROLE_ADMIN (highest)
  ├── ROLE_STAFF
  │   └── ROLE_USER
  │       └── Anonymous
```

| Action | Anonymous | USER | STAFF | ADMIN |
|--------|-----------|------|-------|-------|
| Read public products | ✅ | ✅ | ✅ | ✅ |
| Read own profile | ❌ | ✅ | ✅ | ✅ |
| Create order | ❌ | ✅ | ✅ | ✅ |
| Manage customers | ❌ | ❌ | ✅ | ✅ |
| Manage products | ❌ | ❌ | ✅ | ✅ |
| Delete resources | ❌ | ❌ | ❌ | ✅ |
| Manage staff | ❌ | ❌ | ❌ | ✅ |

---

## Testing the API

### Using cURL

```bash
# Get JWT Token
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}' | jq -r '.token')

# Get all customers
curl -X GET http://localhost:8000/api/customers \
  -H "Authorization: Bearer $TOKEN"

# Create new customer
curl -X POST http://localhost:8000/api/customers \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName":"Jane",
    "lastName":"Smith",
    "email":"jane@example.com",
    "phoneNumber":"+1987654321"
  }'
```

### Using Postman

1. Import `Customer_API_Postman_Collection.json`
2. Set environment variables:
   - `BASE_URL`: `http://localhost:8000`
   - `TOKEN`: (auto-populated after login)
3. Run requests with pre-configured auth

### Using JavaScript/Fetch

```javascript
// Get token
const response = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@example.com',
    password: 'password123'
  })
});

const { token } = await response.json();

// Use token
const customersResponse = await fetch('http://localhost:8000/api/customers', {
  headers: { 'Authorization': `Bearer ${token}` }
});

const customers = await customersResponse.json();
```

---

## Troubleshooting

### Common Issues

**401 Unauthorized - Invalid Token**
- Token has expired → Re-authenticate
- Token is malformed → Check JWT format
- Token missing Bearer prefix → Use `Authorization: Bearer TOKEN`

**403 Forbidden - Insufficient Permissions**
- User lacks required role → Check role in `/api/users/me`
- Admin-only endpoint → Use admin account

**404 Not Found**
- Resource doesn't exist → Check the ID
- Wrong endpoint path → Review documentation

**422 Unprocessable Entity**
- Validation failed → Check response `errors` field
- Required field missing → Review request body

**500 Internal Server Error**
- Database error → Check server logs
- Configuration issue → Verify `.env` settings

---

## Support & Feedback

- **Documentation:** Check `API_DOCUMENTATION_COMPLETE.md`
- **Issues:** Open GitHub issue with endpoint name and response
- **Contact:** support@khriciadivino.example.com

---

**End of API Documentation**
