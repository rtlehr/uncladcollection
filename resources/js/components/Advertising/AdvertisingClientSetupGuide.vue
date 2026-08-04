<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, ChevronDown } from '@lucide/vue';

type GuideStep = {
    title: string;
    description: string;
    href?: string;
    linkLabel?: string;
    instructions: string[];
    notes?: string[];
};

const steps: GuideStep[] = [
    {
        title: 'Create the Advertiser',
        description: 'Create the organization that will own campaigns, proposals, invoices, and portal access.',
        href: '/admin/advertisers/create',
        linkLabel: 'Create Advertiser',
        instructions: [
            'Open Advertisers and select Create Advertiser.',
            'Enter the advertiser organization name.',
            'Add the primary business contact and billing contact information.',
            'Add the business address and any useful internal notes.',
            'Save the advertiser record.',
        ],
        notes: [
            'Create one advertiser record per organization rather than creating a new record for every campaign.',
            'Use separate advertiser records when brands require separate contracts, portal access, or billing.',
        ],
    },
    {
        title: 'Add Advertiser Portal Members',
        description: 'Give the advertiser’s staff access to the advertiser portal with the appropriate responsibilities.',
        href: '/admin/advertisers',
        linkLabel: 'Open Advertisers',
        instructions: [
            'Open the advertiser record and select Edit Advertiser.',
            'Find the Portal Members section.',
            'Select an existing Unclad Collection user.',
            'Assign the appropriate portal role.',
            'Mark the primary owner when applicable.',
            'Save the advertiser.',
        ],
        notes: [
            'Advertiser Owner is normally used for the main customer contact.',
            'Billing Contact is appropriate for accounting staff.',
            'Campaign Manager can manage campaigns, creatives, performance, and proposals.',
            'Creative Contributor is intended for designers and content contributors.',
            'Report Viewer provides read-only access to campaign information and reporting.',
        ],
    },
    {
        title: 'Create the Sponsorship Lead',
        description: 'Add the sales opportunity and assign responsibility for follow-up.',
        href: '/admin/sponsorship-leads/create',
        linkLabel: 'Create Sponsorship Lead',
        instructions: [
            'Select or create the advertiser.',
            'Enter the opportunity or lead title.',
            'Choose the lead source and assign a sales owner.',
            'Enter the estimated value and probability.',
            'Set the target close date, current stage, and next follow-up date.',
            'Add useful notes and save the lead.',
        ],
        notes: [
            'Keep the stage current as the opportunity progresses.',
            'Use realistic probability values so pipeline forecasting remains useful.',
        ],
    },
    {
        title: 'Record Sales Activity and Follow-Up',
        description: 'Maintain a shared history of meaningful advertiser contact and next actions.',
        href: '/admin/sponsorship-leads',
        linkLabel: 'Open Sponsorship Pipeline',
        instructions: [
            'Open the sponsorship lead.',
            'Add the activity type and activity date.',
            'Record concise notes describing the interaction or outcome.',
            'Set the next follow-up date when another action is required.',
            'Save the activity.',
        ],
        notes: [
            'Record important calls, emails, meetings, objections, pricing discussions, and proposal requests.',
            'Do not keep the complete customer history only in personal email or private notes.',
        ],
    },
    {
        title: 'Select or Create a Sponsorship Package',
        description: 'Use a reusable package for standard offerings or prepare custom proposal items for unique work.',
        href: '/admin/sponsorship-packages',
        linkLabel: 'Open Sponsorship Packages',
        instructions: [
            'Review the active sponsorship packages.',
            'Confirm the package duration, pricing, creative allowance, billing terms, and included placements.',
            'Create a new package only when the offering will be reused.',
            'Use custom proposal line items for one-time arrangements.',
        ],
        notes: [
            'Do not reuse an old package code for a materially different offering.',
            'Confirm package pricing before using it in a proposal.',
        ],
    },
    {
        title: 'Check Placements and Advertising Inventory',
        description: 'Confirm that the requested placement, media type, dates, and capacity are available.',
        href: '/admin/ad-inventory',
        linkLabel: 'Review Ad Inventory',
        instructions: [
            'Confirm that the requested placement is active.',
            'Verify that the placement supports the intended image or video format.',
            'Check the requested campaign dates.',
            'Review current and future reservations for capacity conflicts.',
            'Confirm that the selected sponsorship package includes compatible placements.',
        ],
        notes: [
            'Review inventory before sending a proposal and again before converting an accepted proposal.',
            'Availability may change while a proposal is awaiting a response.',
        ],
    },
    {
        title: 'Create and Send the Sponsorship Proposal',
        description: 'Prepare the commercial offer and make it available to the advertiser for review.',
        href: '/admin/sponsorship-proposals/create',
        linkLabel: 'Create Sponsorship Proposal',
        instructions: [
            'Select the advertiser and related sponsorship lead.',
            'Select a sponsorship package when applicable.',
            'Enter the proposal title, proposed campaign dates, and expiration date.',
            'Add and review every proposal line item.',
            'Verify subtotal, discount, tax, total, terms, and notes.',
            'Save the proposal as Draft.',
            'Review the proposal, then use the available action to send or mark it as Sent.',
        ],
        notes: [
            'Material changes after sending should normally be handled through a revised proposal.',
            'Confirm placement availability and pricing immediately before sending.',
        ],
    },
    {
        title: 'Advertiser Reviews and Accepts the Proposal',
        description: 'The authorized advertiser contact reviews the offer through the advertiser portal.',
        instructions: [
            'Confirm that the correct advertiser portal members have access.',
            'Ask the advertiser to open the proposal from the advertiser portal.',
            'The advertiser reviews dates, placements, line items, discounts, taxes, totals, and terms.',
            'An authorized Advertiser Owner or Campaign Manager accepts or declines the proposal.',
            'Acceptance requires signer information and acknowledgment of the terms.',
        ],
        notes: [
            'The proposal must be Sent, unexpired, and belong to the advertiser organization.',
            'Acceptance records the signer, time, portal user, IP address, user agent, and acknowledgment.',
            'Acceptance does not automatically create or activate the campaign.',
        ],
    },
    {
        title: 'Convert the Accepted Proposal',
        description: 'Create the operational campaign, placement reservations, and invoice from the accepted offer.',
        href: '/admin/sponsorship-proposals',
        linkLabel: 'Open Sponsorship Proposals',
        instructions: [
            'Open the accepted proposal.',
            'Review the advertiser, campaign dates, placements, line items, and totals one final time.',
            'Select Convert Proposal.',
            'Confirm the resulting campaign, placement assignments, inventory reservations, and invoice.',
        ],
        notes: [
            'The system rechecks proposal status, dates, inventory, and capacity during conversion.',
            'A proposal can be converted only once.',
        ],
    },
    {
        title: 'Review the Advertising Campaign',
        description: 'Confirm that the converted campaign contains the correct schedule, goals, placement, and commercial values.',
        href: '/admin/ad-campaigns',
        linkLabel: 'Open Ad Campaigns',
        instructions: [
            'Open the newly created campaign.',
            'Verify the advertiser, campaign name, start date, and end date.',
            'Confirm the billing model, budget, contract value, impression goal, and click goal.',
            'Review assigned placements and the current campaign status.',
            'Correct eligible draft information before submission or approval.',
        ],
        notes: [
            'Campaign approval and invoice payment are separate controls.',
            'A paid invoice does not automatically approve or activate a campaign.',
        ],
    },
    {
        title: 'Add and Approve Campaign Creatives',
        description: 'Upload placement-ready creative files, review them, and complete the approval workflow.',
        href: '/admin/ad-campaigns',
        linkLabel: 'Open Ad Campaigns',
        instructions: [
            'Open the campaign and select Manage Creatives.',
            'Add a creative and select the intended placement.',
            'For an image, upload the source and use the image editor to crop and position it for the placement.',
            'For a video, upload the file to a compatible placement.',
            'Complete the headline, body copy, call-to-action, destination URL, and alternative text as applicable.',
            'Preview and save the creative.',
            'Submit the creative for approval.',
            'Approve it only after verifying dimensions, display quality, destination, accessibility, and site standards.',
        ],
        notes: [
            'The original image and placement-ready edited image are retained.',
            'Material changes to an approved creative should normally require another review.',
            'A campaign generally cannot deliver without an eligible approved creative.',
        ],
    },
    {
        title: 'Review, Issue, and Collect the Invoice',
        description: 'Verify billing against the accepted proposal and record payment through the supported workflow.',
        href: '/admin/advertising-invoices',
        linkLabel: 'Open Advertising Invoices',
        instructions: [
            'Open the invoice created during proposal conversion.',
            'Verify the advertiser, campaign, line items, dates, discount, tax, total, and payment terms.',
            'Issue the invoice when it is accurate.',
            'For offline payment, record the amount, date, method, reference, and notes.',
            'For online payment, the advertiser can use Stripe Checkout from the advertiser portal.',
            'Confirm the updated invoice balance and status after payment.',
        ],
        notes: [
            'Do not record a payment until receipt is verified.',
            'Partial payments and supported refunds update the invoice balance automatically.',
        ],
    },
    {
        title: 'Approve, Activate, and Schedule the Campaign',
        description: 'Complete the final eligibility checks before the advertisement is allowed to render publicly.',
        href: '/admin/ad-campaigns',
        linkLabel: 'Open Ad Campaigns',
        instructions: [
            'Confirm that the campaign has been approved.',
            'Confirm that the campaign is active and its schedule is correct.',
            'Verify that the placement is active and the inventory reservation remains valid.',
            'Confirm that at least one creative is approved, compatible, and available.',
            'Test the public placement after the campaign becomes eligible.',
        ],
        notes: [
            'Only eligible campaigns and creatives are delivered publicly.',
            'The delivery system enforces schedules, placement capacity, creative approval, and media compatibility.',
        ],
    },
    {
        title: 'Review Delivery and Performance',
        description: 'Monitor campaign results, billing, inventory, and renewal opportunities.',
        href: '/admin/analytics/campaigns',
        linkLabel: 'Open Advertising Analytics',
        instructions: [
            'Review impressions, unique viewers, clicks, and click-through rate.',
            'Compare delivery against the campaign schedule and goals.',
            'Review influenced buyers, orders, revenue, and revenue per viewer when available.',
            'Check invoice status and outstanding balances.',
            'Review placement utilization and future inventory.',
            'Record renewal or follow-up opportunities in the sponsorship pipeline.',
        ],
        notes: [
            'Influenced revenue is an attribution signal and does not prove that an advertisement directly caused a purchase.',
            'Review active campaigns regularly rather than waiting until the end date.',
        ],
    },
];
</script>

<template>
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold tracking-tight">Advertising Client Setup Guide</h2>
            <p class="mt-1 text-sm leading-6 text-muted-foreground">
                Follow these steps to take a new advertising client from initial setup through campaign delivery and reporting.
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card">
            <details
                v-for="(step, index) in steps"
                :key="step.title"
                class="group border-b last:border-b-0"
            >
                <summary
                    class="flex cursor-pointer list-none items-start gap-4 px-5 py-4 outline-none transition hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring [&::-webkit-details-marker]:hidden"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border bg-background text-sm font-semibold">
                        {{ index + 1 }}
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ step.title }}</span>
                        <span class="mt-1 block text-sm leading-5 text-muted-foreground">
                            {{ step.description }}
                        </span>
                    </span>

                    <ChevronDown
                        class="mt-1 h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>

                <div class="border-t bg-muted/15 px-5 py-5 sm:pl-17">
                    <ol class="space-y-3">
                        <li
                            v-for="(instruction, instructionIndex) in step.instructions"
                            :key="instruction"
                            class="flex gap-3 text-sm leading-6"
                        >
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                {{ instructionIndex + 1 }}
                            </span>
                            <span>{{ instruction }}</span>
                        </li>
                    </ol>

                    <div v-if="step.notes?.length" class="mt-5 rounded-lg border bg-background p-4">
                        <p class="text-sm font-semibold">Important notes</p>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="note in step.notes"
                                :key="note"
                                class="flex gap-2 text-sm leading-6 text-muted-foreground"
                            >
                                <CheckCircle2 class="mt-1 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                <span>{{ note }}</span>
                            </li>
                        </ul>
                    </div>

                    <Link
                        v-if="step.href && step.linkLabel"
                        :href="step.href"
                        class="mt-5 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        {{ step.linkLabel }}
                    </Link>
                </div>
            </details>
        </div>
    </section>
</template>
