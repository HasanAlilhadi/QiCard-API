# Laravel User Management System for QiCard

A comprehensive Laravel-based REST API for managing users, roles, and permissions with detailed audit logging.

## Project Overview

This system provides a robust authentication and authorization framework with the following key features:

- Token-based API authentication with Laravel Sanctum
- Role-based access control (RBAC) using Spatie Permission
- Granular permission management
- Complete audit logging of all system activities
- API-first architecture with OpenAPI/Swagger documentation
- Rate limiting for API endpoints
- Security features (token management, system role protection)
- Soft deletion for data recovery

## System Architecture

### Controllers

- **AuthController**: Handles authentication (login, logout, profile)
- **UserController**: User CRUD operations and role/permission assignment
- **RoleController**: Role management
- **PermissionController**: Permission management
- **AuditLogController**: Access to audit trail

### Models

- **User**: Extends Laravel's Authenticatable with role/permission capabilities
- **AuditLog**: Stores detailed audit information
- **Role & Permission**: Extended from Spatie's package

### Middleware

- **Authentication**: Ensures users are authenticated
- **CheckRole**: Verifies user has required role(s)
- **CheckPermission**: Verifies user has required permission(s)

### Services

- **AuditService**: Centralized logging for all system operations

### Database Structure

The system uses the following database tables:

1. `users` - Stores user account information
2. `roles` - Defines user roles in the system
3. `permissions` - Defines granular permissions
4. `model_has_roles` - Maps roles to users (polymorphic)
5. `model_has_permissions` - Maps permissions directly to users (polymorphic)
6. `role_has_permissions` - Maps permissions to roles
7. `personal_access_tokens` - Stores Sanctum API tokens
8. `audit_logs` - Records all system activities

## Entity Relationships

- **Users** can have multiple **Roles**
- **Roles** can have multiple **Permissions**
- **Users** can have direct **Permissions**
- All operations are tracked in **AuditLogs**

## Setup Instructions

### Requirements

- PHP 8.1 or higher
- Laravel 11.x
- Composer
- MySQL or PostgreSQL

### Installation

1. Clone the repository:

   ```bash
   git clone <repository-url>
   cd <project-folder>
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Create and configure the environment file:

   ```bash
   cp .env.example .env
   ```

   Edit the `.env` file and configure your database connection.

4. Generate application key:

   ```bash
   php artisan key:generate
   ```

5. Run database migrations and seed initial data:

   ```bash
   php artisan migrate --seed
   ```

6. Configure Sanctum (for API authentication):

   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

7. Configure Spatie Permission:

   ```bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

### Default Access

After running the migrations and seeders, a default admin user will be created:

- **Username**: admin
- **Password**: 1234
- **Role**: super_admin

**Important**: Change the default password immediately in production.

## API Documentation

This API is thoroughly documented using Swagger/OpenAPI. You can access the full API documentation at:

```
http://localhost:8000/api/documentation
```

The documentation provides detailed information about all endpoints, request parameters, response formats, and authentication requirements.

## API Endpoints

### Authentication

- `POST /api/auth/login` - Authenticate user and get token
- `POST /api/auth/logout` - Invalidate user token
- `GET /api/auth/me` - Get current user information
- `POST /api/auth/update` - Update user profile

### Users

- `GET /api/users` - List all users
- `GET /api/users/{id}` - Get user details
- `POST /api/users` - Create new user
- `POST /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `POST /api/users/{id}/roles` - Assign roles to user
- `POST /api/users/{id}/permissions` - Assign permissions to user

### Roles

- `GET /api/roles` - List all roles
- `GET /api/roles/{id}` - Get role details
- `POST /api/roles` - Create new role
- `POST /api/roles/{id}` - Update role
- `DELETE /api/roles/{id}` - Delete role

### Permissions

- `GET /api/permissions` - List all permissions
- `POST /api/permissions` - Create new permission
- `POST /api/permissions/{id}` - Update permission
- `DELETE /api/permissions/{id}` - Delete permission

### Audit Logs

- `GET /api/audit_logs` - List all audit logs

## Default Permissions

The system comes with the following pre-configured permissions, grouped by functionality:

**Users Group:**

- `show_users` - View user listings and details
- `create_users` - Create new user accounts
- `edit_users` - Modify existing user accounts
- `delete_users` - Remove user accounts

**Permissions Group:**

- `show_permissions` - View permission listings
- `create_permissions` - Create new permissions
- `edit_permissions` - Modify existing permissions
- `delete_permissions` - Remove permissions
- `assign_permissions` - Assign permissions to users or roles

**Roles Group:**

- `show_roles` - View role listings
- `create_roles` - Create new roles
- `edit_roles` - Modify existing roles
- `delete_roles` - Remove roles
- `assign_roles` - Assign roles to users

**AuditLogs Group:**

- `show_audit_logs` - View system audit logs

All of these are marked as system permissions (`is_system_permission=true`), which protects them from being deleted through the API.

## Default Roles

The system includes one pre-configured role:

- **super_admin**: Has all system permissions and is marked as a system role (`is_system_role=true`), which prevents it from being deleted.

## Rate Limiting

The API implements rate limiting to prevent abuse:

- Auth endpoints: 10 requests/minute
- User management: 30 requests/minute
- Role/Permission management: 30 requests/minute
- Audit logs: 30 requests/minute

## Security Features

- System roles and permissions cannot be deleted
- Users cannot assign roles/permissions to themselves
- All security violations are logged
- Password hashing
- Token-based authentication
- IP and user agent logging for authentication attempts
- Super admin users are protected and cannot be deleted through the API
- Users can only assign permissions they themselves possess

## Authentication Flow

1. Client sends credentials to `/api/auth/login`
2. Server responds with a token
3. Client includes token in the Authorization header for subsequent requests:

   ```
   Authorization: Bearer {token}
   ```

   **or**

   ```
   Authorization: {token}
   ```

4. Token is invalidated on logout or can expire based on configuration

## Response Format

API responses follow a consistent format:

### Success Response

```json
{
  "success": true,
  "message": "success",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message"
}
```

## Core System Design

- The system uses a single-guard authentication setup (API guard)
- The application follows RESTful API architecture principles
- Complete server-side validation is implemented
- Data is processed in JSON format for all API requests

## Development Guidelines

### Extending Audit Logging

The AuditService can be extended to log additional entity types:

1. Add new methods to `AuditService.php`
2. Follow the pattern of existing methods
3. Call your new methods from relevant controllers

## License

Made for **QiCard**

