# Laravel User Management System

A comprehensive user management system built with Laravel, featuring role-based access control (RBAC), permissions, and detailed audit logging.

## Project Overview

This system provides a robust authentication and authorization framework with the following key features:

- User authentication with Laravel Sanctum
- Role-based access control using Spatie Permission
- Granular permission management
- Complete audit logging of all system activities
- Soft deletion for data recovery
- API-first architecture

## System Architecture

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

### Key Components

#### Authentication

- Uses Laravel Sanctum for token-based API authentication
- Implements proper password hashing and security measures
- Includes rate limiting to prevent brute force attacks

#### Authorization

- Implements Spatie Permission package for RBAC
- Provides middleware for role and permission checks
- Supports both role-based and direct permission assignments
- Includes system roles/permissions that cannot be deleted

#### Audit Logging

- Comprehensive audit logging for all system actions
- Records previous and new states for all data changes
- Captures IP address and user agent information
- Logs security violations and suspicious activities

#### Middleware

- `Authentication` - Ensures users are authenticated
- `CheckRole` - Verifies user has required role
- `CheckPermission` - Verifies user has required permission

## Project Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL or PostgreSQL

### Installation Steps

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd <project-folder>
   ```

2. Install PHP dependencies:
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
- **Password**: 1234 (change this in production!)
- **Role**: super_admin

## API Endpoints

### Authentication

- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user details
- `PUT /api/auth/update` - Update current user's profile

### User Management

- `GET /api/users` - List all users
- `GET /api/users/{id}` - Get user details
- `POST /api/users` - Create new user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `POST /api/users/{id}/roles` - Assign roles to user
- `POST /api/users/{id}/permissions` - Assign permissions to user

### Role Management

- `GET /api/roles` - List all roles
- `GET /api/roles/{id}` - Get role details
- `POST /api/roles` - Create new role
- `PUT /api/roles/{id}` - Update role
- `DELETE /api/roles/{id}` - Delete role

### Permission Management

- `GET /api/permissions` - List all permissions
- `POST /api/permissions` - Create new permission
- `PUT /api/permissions/{id}` - Update permission
- `DELETE /api/permissions/{id}` - Delete permission

### Audit Logs

- `GET /api/audit-logs` - View system audit logs



## Security Considerations

- All system roles and permissions are protected from deletion
- Users cannot self-assign roles or permissions
- All security violations are logged
- Password strength requirements enforced
- Rate limiting implemented on sensitive endpoints
- Users can only assign permissions they themselves possess

## Core System Design

- The system uses a single-guard authentication setup (web guard)
- Super admin users are protected and cannot be deleted through the API
- The application follows RESTful API architecture principles
- Complete server-side validation is implemented
- Data is processed in JSON format for all API requests

## Development Guidelines

### Adding New Permissions

1. Create a new permission record via the API or in the PermissionsSeeder
2. Group permissions logically by feature area
3. Update frontend to utilize the new permission

### Creating Custom Middleware

1. Create middleware class in `App\Http\Middleware`
2. Register middleware in `app/Http/Kernel.php`
3. Apply middleware to routes or controller methods

### Extending Audit Logging

The AuditService can be extended to log additional entity types:

1. Add new methods to `AuditService.php`
2. Follow the pattern of existing methods
3. Call your new methods from relevant controllers



## License

This project is licensed under the [MIT License](LICENSE.md).
