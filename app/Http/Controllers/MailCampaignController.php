<?php

namespace App\Http\Controllers;

use App\Enums\MailRecipientGroup;
use App\Http\Requests\MailCampaignRequest;
use App\Models\MailCampaign;
use App\Services\AuditLogger;
use App\Services\MailCampaignManager;
use App\Services\MailRecipientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailCampaignController extends Controller
{
    public function __construct(
        private readonly MailRecipientResolver $resolver,
        private readonly MailCampaignManager $manager,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', MailCampaign::class);

        return view('mail-campaigns.index', [
            'campaigns' => MailCampaign::query()
                ->with('creator')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MailCampaign::class);

        return view('mail-campaigns.create', [
            'groups' => MailRecipientGroup::cases(),
            'recipientCounts' => collect(MailRecipientGroup::cases())
                ->mapWithKeys(fn (MailRecipientGroup $group) => [
                    $group->value => $this->resolver->resolve($group)->count(),
                ]),
        ]);
    }

    public function store(MailCampaignRequest $request): RedirectResponse
    {
        $campaign = MailCampaign::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('mail_campaign.created', $request->user(), $campaign);

        return redirect()
            ->route('mail-campaigns.show', $campaign)
            ->with('status', 'Serienmail wurde als Entwurf gespeichert.');
    }

    public function show(MailCampaign $mailCampaign): View
    {
        $this->authorize('view', $mailCampaign);
        $mailCampaign->load(['creator', 'recipients']);

        return view('mail-campaigns.show', [
            'campaign' => $mailCampaign,
            'prospectiveCount' => $mailCampaign->status->value === 'draft'
                ? $this->resolver->resolve($mailCampaign->recipient_group)->count()
                : null,
        ]);
    }

    public function send(Request $request, MailCampaign $mailCampaign): RedirectResponse
    {
        $this->authorize('send', $mailCampaign);
        $campaign = $this->manager->send($mailCampaign, $request->user());

        return redirect()
            ->route('mail-campaigns.show', $campaign)
            ->with('status', 'Die Serienmail wurde an die Versandwarteschlange übergeben.');
    }
}
