# Customer API Documentation

## Authentication

### Login to get JWT Token

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
    "username": "user@example.com",
    "password": "your_password"
}
```

**Response:**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Example cURL:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin@example.com","password":"password"}'
```

---

## Customer API Endpoints

All customer endpoints require JWT authentication. Include the token in the Authorization header:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

### 1. Get All Customers

**Endpoint:** `GET /api/customers`

**Headers:**
- `Authorization: Bearer YOUR_JWT_TOKEN`

**Response:**
```json
{
    "success": true,
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
            "postalCode": "10001",
            "country": "USA",
            "registrationDate": "2024-01-15 10:30:00",
            "lastPurchaseDate": "2024-03-01 14:20:00"
        }
    ],
    "count": 1
}
```

**Example cURL:**
```bash
curl -X GET http://localhost:8000/api/customers \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

### 2. Get Single Customer

**Endpoint:** `GET /api/customers/{id}`

**Headers:**
- `Authorization: Bearer YOUR_JWT_TOKEN`

**Response:**
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
        "postalCode": "10001",
        "country": "USA",
        "registrationDate": "2024-01-15 10:30:00",
        "lastPurchaseDate": "2024-03-01 14:20:00"
    }
}
```

**Example cURL:**
```bash
curl -X GET http://localhost:8000/api/customers/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

### 3. Create Customer

**Endpoint:** `POST /api/customers`

**Headers:**
- `Authorization: Bearer YOUR_JWT_TOKEN`
- `Content-Type: application/json`

**Request Body:**
```json
{
    "firstName": "Jane",
    "lastName": "Smith",
    "email": "jane@example.com",
    "phoneNumber": "+1987654321",
    "address": "456 Oak Ave",
    "city": "Los Angeles",
    "postalCode": "90001",
    "country": "USA",
    "registrationDate": "2024-03-10 12:00:00",
    "lastPurchaseDate": null
}
```

**Response:**
```json
{
    "success": true,
    "message": "Customer created successfully",
    "data": {
        "id": 2,
        "firstName": "Jane",
        "lastName": "Smith",
        "fullName": "Jane Smith",
        "email": "jane@example.com"
    }
}
```

**Example cURL:**
```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Jane",
    "lastName": "Smith",
    "email": "jane@example.com",
    "phoneNumber": "+1987654321",
    "address": "456 Oak Ave",
    "city": "Los Angeles",
    "postalCode": "90001",
    "country": "USA"
  }'
```

---

### 4. Update Customer

**Endpoint:** `PUT /api/customers/{id}` or `PATCH /api/customers/{id}`

**Headers:**
- `Authorization: Bearer YOUR_JWT_TOKEN`
- `Content-Type: application/json`

**Request Body (partial update allowed with PATCH):**
```json
{
    "phoneNumber": "+1111111111",
    "city": "San Francisco",
    "lastPurchaseDate": "2024-03-10 15:30:00"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Customer updated successfully",
    "data": {
        "id": 2,
        "firstName": "Jane",
        "lastName": "Smith",
        "fullName": "Jane Smith",
        "email": "jane@example.com"
    }
}
```

**Example cURL:**
```bash
curl -X PATCH http://localhost:8000/api/customers/2 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phoneNumber": "+1111111111",
    "city": "San Francisco"
  }'
```

---

### 5. Delete Customer

**Endpoint:** `DELETE /api/customers/{id}`

**Headers:**
- `Authorization: Bearer YOUR_JWT_TOKEN`

**Response:**
```json
{
    "success": true,
    "message": "Customer deleted successfully"
}
```

**Example cURL:**
```bash
curl -X DELETE http://localhost:8000/api/customers/2 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Error Responses

### 401 Unauthorized
```json
{
    "code": 401,
    "message": "Invalid credentials."
}
```

### 400 Bad Request
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": [
        "Email is required",
        "First name cannot be blank"
    ]
}
```

### 404 Not Found
```json
{
    "success": false,
    "message": "Customer not found"
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Error creating customer: Database connection failed"
}
```

---

## Testing with Postman

1. **Get JWT Token:**
   - Create a POST request to `/api/login`
   - Set body to JSON with `username` and `password`
   - Save the returned `token`

2. **Use Token for API Calls:**
   - Add Authorization header to all requests
   - Type: Bearer Token
   - Token: Paste the JWT token from login

3. **Test CRUD Operations:**
   - GET all customers
   - POST create a new customer
   - GET single customer by ID
   - PUT/PATCH update customer
   - DELETE customer

---

## PowerShell Testing Examples

### 1. Login
```powershell
$loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/login" `
    -Method Post `
    -ContentType "application/json" `
    -Body '{"username":"admin@example.com","password":"password"}'

$token = $loginResponse.token
```

### 2. Get All Customers
```powershell
$headers = @{
    "Authorization" = "Bearer $token"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/customers" `
    -Method Get `
    -Headers $headers
```

### 3. Create Customer
```powershell
$customerData = @{
    firstName = "John"
    lastName = "Doe"
    email = "john@example.com"
    phoneNumber = "+1234567890"
    city = "New York"
    country = "USA"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/customers" `
    -Method Post `
    -Headers $headers `
    -ContentType "application/json" `
    -Body $customerData
```

---

## Notes

- JWT tokens expire after 1 hour (3600 seconds) by default
- All dates should be in format: `Y-m-d H:i:s` (e.g., "2024-03-10 15:30:00")
- Registration date defaults to current date/time if not provided
- Required fields: firstName, lastName, email
- Optional fields: phoneNumber, address, city, postalCode, country, lastPurchaseDate
