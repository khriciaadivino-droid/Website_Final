# Google OAuth, Email Verification & Mobile API Setup Guide

## Overview
This guide covers the implementation of three major features:
1. **Google OAuth** - Functional Google Sign-in for automatic staff verification
2. **Email Verification** - Unified email verification for Web UI and API registrations
3. **Mobile API** - Standardized JSON REST endpoints for mobile consumption

## 1. Google OAuth Setup

### Prerequisites
- Google Cloud Console account
- Google OAuth 2.0 credentials (Client ID and Client Secret)

### Step 1: Get Google OAuth Credentials
1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API
4. Go to "Create Credentials" > "OAuth 2.0 Client ID"
5. Select "Web Application"
6. Add authorized redirect URIs:
   - `http://localhost:8000/oauth2/callback/google` (development)
   - `https://yourdomain.com/oauth2/callback/google` (production)
7. Copy the Client ID and Client Secret

### Step 2: Configure Environment Variables
Add to `.env.local`:
```
GOOGLE_OAUTH_CLIENT_ID=your_client_id_here
GOOGLE_OAUTH_CLIENT_SECRET=your_client_secret_here
```

### How It Works
- Users can click "Login with Google" on the login page
- New users are automatically created with `ROLE_STAFF` (as per requirement)
- Email is automatically verified upon Google login
- Existing users are linked to their Google account

### API Endpoint
- **Route**: `/oauth2/callback/google`
- **Type**: OAuth2 Callback (handled by Symfony security)
- **Automatic Verification**: Yes - Google users are auto-verified

---

## 2. Email Verification System

### Features
- **Unified Flow**: Works for both Web UI and API registrations
- **Verification Tokens**: 24-hour expiration
- **Database Status**: `verifiedAt` column reflects verification status
- **Re-send Capability**: Can resend verification emails if expired

### Web UI Registration Flow
1. User registers via `/register` form
2. Account created with `verifiedAt = NULL` (unverified)
3. Verification email sent to user's email address
4. User clicks email link to verify account
5. Can now login and access dashboard

### API Registration Flow
1. POST to `/api/register` with `email`, `full_name`, `password`
2. Returns user data with `verified: false`
3. Verification email sent
4. User sends verification token via POST `/api/verify-email`
5. Account marked as verified, can obtain JWT token

### Verification Email
- **From**: Khriciadivino <noreply@khriciadivino.com>
- **Template**: `templates/emails/verification.html.twig`
- **Link**: Includes 64-character cryptographic token
- **Expiration**: 24 hours

### Database Schema
```sql
-- New fields in user table
ALTER TABLE `user` ADD verified_at DATETIME NULL;
ALTER TABLE `user` ADD google_id VARCHAR(255) NULL UNIQUE;

-- New verification_token table
CREATE TABLE verification_token (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
)
```

---

## 3. Mobile API Endpoints

### Base URL
`http://localhost:8000/api` (development)

### Endpoint 1: Register User (Public)
**POST** `/api/register`

Request:
```json
{
    "email": "user@example.com",
    "full_name": "John Doe",
    "password": "securepassword123"
}
```

Response (201 Created):
```json
{
    "success": true,
    "message": "Registration successful. Please check your email to verify your account.",
    "data": {
        "id": 1,
        "email": "user@example.com",
        "full_name": "John Doe",
        "verified": false
    }
}
```

---

### Endpoint 2: Verify Email (Public)
**POST** `/api/verify-email`

Request:
```json
{
    "token": "verification_token_from_email"
}
```

Response (200 OK):
```json
{
    "success": true,
    "message": "Email verified successfully",
    "data": {
        "id": 1,
        "email": "user@example.com",
        "full_name": "John Doe",
        "verified": true
    }
}
```

---

### Endpoint 3: Current User Info (Protected)
**GET** `/api/users/me`

Headers:
```
Authorization: Bearer your_jwt_token
```

Response (200 OK):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "email": "user@example.com",
        "full_name": "John Doe",
        "roles": ["ROLE_USER"],
        "verified": true,
        "last_login": "2026-03-22 10:30:00",
        "created_at": "2026-03-22 09:15:00"
    }
}
```

---

### Endpoint 4: List Products (Public)
**GET** `/api/products`

Response (200 OK):
```json
{
    "success": true,
    "message": "Products retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Product Name",
            "description": "Product Description",
            "price": 29.99,
            "quantity": 100,
            "category": "Category Name",
            "image": "filename.jpg"
        },
        ...
    ],
    "count": 5
}
```

---

### Endpoint 5: Get Product by ID (Public)
**GET** `/api/products/{id}`

Response (200 OK):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Product Name",
        "description": "Product Description",
        "price": 29.99,
        "quantity": 100,
        "category": "Category Name",
        "image": "filename.jpg"
    }
}
```

---

## Installation & Migration Steps

### 1. Run Migrations
```bash
php bin/console doctrine:migrations:migrate
```

This will:
- Add `verified_at` DATETIME column to user table
- Add `google_id` VARCHAR column to user table
- Create `verification_token` table

### 2. Configure SMTP (Optional but Recommended)
Ensure your `.env.local` has Brevo SMTP configured:
```
MAILER_DSN=smtp://your_brevo_email@smtp-brevo.com:password@smtp-relay.brevo.com:587
```

### 3. Set Google OAuth Credentials
Add to `.env.local`:
```
GOOGLE_OAUTH_CLIENT_ID=your_id
GOOGLE_OAUTH_CLIENT_SECRET=your_secret
```

### 4. Update Login Page (Optional UI Enhancement)
Add a "Login with Google" button linking to:
```html
<a href="{{ path('app_oauth2_google_check') }}" class="btn btn-primary">
    Login with Google
</a>
```

---

## Testing the Features

### Test Google OAuth
1. Start the app: `symfony serve`
2. Visit login page
3. Include Google login button (update needed in template)
4. Click "Login with Google"
5. Authenticate with Google account
6. Verify redirected to dashboard with auto-verified status

### Test Email Verification (Web)
1. Go to `/register`
2. Fill in registration form
3. Check email (or app logs in dev) for verification link
4. Click verification link
5. Should redirect with success message
6. Can now login

### Test Email Verification (API)
```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "full_name": "Test User",
    "password": "password123"
  }'

# Verify (use token from email)
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d '{"token": "token_from_email"}'
```

### Test Mobile API Endpoints
```bash
# Get products
curl http://localhost:8000/api/products

# Get single product
curl http://localhost:8000/api/products/1

# Get current user (requires JWT token from login)
curl -H "Authorization: Bearer your_token" \
  http://localhost:8000/api/users/me
```

---

## Files Created/Modified

### New Files Created
- `src/Entity/VerificationToken.php` - Token entity for email verification
- `src/Repository/VerificationTokenRepository.php` - Token repository
- `src/Service/EmailVerificationService.php` - Email verification service
- `src/Controller/OAuthController.php` - Google OAuth callback handler
- `src/Controller/Api/AuthApiController.php` - Mobile API endpoints
- `templates/emails/verification.html.twig` - Email verification template
- `config/packages/knpu_oauth2_client.yaml` - OAuth2 client configuration
- `migrations/Version20260322100000.php` - User table migration
- `migrations/Version20260322100001.php` - VerificationToken table migration

### Modified Files
- `src/Entity/User.php` - Added `verifiedAt`, `googleId` fields
- `src/Controller/RegistrationController.php` - Updated for email verification
- `config/packages/security.yaml` - Added OAuth2 firewall and routes
- `.env` - Added Google OAuth placeholder variables

---

## Error Handling

All API endpoints return standardized JSON responses:

### Success Response (2xx)
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response (4xx/5xx)
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": "Specific field error"
    }
}
```

---

## Security Considerations

1. **Email Verification Tokens**
   - 64-character cryptographic tokens (random_bytes(32))
   - 24-hour expiration
   - Single-use (marked as used after verification)

2. **Google OAuth**
   - Credentials stored in secure .env.local
   - Redirect URI must be registered in Google Console
   - State parameter enabled for CSRF protection

3. **API Authentication**
   - Endpoints requiring auth use JWT tokens
   - Unverified users can register but cannot access protected routes
   - Rate limiting recommended for registration endpoint

---

## Troubleshooting

### Email not sending?
- Check Brevo SMTP credentials in `.env.local`
- Verify `MAILER_DSN` is set correctly
- Check PHP mail logs: `symfony logs`

### Google OAuth not working?
- Verify credentials in `.env.local`
- Check Google Console redirect URIs match exactly
- Ensure OpenSSL extension is enabled (`php -m | grep openssl`)

### Migrations failed?
- Check database connectivity: `php bin/console doctrine:database:create`
- Verify no conflicting migrations
- Manually run: `php bin/console doctrine:migrations:migrate --no-interaction`

---

## Next Steps

1. ✅ Implement Google OAuth (Staff auto-verification)
2. ✅ Implement Email Verification (Web + API)
3. ✅ Create Mobile API Endpoints (5+ endpoints)
4. **Pending**: Integration testing and user acceptance testing
5. **Pending**: Update login template with Google OAuth button
6. **Pending**: Configure Gmail/Email service for production

