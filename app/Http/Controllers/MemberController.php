<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Resources\LoanResource;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    // POST /api/members
    public function store(StoreMemberRequest $request): MemberResource
    {
        return new MemberResource(Member::create($request->validated()));
    }

    // GET /api/members/{member}
    public function show(Member $member): MemberResource
    {
        return new MemberResource($member);
    }

    // GET /api/members/{member}/loans?status=active|returned
    public function loans(Request $request, Member $member): AnonymousResourceCollection
    {
        $loans = $member->loans()
            ->with('book')   // eager loading: 2 queries in total instead of 1 + N
            ->when($request->query('status') === 'active', fn ($q) => $q->active())
            ->when($request->query('status') === 'returned', fn ($q) => $q->whereNotNull('returned_at'))
            ->latest('borrowed_at')
            ->paginate(15)
            ->withQueryString();

        return LoanResource::collection($loans);
    }
}