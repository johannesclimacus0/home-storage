<?php

namespace App\Http\Controllers\Api;

use App\Actions\Households\AddHouseholdMemberAction;
use App\Actions\Households\CreateHouseholdAction;
use App\Actions\Households\DeleteHouseholdAction;
use App\Actions\Households\LeaveHouseholdAction;
use App\Actions\Households\ListHouseholdMembersAction;
use App\Actions\Households\ListUserHouseholdsAction;
use App\Actions\Households\RemoveHouseholdMemberAction;
use App\Actions\Households\ShowHouseholdAction;
use App\Actions\Households\TransferHouseholdOwnershipAction;
use App\Actions\Households\UpdateHouseholdAction;
use App\DTO\Households\AddHouseholdMemberData;
use App\DTO\Households\CreateHouseholdData;
use App\DTO\Households\LeaveHouseholdData;
use App\DTO\Households\RemoveHouseholdMemberData;
use App\DTO\Households\TransferHouseholdOwnershipData;
use App\DTO\Households\UpdateHouseholdData;
use App\Enums\HouseholdRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Households\DeleteHouseholdRequest;
use App\Http\Requests\Households\LeaveHouseholdRequest;
use App\Http\Requests\Households\RemoveHouseholdMemberRequest;
use App\Http\Requests\Households\StoreHouseholdMemberRequest;
use App\Http\Requests\Households\StoreHouseholdRequest;
use App\Http\Requests\Households\TransferHouseholdOwnershipRequest;
use App\Http\Requests\Households\UpdateHouseholdRequest;
use App\Http\Requests\Households\ViewHouseholdRequest;
use App\Http\Resources\HouseholdDetailResource;
use App\Http\Resources\HouseholdMemberResource;
use App\Http\Resources\HouseholdMembershipResource;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
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

    public function show(
        ViewHouseholdRequest $request,
        Household $household,
        ShowHouseholdAction $action,
    ): HouseholdDetailResource {
        return new HouseholdDetailResource(
            $action->handle($household->uuid, $request->user()->getKey()),
        );
    }

    public function update(
        UpdateHouseholdRequest $request,
        Household $household,
        UpdateHouseholdAction $action,
    ): HouseholdDetailResource {
        return new HouseholdDetailResource($action->handle(new UpdateHouseholdData(
            uuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            name: $request->validated('name'),
        )));
    }

    public function destroy(
        DeleteHouseholdRequest $request,
        Household $household,
        DeleteHouseholdAction $action,
    ): HttpResponse {
        $action->handle($household->uuid, $request->user()->getKey());

        return response()->noContent();
    }

    public function members(
        ViewHouseholdRequest $request,
        Household $household,
        ListHouseholdMembersAction $action,
    ): AnonymousResourceCollection {
        return HouseholdMemberResource::collection(
            $action->handle($household->uuid, $request->user()->getKey()),
        );
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

    public function removeMember(
        RemoveHouseholdMemberRequest $request,
        Household $household,
        int $member,
        RemoveHouseholdMemberAction $action,
    ): HttpResponse {
        $action->handle(new RemoveHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            memberUserId: $member,
        ));

        return response()->noContent();
    }

    public function leave(
        LeaveHouseholdRequest $request,
        Household $household,
        LeaveHouseholdAction $action,
    ): JsonResponse {
        $result = $action->handle(new LeaveHouseholdData(
            uuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
        ));

        return response()->json([
            'data' => [
                'household_uuid' => $result->uuid,
                'household_deleted' => $result->householdDeleted,
                'new_owner_user_id' => $result->newOwnerUserId,
            ],
        ]);
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
