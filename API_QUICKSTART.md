# Customer API - Quick Start Guide

## ✅ Installation Complete!

Your Symfony application now has a fully functional REST API with JWT authentication for managing customers.

## 🚀 What Was Created

### 1. JWT Authentication
- **Package:** LexikJWTAuthenticationBundle installed
- **Keys:** RSA key pair generated in `config/jwt/`
- **Configuration:** JWT settings in `config/packages/lexik_jwt_authentication.yaml`
- **Security:** Updated `security.yaml` with JWT firewall

### 2. Customer API Controller
- **File:** `src/Controller/Api/CustomerApiController.php`
- **Endpoints:**
  - `GET /api/customers` - List all customers
  - `GET /api/customers/{id}` - Get single customer
  - `POST /api/customers` - Create customer
  - `PUT/PATCH /api/customers/{id}` - Update customer
  - `DELETE /api/customers/{id}` - Delete customer

### 3. Authentication Endpoint
- `POST /api/login` - Get JWT token

## 📝 How to Use

### Step 1: Start the Server

The server is already running at `http://localhost:8000`

If you need to restart it:
```powershell
php -S localhost:8000 -t public
```

### Step 2: Get a JWT Token

You need to login with an existing user account. If you don't have one, create it through the web interface at `http://localhost:8000/register`

Then login via API:

```powershell
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" `
    -Method Post `
    -ContentType "application/json" `
    -Body '{"email":"your-email@example.com","password":"your-password"}'

$token = $response.token
Write-Host "Token: $token"
```

### Step 3: Use the Token for API Calls

```powershell
# Set your token
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

# Get all customers
Invoke-RestMethod -Uri "http://localhost:8000/api/customers" `
    -Method Get `
    -Headers $headers

# Create a customer
$customer = @{
    firstName = "John"
    lastName = "Doe"
    email = "john.doe@example.com"
    phoneNumber = "+1234567890"
    city = "New York"
    country = "USA"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/customers" `
    -Method Post `
    -Headers $headers `
    -Body $customer
```

### Step 4: Run the Test Script

We've created a comprehensive test script:

```powershell
# Edit test-api.ps1 and update the username and password
# Then run:
.\test-api.ps1
```

## 📚 Full Documentation

See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for complete API documentation including:
- All endpoints with examples
- Request/response formats
- Error handling
- cURL and PowerShell examples

## 🔧 Testing with Tools

### Postman
1. Import the API endpoints
2. Create a request to `/api/login` to get your token
3. Use the token in Authorization header (Bearer Token) for other requests

### cURL
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"your-email@example.com","password":"your-password"}'

# Use the token
curl -X GET http://localhost:8000/api/customers \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### VSCode REST Client
Create a file `.http`:
```http
### Login
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "username": "your-email@example.com",
  "password": "your-password"
}

### Get All Customers
GET http://localhost:8000/api/customers
Authorization: Bearer YOUR_TOKEN_HERE
```

## 🎯 API Features

✅ JWT Authentication with 1-hour token expiration  
✅ Full CRUD operations for customers  
✅ Input validation  
✅ JSON responses with consistent format  
✅ Error handling  
✅ RESTful design  

## 🔐 Security

- All API routes require JWT authentication
- Tokens expire after 1 hour
- Stateless authentication (no sessions)
- Secure password handling using Symfony's security system

## 📁 Important Files

- `src/Controller/Api/CustomerApiController.php` - API controller
- `config/packages/security.yaml` - Security configuration
- `config/packages/lexik_jwt_authentication.yaml` - JWT configuration
- `config/jwt/private.pem` - JWT private key (keep secret!)
- `config/jwt/public.pem` - JWT public key
- `API_DOCUMENTATION.md` - Full API documentation
- `test-api.ps1` - PowerShell test script

## ⚡ Next Steps

1. Create a user account if you don't have one
2. Test the login endpoint to get a JWT token
3. Use the token to test customer CRUD operations
4. Check out the full documentation in `API_DOCUMENTATION.md`
5. Integrate with your frontend application

## 🐛 Troubleshooting

**"JWT Token not found"**
- You forgot to include the Authorization header
- Add: `Authorization: Bearer YOUR_TOKEN`

**"Invalid credentials"**
- Check your username/password
- Make sure the user exists in the database

**"401 Unauthorized"**
- Your token may have expired (tokens last 1 hour)
- Login again to get a new token

**Server not responding**
- Make sure the PHP server is running: `php -S localhost:8000 -t public`
- Check if port 8000 is available

## 📞 Support

For issues or questions about the API:
1. Check the full documentation in `API_DOCUMENTATION.md`
2. Review the error messages - they're descriptive
3. Check the Symfony logs in `var/log/dev.log`

---

**Created:** March 10, 2026  
**Symfony Version:** 7.3  
**PHP Version:** 8.2+
