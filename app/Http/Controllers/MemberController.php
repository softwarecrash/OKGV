<?php

namespace App\Http\Controllers;

use App\Enums\MemberStatus;
use App\Enums\NumberSequenceType;
use App\Enums\UserRole;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NumberSequenceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(
        private readonly NumberSequenceManager $numberSequenceManager,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $members = Member::query()
            ->when(
                ! $request->user()->canViewAllMasterData(),
                fn ($query) => $query->where('user_id', $request->user()->id),
            )
            ->search($request->string('q')->trim()->toString())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
                fn ($query) => $query->where('status', '!=', MemberStatus::Archived->value),
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('members.index', [
            'members' => $members,
            'statuses' => MemberStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Member::class);

        return view('members.create', [
            'member' => new Member,
            'statuses' => MemberStatus::cases(),
            'users' => $this->availableTenantUsers(),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = DB::transaction(function () use ($request): Member {
            $data = $this->memberData($request);
            $data['member_number'] = $data['member_number']
                ?: $this->numberSequenceManager->next(
                    NumberSequenceType::Member,
                    $data['joined_at'],
                );
            $member = Member::create($data);
            AuditLogger::log('member.created', $request->user(), $member);

            return $member;
        });

        return redirect()->route('members.show', $member)
            ->with('status', 'Mitglied wurde angelegt.');
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        $member->load([
            'parcelTenancies' => fn ($query) => $query->with('parcel')->latest('starts_at'),
        ]);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        return view('members.edit', [
            'member' => $member,
            'statuses' => MemberStatus::cases(),
            'users' => $this->availableTenantUsers($member),
        ]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($this->memberData($request));
        AuditLogger::log('member.updated', $request->user(), $member, [
            'changed_fields' => array_keys($member->getChanges()),
        ]);

        return redirect()->route('members.show', $member)
            ->with('status', 'Mitglied wurde aktualisiert.');
    }

    public function archive(Request $request, Member $member): RedirectResponse
    {
        $this->authorize('archive', $member);

        $member->update([
            'status' => MemberStatus::Archived,
            'archived_at' => now(),
        ]);
        AuditLogger::log('member.archived', $request->user(), $member);

        return redirect()->route('members.index')
            ->with('status', 'Mitglied wurde archiviert.');
    }

    private function memberData(MemberRequest $request): array
    {
        $data = $request->validated();
        $data['archived_at'] = $data['status'] === MemberStatus::Archived->value
            ? now()
            : null;

        return $data;
    }

    private function availableTenantUsers(?Member $member = null)
    {
        return User::query()
            ->where('role', UserRole::Tenant)
            ->where(function ($query) use ($member): void {
                $query->whereDoesntHave('member');

                if ($member?->user_id) {
                    $query->orWhereKey($member->user_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
