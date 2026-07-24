<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Ban, Bot, Check, History, LoaderCircle, Sparkles } from '@lucide/vue';
import { Button } from '@/components/ui/button';

interface SuggestionRecord {
    id: number;
    status: string;
    provider?: string | null;
    model?: string | null;
    suggestions?: Record<string, any> | null;
    local_analysis?: Record<string, any> | null;
    error_message?: string | null;
    total_tokens?: number | null;
    requested_by?: string | null;
    created_at?: string | null;
    reviewed_at?: string | null;
}

const emit = defineEmits<{
    applied: [values: Record<string, unknown>];
}>();

const props = defineProps<{
    assetId: number;
    enabled: boolean;
    current: {
        title: string;
        description?: string | null;
        alt_text?: string | null;
        seo_title?: string | null;
        seo_description?: string | null;
        keywords?: string[];
        dominant_colors?: string[];
        detected_objects?: string[];
    };
    history: SuggestionRecord[];
    providers: Array<{ value: string; label: string; model: string }>;
    defaultProvider: string;
}>();

const confirming = ref(false);
const nonSexualConfirmed = ref(false);
const generating = ref(false);
const applying = ref(false);
const selected = ref<string[]>([]);
const keywordMode = ref<'replace' | 'append'>('replace');
const selectedKeywords = ref<string[]>([]);
const locallyExcludedKeywords = ref<string[]>([]);
const excludingKeywords = ref<string[]>([]);
const selectedProvider = ref(props.providers.some((provider) => provider.value === props.defaultProvider)
    ? props.defaultProvider
    : (props.providers[0]?.value ?? props.defaultProvider));

const latest = computed(() => props.history.find((item) => item.status === 'completed') ?? props.history[0] ?? null);
const suggestions = computed(() => latest.value?.suggestions ?? {});
const colors = computed(() => latest.value?.local_analysis?.dominant_colors ?? []);
const suggestedKeywords = computed<string[]>(() => {
    const keywords = Array.isArray(suggestions.value.keywords) ? suggestions.value.keywords : [];

    return keywords.filter((keyword: string) => !locallyExcludedKeywords.value.includes(keyword));
});

const uncheckedKeywords = computed<string[]>(() =>
    suggestedKeywords.value.filter((keyword) => !selectedKeywords.value.includes(keyword)),
);

function toggleKeyword(keyword: string): void {
    selectedKeywords.value = selectedKeywords.value.includes(keyword)
        ? selectedKeywords.value.filter((item) => item !== keyword)
        : [...selectedKeywords.value, keyword];
}

function excludeKeyword(keyword: string): void {
    if (excludingKeywords.value.includes(keyword)) return;

    selectedKeywords.value = selectedKeywords.value.filter((item) => item !== keyword);
    locallyExcludedKeywords.value = [...locallyExcludedKeywords.value, keyword];
    excludingKeywords.value = [...excludingKeywords.value, keyword];

    router.post('/admin/ai-keyword-exclusions', {
        keyword,
        notes: 'Added directly from an AI keyword suggestion.',
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            locallyExcludedKeywords.value = locallyExcludedKeywords.value.filter((item) => item !== keyword);
        },
        onFinish: () => {
            excludingKeywords.value = excludingKeywords.value.filter((item) => item !== keyword);
        },
    });
}

function excludeUncheckedKeywords(): void {
    const keywords = [...uncheckedKeywords.value];
    if (keywords.length === 0) return;

    locallyExcludedKeywords.value = Array.from(new Set([
        ...locallyExcludedKeywords.value,
        ...keywords,
    ]));
    excludingKeywords.value = Array.from(new Set([
        ...excludingKeywords.value,
        ...keywords,
    ]));

    router.post('/admin/ai-keyword-exclusions/bulk', {
        keywords: keywords.join('\n'),
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            locallyExcludedKeywords.value = locallyExcludedKeywords.value.filter(
                (item) => !keywords.includes(item),
            );
        },
        onFinish: () => {
            excludingKeywords.value = excludingKeywords.value.filter(
                (item) => !keywords.includes(item),
            );
        },
    });
}

const fields = computed(() => [
    ['title', 'Title', suggestions.value.title],
    ['description', 'Description', suggestions.value.description],
    ['alt_text', 'Alternative text', suggestions.value.alt_text],
    ['seo_title', 'SEO title', suggestions.value.seo_title],
    ['seo_description', 'SEO description', suggestions.value.seo_description],
    ['keywords', 'Keywords', suggestions.value.keywords],
    ['dominant_colors', 'Dominant colors', colors.value],
    ['detected_objects', 'Visible objects', suggestions.value.objects],
].filter(([, , value]) => Array.isArray(value) ? value.length : Boolean(value)));

function generate(): void {
    if (!nonSexualConfirmed.value) return;
    generating.value = true;
    router.post(`/admin/assets/${props.assetId}/ai-suggestions`, {
        non_sexual_content_confirmed: nonSexualConfirmed.value,
        provider: selectedProvider.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            confirming.value = false;
            nonSexualConfirmed.value = false;
        },
        onFinish: () => {
            generating.value = false;
        },
    });
}

function toggle(field: string): void {
    if (field === 'keywords' && !selected.value.includes(field) && selectedKeywords.value.length === 0) {
        selectedKeywords.value = [...suggestedKeywords.value];
    }
    selected.value = selected.value.includes(field)
        ? selected.value.filter((item) => item !== field)
        : [...selected.value, field];
}

function appliedValues(): Record<string, unknown> {
    const values: Record<string, unknown> = {};

    for (const field of selected.value) {
        if (field === 'keywords') {
            values.keywords = keywordMode.value === 'append'
                ? Array.from(new Set([...(props.current.keywords ?? []), ...selectedKeywords.value]))
                : [...selectedKeywords.value];
            continue;
        }

        if (field === 'dominant_colors') {
            values.dominant_colors = [...colors.value];
            continue;
        }

        if (field === 'detected_objects') {
            values.detected_objects = Array.isArray(suggestions.value.objects)
                ? [...suggestions.value.objects]
                : [];
            continue;
        }

        values[field] = suggestions.value[field] ?? null;
    }

    return values;
}

function apply(): void {
    if (!latest.value || selected.value.length === 0) return;

    const values = appliedValues();

    // Give immediate feedback, then apply the same values again after the
    // redirect has finished. The second emit is intentional: Inertia can replace
    // page props during the POST response and otherwise restore stale control DOM.
    emit('applied', values);
    applying.value = true;

    router.post(`/admin/assets/${props.assetId}/ai-suggestions/${latest.value.id}/apply`, {
        fields: selected.value,
        keyword_mode: keywordMode.value,
        keyword_names: selectedKeywords.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => emit('applied', values),
        onFinish: () => { applying.value = false; selected.value = []; },
    });
}

function display(value: any): string {
    return Array.isArray(value) ? value.join(', ') : String(value ?? '');
}
</script>

<template>
    <section class="rounded-xl border bg-background p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex gap-3">
                <div class="rounded-lg border bg-muted/30 p-2"><Bot class="h-5 w-5" /></div>
                <div>
                    <h2 class="font-semibold">AI Asset Assistant</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Generate optional metadata suggestions from the marketplace image or primary preview. Nothing is applied automatically.</p>
                </div>
            </div>
            <Button type="button" :disabled="!enabled || generating" @click="confirming = true">
                <LoaderCircle v-if="generating" class="mr-2 h-4 w-4 animate-spin" />
                <Sparkles v-else class="mr-2 h-4 w-4" />
                {{ latest ? 'Regenerate Suggestions' : 'Get AI Suggestions' }}
            </Button>
        </div>

        <p v-if="!enabled" class="mt-4 rounded-lg border border-amber-300/50 bg-amber-50 p-3 text-sm text-amber-900">AI assistance is disabled in configuration.</p>

        <div v-if="latest?.status === 'failed'" class="mt-4 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
            {{ latest.error_message || 'AI analysis was unavailable. Manual metadata entry remains available.' }}
        </div>

        <div v-if="latest?.status === 'completed'" class="mt-5 space-y-4">
            <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                <History class="h-4 w-4" />
                <span>{{ latest.provider || 'AI' }}</span><span>•</span><span>{{ latest.model }}</span><span>•</span><span>{{ latest.total_tokens ?? 0 }} tokens</span>
                <span v-if="latest.requested_by">• requested by {{ latest.requested_by }}</span>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <button v-for="field in fields" :key="field[0]" type="button"
                    class="rounded-xl border p-4 text-left transition hover:bg-muted/30"
                    :class="selected.includes(String(field[0])) ? 'border-primary bg-primary/5' : ''"
                    @click="toggle(String(field[0]))">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium">{{ field[1] }}</p>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">{{ display(field[2]) }}</p>
                        </div>
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border">
                            <Check v-if="selected.includes(String(field[0]))" class="h-4 w-4" />
                        </span>
                    </div>
                </button>
            </div>

            <div v-if="suggestions.scene || suggestions.composition || suggestions.relationship_suggestions" class="grid gap-3 lg:grid-cols-3">
                <div v-if="suggestions.scene" class="rounded-xl border bg-muted/20 p-4">
                    <p class="text-sm font-medium">Scene</p>
                    <p class="mt-2 text-sm text-muted-foreground">{{ display(suggestions.scene) }}</p>
                </div>
                <div v-if="suggestions.composition" class="rounded-xl border bg-muted/20 p-4">
                    <p class="text-sm font-medium">Composition</p>
                    <p class="mt-2 text-sm text-muted-foreground">{{ display(suggestions.composition) }}</p>
                </div>
                <div v-if="suggestions.relationship_suggestions" class="rounded-xl border bg-muted/20 p-4">
                    <p class="text-sm font-medium">Relationship themes</p>
                    <p class="mt-2 text-sm text-muted-foreground">{{ display(suggestions.relationship_suggestions) }}</p>
                </div>
            </div>

            <div v-if="selected.includes('keywords')" class="space-y-3 rounded-xl border bg-muted/20 p-4 text-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-medium">Apply keywords:</span>
                    <label><input v-model="keywordMode" type="radio" value="replace" /> Replace existing</label>
                    <label><input v-model="keywordMode" type="radio" value="append" /> Add to existing</label>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="keyword in suggestedKeywords"
                        :key="keyword"
                        class="flex items-center overflow-hidden rounded-full border bg-background"
                    >
                        <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5">
                            <input
                                type="checkbox"
                                :checked="selectedKeywords.includes(keyword)"
                                @change="toggleKeyword(keyword)"
                            />
                            <span>{{ keyword }}</span>
                        </label>
                        <button
                            type="button"
                            class="flex self-stretch items-center border-l px-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive disabled:cursor-wait disabled:opacity-50"
                            :disabled="excludingKeywords.includes(keyword)"
                            :aria-label="`Exclude ${keyword} from future AI keyword suggestions`"
                            :title="`Exclude “${keyword}” from future AI suggestions`"
                            @click="excludeKeyword(keyword)"
                        >
                            <LoaderCircle
                                v-if="excludingKeywords.includes(keyword)"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <Ban v-else class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t pt-3">
                    <p class="text-xs text-muted-foreground">
                        Click the exclusion icon on a keyword to remove it now and block it from future AI suggestions.
                    </p>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="uncheckedKeywords.length === 0 || excludingKeywords.length > 0"
                        @click="excludeUncheckedKeywords"
                    >
                        <Ban class="mr-2 h-4 w-4" />
                        Exclude unchecked keywords ({{ uncheckedKeywords.length }})
                    </Button>
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="button" :disabled="selected.length === 0 || applying" @click="apply">
                    {{ applying ? 'Applying…' : `Apply Selected (${selected.length})` }}
                </Button>
            </div>
        </div>

        <div v-if="confirming" class="mt-5 rounded-xl border bg-muted/20 p-4">
            <h3 class="font-medium">Confirm before analysis</h3>
            <p class="mt-1 text-sm text-muted-foreground">A reduced marketplace preview will be sent to the configured AI provider. Confirm the statement below.</p>
            <div class="mt-4 space-y-4 text-sm">
                <label v-if="providers.length > 1" class="block">
                    <span class="mb-1 block font-medium">AI provider</span>
                    <select v-model="selectedProvider" class="h-10 w-full rounded-md border bg-background px-3 text-sm sm:max-w-sm">
                        <option v-for="provider in providers" :key="provider.value" :value="provider.value">
                            {{ provider.label }} — {{ provider.model }}
                        </option>
                    </select>
                    <span class="mt-1 block text-xs text-muted-foreground">If the selected provider fails, the configured fallback may be used automatically.</span>
                </label>
                <div v-else-if="providers[0]" class="rounded-lg border bg-background p-3">
                    <span class="font-medium">Provider:</span> {{ providers[0].label }} — {{ providers[0].model }}
                </div>
                <label class="flex items-start gap-2"><input v-model="nonSexualConfirmed" type="checkbox" class="mt-1" /><span>The asset depicts non-sexual content and contains no sexual activity.</span></label>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <Button type="button" variant="outline" @click="confirming = false">Cancel</Button>
                <Button type="button" :disabled="!nonSexualConfirmed || generating" @click="generate">Analyze Preview</Button>
            </div>
        </div>
    </section>
</template>
