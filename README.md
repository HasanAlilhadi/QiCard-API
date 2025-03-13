# Laravel User Management System for QiCard

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
- `CheckRole` - Verifies user has required role(s)
- `CheckPermission` - Verifies user has required permission(s)

## Project Setup

### Prerequisites

- PHP 8.1 or higher
- Laravel 11.x
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
- **Password**: 1234
- **Role**: super_admin

#### Default Permissions

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

#### Default Roles

The system includes one pre-configured role:

- **super_admin**: Has all system permissions and is marked as a system role (`is_system_role=true`), which prevents it from being deleted.

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

### Extending Audit Logging

The AuditService can be extended to log additional entity types:

1. Add new methods to `AuditService.php`
2. Follow the pattern of existing methods
3. Call your new methods from relevant controllers
