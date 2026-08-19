<?php

namespace App\Http\Controllers\Api;

use App\Actions\Households\AddHouseholdMemberAction;
use App\Actions\Households\CreateHouseholdAction;
use App\Actions\Households\ListUserHouseholdsAction;
use App\Actions\Households\TransferHouseholdOwnershipAction;
use App\DTO\Households\AddHouseholdMemberData;
use App\DTO\Households\CreateHouseholdData;
use App\DTO\Households\TransferHouseholdOwnershipData;
use App\Enums\HouseholdRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Households\StoreHouseholdMemberRequest;
use App\Http\Requests\Households\StoreHouseholdRequest;
use App\Http\Requests\Households\TransferHouseholdOwnershipRequest;
use App\Http\Resources\HouseholdMembershipResource;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class HouseholdController extends Controller
{
    public function index(Request $request, ListUserHouseholdsAction $action): AnonymousResourceCollection
    {
        return HouseholdMembershipResource::collection(
            $action->handle($request->user()->getKey()),
        );
    }

    public function store(
        StoreHouseholdRequest $request,
        CreateHouseholdAction $action
    ): JsonResponse {
        $data = new CreateHouseholdData(
            userId: $request->user()->getKey(),
            name: $request->validated('name'),
        );

        return new HouseholdResource($action->handle($data))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function storeMember(
        StoreHouseholdMemberRequest $request,
        Household $household,
        AddHouseholdMemberAction $action,
    ): JsonResponse {
        $result = $action->handle(new AddHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            newMemberEmail: $request->validated('email'),
        ));

        return response()->json([
            'data' => [
                'household_uuid' => $result->uuid,
                'user_id' => $result->newMemberUserId,
                'role' => HouseholdRole::Member->value,
            ],
        ], Response::HTTP_CREATED);
    }

    public function transferOwnership(
        TransferHouseholdOwnershipRequest $request,
        Household $household,
        TransferHouseholdOwnershipAction $action
    ): JsonResponse {
        $result = $action->handle(new TransferHouseholdOwnershipData(
            uuid: $household->uuid,
            currentOwnerUserId: $request->user()->getKey(),
            newOwnerUserId: $request->integer('new_owner_user_id'),
        ));

        return response()->json([
            'data' => [
                'household_uuid' => $result->uuid,
                'owner_user_id' => $result->newOwnerUserId,
            ],
        ], Response::HTTP_OK);
    }
}
