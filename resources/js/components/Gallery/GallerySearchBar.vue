<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import PublicSearchSuggestions from '@/components/Public/PublicSearchSuggestions.vue';
import type { PublicSearchSuggestion } from '@/types/publicSearch';

const model = defineModel<string>({ required: true });

const props = withDefaults(defineProps<{
    placeholder?: string;
    suggestions?: PublicSearchSuggestion[];
    suggestionEndpoint?: string;
}>(), {
    placeholder: 'Search images, categories, tags, collections, or photographers...',
    suggestions: () => [],
    suggestionEndpoint: '/images/search-suggestions',
});

const emit = defineEmits<{
    search: [];
    suggestion: [suggestion: PublicSearchSuggestion];
}>();

const focused = ref(false);
const liveSuggestions = ref<PublicSearchSuggestion[]>(props.suggestions);
const loading = ref(false);
const activeIndex = ref(-1);
const listboxId = `gallery-search-suggestions-${Math.random().toString(36).slice(2, 9)}`;
let timer: ReturnType<typeof setTimeout> | null = null;
let controller: AbortController | null = null;

const showSuggestions = computed(() => focused.value && (loading.value || liveSuggestions.value.length > 0 || model.value.trim().length > 0));
const activeDescendant = computed(() => activeIndex.value >= 0 ? `${listboxId}-option-${activeIndex.value}` : undefined);

watch(() => props.suggestions, (value) => {
    if (! focused.value) {
liveSuggestions.value = value;
}
});

watch(model, () => {
    activeIndex.value = -1;
    scheduleSuggestions();
});

onBeforeUnmount(() => {
    if (timer) {
clearTimeout(timer);
}

    controller?.abort();
});

function scheduleSuggestions(): void {
    if (! focused.value) {
return;
}

    if (timer) {
clearTimeout(timer);
}

    timer = setTimeout(loadSuggestions, 250);
}

async function loadSuggestions(): Promise<void> {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        const url = new URL(props.suggestionEndpoint, window.location.origin);

        if (model.value.trim()) {
url.searchParams.set('q', model.value.trim());
}

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
            credentials: 'same-origin',
        });

        if (! response.ok) {
throw new Error('Suggestion request failed');
}

        const payload = await response.json() as { suggestions?: PublicSearchSuggestion[] };
        liveSuggestions.value = payload.suggestions ?? [];
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
liveSuggestions.value = props.suggestions;
}
    } finally {
        loading.value = false;
    }
}

function selectSuggestion(suggestion: PublicSearchSuggestion): void {
    model.value = suggestion.value;
    focused.value = false;
    activeIndex.value = -1;
    emit('suggestion', suggestion);
}

function moveActive(direction: 1 | -1): void {
    if (! liveSuggestions.value.length) {
return;
}

    const next = activeIndex.value + direction;
    activeIndex.value = next < 0 ? liveSuggestions.value.length - 1 : next % liveSuggestions.value.length;
}

function submitOrSelect(): void {
    if (activeIndex.value >= 0 && liveSuggestions.value[activeIndex.value]) {
        selectSuggestion(liveSuggestions.value[activeIndex.value]);

        return;
    }

    focused.value = false;
    emit('search');
}

function closeSuggestions(): void {
    focused.value = false;
    activeIndex.value = -1;
}
</script>

<template>
    <div class="relative">
        <div class="flex items-center gap-2 rounded-full border border-white/25 bg-white p-2 shadow-2xl shadow-black/15 dark:border-stone-700 dark:bg-stone-900" role="search">
            <Search class="ml-3 h-5 w-5 shrink-0 text-stone-400" />

            <label for="gallery-search" class="sr-only">Search the digital asset library</label>
            <input
                id="gallery-search"
                v-model="model"
                type="search"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="showSuggestions"
                :aria-controls="listboxId"
                :aria-activedescendant="activeDescendant"
                :placeholder="placeholder"
                class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm text-stone-950 outline-none placeholder:text-stone-400 dark:text-white sm:text-base"
                @focus="focused = true; loadSuggestions()"
                @blur="closeSuggestions"
                @keydown.down.prevent="moveActive(1)"
                @keydown.up.prevent="moveActive(-1)"
                @keydown.enter.prevent="submitOrSelect"
                @keydown.esc.prevent="closeSuggestions"
            />

            <button v-if="model" type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-stone-500 transition hover:bg-stone-100 dark:hover:bg-stone-800" aria-label="Clear search" @click="model = ''; loadSuggestions()">
                <X class="h-4 w-4" />
            </button>

            <button type="button" class="inline-flex h-11 shrink-0 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90" @click="submitOrSelect">
                Search
            </button>
        </div>

        <PublicSearchSuggestions
            :suggestions="liveSuggestions"
            :visible="showSuggestions"
            :active-index="activeIndex"
            :loading="loading"
            :listbox-id="listboxId"
            @activate="activeIndex = $event"
            @select="selectSuggestion"
        />

        <p class="sr-only" aria-live="polite">
            {{ loading ? 'Loading search suggestions' : `${liveSuggestions.length} search suggestions available` }}
        </p>
    </div>
</template>
