<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Bot, Check, History, LoaderCircle, Sparkles } from '@lucide/vue';
import { Button } from '@/components/ui/button';

interface SuggestionRecord {
    id: number;
    status: string;
    model?: string | null;
    suggestions?: Record<string, any> | null;
    local_analysis?: Record<string, any> | null;
    error_message?: string | null;
    total_tokens?: number | null;
    requested_by?: string | null;
    created_at?: string | null;
    reviewed_at?: string | null;
}

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
}>();

const confirming = ref(false);
const adultConfirmed = ref(false);
const nonSexualConfirmed = ref(false);
const generating = ref(false);
const applying = ref(false);
const selected = ref<string[]>([]);
const keywordMode = ref<'replace' | 'append'>('replace');
const selectedKeywords = ref<string[]>([]);

const latest = computed(() => props.history.find((item) => item.status === 'completed') ?? props.history[0] ?? null);
const suggestions = computed(() => latest.value?.suggestions ?? {});
const colors = computed(() => latest.value?.local_analysis?.dominant_colors ?? []);
const suggestedKeywords = computed<string[]>(() => Array.isArray(suggestions.value.keywords) ? suggestions.value.keywords : []);

function toggleKeyword(keyword: string): void {
    selectedKeywords.value = selectedKeywords.value.includes(keyword)
        ? selectedKeywords.value.filter((item) => item !== keyword)
        : [...selectedKeywords.value, keyword];
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
    if (!adultConfirmed.value || !nonSexualConfirmed.value) return;
    generating.value = true;
    router.post(`/admin/assets/${props.assetId}/ai-suggestions`, {
        adult_content_confirmed: adultConfirmed.value,
        non_sexual_content_confirmed: nonSexualConfirmed.value,
    }, {
        preserveScroll: true,
        onFinish: () => { generating.value = false; confirming.value = false; },
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

function apply(): void {
    if (!latest.value || selected.value.length === 0) return;
    applying.value = true;
    router.post(`/admin/assets/${props.assetId}/ai-suggestions/${latest.value.id}/apply`, {
        fields: selected.value,
        keyword_mode: keywordMode.value,
        keyword_names: selectedKeywords.value,
    }, {
        preserveScroll: true,
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
                <span>{{ latest.model }}</span><span>•</span><span>{{ latest.total_tokens ?? 0 }} tokens</span>
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

            <div v-if="selected.includes('keywords')" class="space-y-3 rounded-xl border bg-muted/20 p-4 text-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-medium">Apply keywords:</span>
                    <label><input v-model="keywordMode" type="radio" value="replace" /> Replace existing</label>
                    <label><input v-model="keywordMode" type="radio" value="append" /> Add to existing</label>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label v-for="keyword in suggestedKeywords" :key="keyword" class="flex cursor-pointer items-center gap-2 rounded-full border bg-background px-3 py-1.5">
                        <input type="checkbox" :checked="selectedKeywords.includes(keyword)" @change="toggleKeyword(keyword)" />
                        <span>{{ keyword }}</span>
                    </label>
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
            <p class="mt-1 text-sm text-muted-foreground">A reduced marketplace preview will be sent to the configured AI provider. Confirm both statements.</p>
            <div class="mt-4 space-y-3 text-sm">
                <label class="flex items-start gap-2"><input v-model="adultConfirmed" type="checkbox" class="mt-1" /><span>All visible people are confirmed consenting adults.</span></label>
                <label class="flex items-start gap-2"><input v-model="nonSexualConfirmed" type="checkbox" class="mt-1" /><span>The asset depicts non-sexual content and contains no sexual activity.</span></label>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <Button type="button" variant="outline" @click="confirming = false">Cancel</Button>
                <Button type="button" :disabled="!adultConfirmed || !nonSexualConfirmed || generating" @click="generate">Analyze Preview</Button>
            </div>
        </div>
    </section>
</template>
