<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NetworkController extends Controller
{
    public function index()
    {
        return Inertia::render('Network/Network');
    }

    public function getDownlineData(Request $request)
    {
        // $upline_id = $request->upline_id;
        // $parent_id = $request->parent_id ?: 82;

        // if ($request->filled('search')) {
        //     $search = '%' . $request->input('search') . '%';
        //     $parent = User::whereNotIn('role', ['super-admin', 'admin'])
        //         ->where('name', 'LIKE', $search)
        //         ->orWhere('email', 'LIKE', $search)
        //         ->orWhere('id_no', 'LIKE', $search)
        //         ->first();

        //     $parent_id = $parent->id;
        //     $upline_id = $parent->upline_id;
        // }

        $parent_id = Auth::id();

        $parent = User::with(['directChildren:id,first_name,id_no,upline_id,role,hierarchyList'])
            ->whereNotIn('role', ['super-admin', 'admin'])
            ->select('id', 'first_name', 'id_no', 'upline_id', 'role', 'hierarchyList')
            ->find($parent_id);
        
        $upline_id = $parent->upline_id;

        $upline = $upline_id ? User::select('id', 'first_name', 'id_no', 'upline_id', 'role', 'hierarchyList')->find($upline_id) : null;

        $parent_data = $this->formatUserData($parent);
        $upline_data = $upline ? $this->formatUserData($upline) : null;

        $direct_children = $parent->directChildren->map(function ($child) {
            return $this->formatUserData($child);
        });

        return response()->json([
            'upline' => $upline_data,
            'parent' => $parent_data,
            'direct_children' => $direct_children,
        ]);
    }

    private function formatUserData($user)
    {
        if ($user->upline) {
            $upper_upline = $user->upline->upline;
        }

        return array_merge(
            $user->only(['id', 'first_name', 'id_no', 'upline_id', 'role']),
            [
                'name' => $user->first_name,
                'upper_upline_id' => $upper_upline->id ?? null,
                'level' => $this->calculateLevel($user->hierarchyList),
                'total_agent_count' => $this->getChildrenCount('agent', $user->id),
                'total_member_count' => $this->getChildrenCount('member', $user->id),
            ]
        );
    }

    private function calculateLevel($hierarchyList)
    {
        if (is_null($hierarchyList) || $hierarchyList === '') {
            return 0;
        }
        return substr_count($hierarchyList, '-') - 1;
    }

    private function getChildrenCount($role, $user_id): int
    {
        return User::where('role', $role)
            ->where('hierarchyList', 'like', '%-' . $user_id . '-%')
            ->count();
    }
}
