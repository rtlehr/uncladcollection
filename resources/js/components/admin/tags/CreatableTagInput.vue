<script setup lang="ts">
import { Plus, X } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';

export interface TagOption {
    id?: number | null;
    name: string;
}

const props = withDefaults(defineProps<{
    modelValue: string[];
    options: TagOption[];
    placeholder?: string;
    maxTags?: number;
    disabled?: boolean;
}>(), {
    placeholder: 'Type a keyword and press Enter',
    maxTags: 50,
    disabled: false,
});

const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>();

const query = ref('');
const open = ref(false);
const highlighted = ref(0);
const input = ref<HTMLInputElement | null>(null);

const normalizedSelected = computed(() => new Set(props.modelValue.map((value) => value.toLocaleLowerCase())));
const matches = computed(() => {
    const needle = query.value.trim().toLocaleLowerCase();

    if (!needle) {
return [];
}

    return props.options
        .filter((option) => !normalizedSelected.value.has(option.name.toLocaleLowerCase()))
        .filter((option) => option.name.toLocaleLowerCase().includes(needle))
        .slice(0, 8);
});

const canCreate = computed(() => {
    const value = query.value.trim();

    return value.length > 0
        && props.modelValue.length < props.maxTags
        && !props.options.some((option) => option.name.toLocaleLowerCase() === value.toLocaleLowerCase())
        && !normalizedSelected.value.has(value.toLocaleLowerCase());
});

function add(name: string): void {
    const value = name.trim();

    if (!value || props.modelValue.length >= props.maxTags || normalizedSelected.value.has(value.toLocaleLowerCase())) {
return;
}

    emit('update:modelValue', [...props.modelValue, value]);
    query.value = '';
    highlighted.value = 0;
    open.value = false;
    nextTick(() => input.value?.focus());
}

function remove(name: string): void {
    emit('update:modelValue', props.modelValue.filter((value) => value !== name));
}

function commit(): void {
    if (matches.value[highlighted.value]) {
        add(matches.value[highlighted.value].name);

        return;
    }

    if (canCreate.value) {
add(query.value);
}
}

function onPaste(event: ClipboardEvent): void {
    const text = event.clipboardData?.getData('text') ?? '';

    if (!/[;,\n]/.test(text)) {
return;
}

    event.preventDefault();
    const incoming = text.split(/[;,\n]/).map((item) => item.trim()).filter(Boolean);
    const combined = [...props.modelValue];

    for (const item of incoming) {
        if (combined.length >= props.maxTags) {
break;
}

        if (!combined.some((value) => value.toLocaleLowerCase() === item.toLocaleLowerCase())) {
combined.push(item);
}
    }

    emit('update:modelValue', combined);
    query.value = '';
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open.value = true;
        highlighted.value = Math.min(highlighted.value + 1, Math.max(matches.value.length - 1, 0));
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commit();
    } else if (event.key === 'Backspace' && query.value === '' && props.modelValue.length) {
        remove(props.modelValue[props.modelValue.length - 1]);
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <div class="flex min-h-10 flex-wrap items-center gap-2 rounded-md border bg-background px-3 py-2 focus-within:ring-2 focus-within:ring-ring">
            <span v-for="tag in modelValue" :key="tag" class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-sm">
                {{ tag }}
                <button type="button" class="rounded-full p-0.5 hover:bg-background" :aria-label="`Remove ${tag}`" :disabled="disabled" @click="remove(tag)">
                    <X class="h-3.5 w-3.5" />
                </button>
            </span>
            <input ref="input" v-model="query" type="text" class="min-w-40 flex-1 border-0 bg-transparent p-0 text-sm outline-none" :placeholder="modelValue.length ? '' : placeholder" :disabled="disabled || modelValue.length >= maxTags" @focus="open = true" @input="open = true" @keydown="onKeydown" @paste="onPaste" />
        </div>

        <div v-if="open && query.trim()" class="absolute z-50 mt-1 w-full overflow-hidden rounded-md border bg-popover shadow-md">
            <button v-for="(option, index) in matches" :key="option.name" type="button" class="flex w-full px-3 py-2 text-left text-sm hover:bg-muted" :class="index === highlighted ? 'bg-muted' : ''" @mousedown.prevent="add(option.name)">
                {{ option.name }}
            </button>
            <button v-if="canCreate" type="button" class="flex w-full items-center gap-2 border-t px-3 py-2 text-left text-sm hover:bg-muted" @mousedown.prevent="add(query)">
                <Plus class="h-4 w-4" /> Create “{{ query.trim() }}”
            </button>
            <p v-if="matches.length === 0 && !canCreate" class="px-3 py-2 text-sm text-muted-foreground">No matching keywords.</p>
        </div>
        <p class="mt-1 text-xs text-muted-foreground">Select an existing keyword or press Enter to create one. Paste comma-separated keywords to add several.</p>
    </div>
</template>
