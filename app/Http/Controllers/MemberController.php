<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;

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
}