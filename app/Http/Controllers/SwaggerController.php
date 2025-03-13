<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

/**
 * @OA\Info(
 *     title="User Management API for QiCard",
 *     version="1.0.0",
 *     description="API for managing users, roles, and permissions",
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="username", type="string", example="johndoe"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(
 *         property="roles",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Role")
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Permission")
 *     ),
 *     @OA\Property(property="creator", ref="#/components/schemas/UserBasic"),
 *     @OA\Property(property="updater", ref="#/components/schemas/UserBasic")
 * )
 *
 * @OA\Schema(
 *     schema="UserBasic",
 *     title="UserBasic",
 *     description="Basic user information without relations",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="username", type="string", example="johndoe"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Role",
 *     title="Role",
 *     description="Role model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="editor"),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Permission")
 *     ),
 *     @OA\Property(property="creator", ref="#/components/schemas/UserBasic"),
 *     @OA\Property(property="updater", ref="#/components/schemas/UserBasic")
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     title="Permission",
 *     description="Permission model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="create_posts"),
 *     @OA\Property(property="group", type="string", example="Posts"),
 *     @OA\Property(property="creator", ref="#/components/schemas/UserBasic"),
 *     @OA\Property(property="updater", ref="#/components/schemas/UserBasic")
 * )
 *
 * @OA\Schema(
 *     schema="AuditLog",
 *     title="AuditLog",
 *     description="Audit log model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="action", type="string", example="user_created"),
 *     @OA\Property(property="entity_type", type="string", example="user"),
 *     @OA\Property(property="entity_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="performed_by", type="integer", format="int64", example=1),
 *     @OA\Property(property="ip_address", type="string", example="127.0.0.1"),
 *     @OA\Property(property="user_agent", type="string", example="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"),
 *     @OA\Property(property="previous_state", type="object", example={"name": "Old Name", "username": "oldusername"}),
 *     @OA\Property(property="new_state", type="object", example={"name": "New Name", "username": "newusername"}),
 *     @OA\Property(property="additional_data", type="object", example={"key": "value"}),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="performer", ref="#/components/schemas/UserBasic")
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 * @OA\Tag(
 *     name="Roles",
 *     description="Role management endpoints"
 * )
 * @OA\Tag(
 *     name="Permissions",
 *     description="Permission management endpoints"
 * )
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="Audit log endpoints"
 * )
 */
class SwaggerController extends Controller
{
    // This controller does not need any methods as it's only used for Swagger annotations
}
