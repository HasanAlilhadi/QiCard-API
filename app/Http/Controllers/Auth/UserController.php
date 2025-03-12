<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Psy\debug;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $query = User::query()->with(['roles']);

        if ($request->has('dataTable')) {
            $data = $query->dataTable();

            if ($request->boolean('export')) {
                return $data;
            }

            UserResource::collection($data);
            return $this->success($data);
        }

        $users = $query->get();
        UserResource::collection($users);
        return $this->success($users);
    }

    public function show($id, Request $request)
    {
        $user = User::query()->with(['roles'])->find($id);

        $user = new UserResource($user);
        return $this->success($user);
    }

    public function create(Request $request)
    {
//        $user = User::create([
//            'name' => $request->get('name'),
//            'username' => $request->get('username'),
//            'password' => $request->get('password'),
//        ]);
//
//        return response()->json([
//            'message' => 'success',
//            'data' => $user,
//            'status' => 200
//        ]);

        return $this->success(['data']);
    }

    public function filterUsers()
    {
        return User::query()->select('id', 'username')->limit(50)->get();
    }

    public function bulkDelete(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:users,id'
            ]);

            DB::beginTransaction();

            // Delete the users
            $deletedCount = User::whereIn('id', $request->ids)->delete();

            DB::commit();

            return $this->success([
                'message' => "$deletedCount users have been deleted successfully",
                'count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to delete users: ' . $e->getMessage());
        }
    }
}
