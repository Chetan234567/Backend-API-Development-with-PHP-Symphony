# Backend-API-Development-with-PHP-Symphony

A modern, scalable backend API built using Symfony 6+, following clean architecture, JWT-based authentication, role management, email verification, password reset, and a fully functional video management module.

## Tech Stack

| Feature | Technology |
|---------|------------|
|Language| PHP 8.x |
|Web Server | PHP built-in server / Nginx / Apache (depending on deployment) |
| Framework | Symfony 6+ |
|ORM | Doctrine ORM |
|Dependency Manager | Composer |
|Configuration Format |	YAML / ENV files |
|API Type |	REST API |
|Security |	Symfony Security Component / JWT (if implemented) |
|Migrations | Doctrine Migrations |
|Environment |	.env, .env.dev |
| Authentication | JWT (lexikJWTAuthenticationBundle) |
| Database | MySQL + Doctrine ORM |
| Mail Sending | Symfony Mailer |
| Password Hashing | Symfony Security |
| Routing | Attribute-Based Routing |

# Installation & Setup

## 1. Clone the project
```
git clone https://github.com/Chetan234567/Backend-API-Development-with-PHP-Symphony.git
cd Backend-API-Development-with-PHP-Symphony
```

## 2. Install dependencies
```
composer install
```

## 3. Configure environment
```
# Copy environment file
cp .env .env.local

# Edit .env.local with these values:
DATABASE_URL="mysql://root:@127.0.0.1:3306/wemotions"
MAILER_DSN=smtp://localhost
JWT_PASSPHRASE=your_passphrase
```

## 4. Generate JWT keys
```
php bin/console lexik:jwt:generate-keypair
```

## 5. Setup database
```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```
## base_url
```
http://localhost:8000
```

## 6. Start Development Server
```
php -S localhost:8000 -t public
```




# API Endpoints

##  Authentication

### 1️ Register User
**POST** `/api/register`

**Body:**
```json
{
  "email": "test@example.com",
  "password": "12345678",
  "username": "john"
}
```
### 2️ Login
**POST** `/api/login`

**Body:**
```json
{
    "email": "test@example.com",
    "password": "12345678"
}
```

**Returns:**
```json
{
    "token": "<jwt-token>",
    "refresh_token": "<refresh-token>"
}
```

### 3️ Get Profile
**GET** `/api/profile`

**Header:**
```
Authorization: Bearer <token>
```

**Returns:**
```json
{
    "id": 1,
    "email": "test@example.com",
    "username": "john",
    "isVerified": true
}

```
## Email Verification

### 4️ Verify Email
**GET** `/api/email/verify/{token}`

**Returns:**
```json
{
  "message": "Email verified successfully"
}
```

## Password Reset

### 5️ Forgot Password
**POST** `/api/password/forgot`

**Body:**
```json
{
  "email": "test@example.com"
}
```
**Returns:**

```json
{
  "message": "Password reset email sent successfully"
}
```
## 6️ Reset Password
### POST /api/password/reset

**Body:**

```json
{
  "token": "<reset-token>",
  "newPassword": "12345678"
}
```
**Returns:**

```json
{
  "message": "Password reset successfully"
}
```
## Video Management

### 7️ Get All Videos
**GET** `/api/videos`

**Header:**
Authorization: Bearer <token>

**Returns:**
```json
{
  "videos": [
    {
      "id": 1,
      "title": "Sample Video",
      "url": "https://example.com/video.mp4",
      "createdAt": "2024-01-01T00:00:00Z"
    }
  ]
}
```
### 8️ Get Single Video
**GET** `/api/videos/{id}`

**Header:**
```
Authorization: Bearer <token>
```

**Returns:**
```json
{
  "id": 1,
  "title": "Sample Video",
  "url": "https://example.com/video.mp4",
  "createdAt": "2024-01-01T00:00:00Z"
}
```
### 9️ Create Video
**POST** `/api/videos`

**Header:**
Authorization: Bearer <token>

**Body:**
```json
{
  "title": "New Video",
  "url": "https://example.com/new-video.mp4"
}
```
**Returns:**

```json
{
  "id": 2,
  "title": "New Video",
  "url": "https://example.com/new-video.mp4",
  "createdAt": "2024-01-01T00:00:00Z"
}
```
### 10 Update Video
**PUT** `/api/videos/{id}`

**Header:**
```
Authorization: Bearer <token>
```
**Body:**
```json
{
  "title": "Updated Video Title",
  "url": "https://example.com/updated-video.mp4"
}
```
**Returns:**
```
json
{
  "id": 1,
  "title": "Updated Video Title",
  "url": "https://example.com/updated-video.mp4",
  "createdAt": "2024-01-01T00:00:00Z"
}
```
### Delete Video
**DELETE** `/api/videos/{id}`

**Header:**
Authorization: Bearer <token>

**Returns:**
```json
{
  "message": "Video deleted successfully"
}
```
## Token Refresh

### Refresh Token
**POST** `/api/refresh-token`

**Body:**
```json
{
  "refresh_token": "<refresh-token>"
}
```
**Returns:**
```json
{
  "token": "<new-jwt-token>"
}
```
### Get All Users (Admin)
**GET** `/api/users`

**Header:**
Authorization: Bearer <token>

**Returns:**
```json
{
  "users": [
    {
      "id": 1,
      "email": "user1@example.com",
      "username": "user1",
      "isVerified": true
    }
  ]
}
```
### Update User Profile
**PUT** `/api/profile`

**Header:**
```
Authorization: Bearer <token>
```

**Body:**
```json
{
  "username": "newusername",
  "email": "newemail@example.com"
}
```
**Returns:**

```jjson
{
  "id": 1,
  "email": "nemail@example.com",
  "username": "nusername",
  "isVerified": true
}
```
### Logout
**POST** `/api/logout`

**Header:**
```
Authorization: Bearer <token>
```

**Returns:**
```json
{
  "message": "Successfully logged out"
}

🎉 DONE
