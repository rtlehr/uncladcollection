<script setup lang="ts">
import {
    ArrowDown,
    ArrowRight,
    ArrowUp,
    Link2,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import type {
    AdminAssetFile,
    AdminAssetFileRelationship,
    AssetFileRelationshipTypeOption,
} from '@/types/adminAsset';

const props = defineProps<{
    files: AdminAssetFile[];
    relationshipTypes: AssetFileRelationshipTypeOption[];
    modelValue: AdminAssetFileRelationship[];
    processing?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: AdminAssetFileRelationship[]];
}>();

const availableFiles = computed(() =>
    props.files.filter((file) => file.is_active),
);

function addRelationship(): void {
    if (availableFiles.value.length < 2) {
        return;
    }

    const source = availableFiles.value[0];
    const target =
        availableFiles.value.find((file) => file.id !== source.id)
        ?? availableFiles.value[1];

    emit('update:modelValue', [
        ...props.modelValue,
        {
            id: null,
            source_asset_file_id: source.id,
            target_asset_file_id: target.id,
            relationship_type:
                props.relationshipTypes[0]?.value ?? 'represents',
            label: null,
            sort_order: (props.modelValue.length + 1) * 10,
            metadata: null,
        },
    ]);
}

function updateRelationship(
    index: number,
    patch: Partial<AdminAssetFileRelationship>,
): void {
    const next = props.modelValue.map((relationship, currentIndex) =>
        currentIndex === index
            ? {
                ...relationship,
                ...patch,
            }
            : relationship,
    );

    emit('update:modelValue', next);
}

function removeRelationship(index: number): void {
    emit(
        'update:modelValue',
        props.modelValue.filter((_, currentIndex) => currentIndex !== index),
    );
}

function move(index: number, offset: number): void {
    const target = index + offset;

    if (target < 0 || target >= props.modelValue.length) {
        return;
    }

    const next = [...props.modelValue];
    [next[index], next[target]] = [next[target], next[index]];

    emit(
        'update:modelValue',
        next.map((relationship, position) => ({
            ...relationship,
            sort_order: (position + 1) * 10,
        })),
    );
}

function fileLabel(fileId: number): string {
    const file = props.files.find((item) => item.id === fileId);

    if (!file) {
        return `File #${fileId}`;
    }

    return `${file.original_filename} (${file.extension.toUpperCase()})`;
}

function typeDescription(value: string): string | null {
    return (
        props.relationshipTypes.find((type) => type.value === value)
            ?.description ?? null
    );
}

function isDuplicate(
    relationship: AdminAssetFileRelationship,
    index: number,
): boolean {
    return props.modelValue.some(
        (candidate, candidateIndex) =>
            candidateIndex !== index
            && candidate.source_asset_file_id
                === relationship.source_asset_file_id
            && candidate.target_asset_file_id
                === relationship.target_asset_file_id
            && candidate.relationship_type
                === relationship.relationship_type,
    );
}
</script>

<template>
    <div class="space-y-5">
        <div
            class="flex flex-wrap items-start justify-between gap-4 rounded-xl border bg-muted/20 p-4"
        >
            <div>
                <h3 class="font-semibold">File Relationships</h3>
                <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                    Describe how files within this Asset relate to one another.
                    Relationships are directional: the source file performs the
                    selected relationship toward the target file.
                </p>
            </div>

            <Button
                type="button"
                :disabled="processing || availableFiles.length < 2"
                @click="addRelationship"
            >
                <Plus class="mr-2 h-4 w-4" />
                Add Relationship
            </Button>
        </div>

        <div
            v-if="availableFiles.length < 2"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            <Link2 class="mx-auto mb-3 h-8 w-8" />
            Add at least two active files before creating relationships.
        </div>

        <div v-else-if="modelValue.length" class="space-y-4">
            <article
                v-for="(relationship, index) in modelValue"
                :key="relationship.id ?? `new-${index}`"
                class="rounded-xl border bg-background p-4"
                :class="
                    relationship.source_asset_file_id
                        === relationship.target_asset_file_id
                        || isDuplicate(relationship, index)
                        ? 'border-destructive/50'
                        : ''
                "
            >
                <div
                    class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_190px_minmax(0,1fr)_auto]"
                >
                    <label class="text-sm">
                        <span
                            class="mb-1 block font-medium text-muted-foreground"
                        >
                            Source file
                        </span>
                        <select
                            :value="relationship.source_asset_file_id"
                            class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                            @change="
                                updateRelationship(index, {
                                    source_asset_file_id: Number(
                                        ($event.target as HTMLSelectElement).value,
                                    ),
                                })
                            "
                        >
                            <option
                                v-for="file in availableFiles"
                                :key="file.id"
                                :value="file.id"
                            >
                                {{ fileLabel(file.id) }}
                            </option>
                        </select>
                    </label>

                    <label class="text-sm">
                        <span
                            class="mb-1 block font-medium text-muted-foreground"
                        >
                            Relationship
                        </span>
                        <select
                            :value="relationship.relationship_type"
                            class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                            @change="
                                updateRelationship(index, {
                                    relationship_type:
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                })
                            "
                        >
                            <option
                                v-for="type in relationshipTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </option>
                        </select>
                    </label>

                    <label class="text-sm">
                        <span
                            class="mb-1 block font-medium text-muted-foreground"
                        >
                            Target file
                        </span>
                        <select
                            :value="relationship.target_asset_file_id"
                            class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                            @change="
                                updateRelationship(index, {
                                    target_asset_file_id: Number(
                                        ($event.target as HTMLSelectElement).value,
                                    ),
                                })
                            "
                        >
                            <option
                                v-for="file in availableFiles"
                                :key="file.id"
                                :value="file.id"
                            >
                                {{ fileLabel(file.id) }}
                            </option>
                        </select>
                    </label>

                    <div class="flex items-end justify-end gap-1">
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            :disabled="index === 0"
                            title="Move relationship up"
                            @click="move(index, -1)"
                        >
                            <ArrowUp class="h-4 w-4" />
                        </Button>

                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            :disabled="index === modelValue.length - 1"
                            title="Move relationship down"
                            @click="move(index, 1)"
                        >
                            <ArrowDown class="h-4 w-4" />
                        </Button>

                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            title="Remove relationship"
                            @click="removeRelationship(index)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div
                    class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                >
                    <span>{{ fileLabel(relationship.source_asset_file_id) }}</span>
                    <ArrowRight class="h-3.5 w-3.5" />
                    <strong class="text-foreground">
                        {{
                            relationshipTypes.find(
                                (type) =>
                                    type.value
                                    === relationship.relationship_type,
                            )?.label
                        }}
                    </strong>
                    <ArrowRight class="h-3.5 w-3.5" />
                    <span>{{ fileLabel(relationship.target_asset_file_id) }}</span>
                </div>

                <p
                    v-if="typeDescription(relationship.relationship_type)"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    {{ typeDescription(relationship.relationship_type) }}
                </p>

                <label class="mt-4 block text-sm">
                    <span
                        class="mb-1 block font-medium text-muted-foreground"
                    >
                        Optional label
                    </span>
                    <input
                        :value="relationship.label ?? ''"
                        class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        maxlength="255"
                        placeholder="Example: Includes source artwork and license guide"
                        @input="
                            updateRelationship(index, {
                                label:
                                    ($event.target as HTMLInputElement).value
                                    || null,
                            })
                        "
                    />
                </label>

                <p
                    v-if="
                        relationship.source_asset_file_id
                            === relationship.target_asset_file_id
                    "
                    class="mt-3 text-sm text-destructive"
                >
                    A file cannot be related to itself.
                </p>

                <p
                    v-if="isDuplicate(relationship, index)"
                    class="mt-3 text-sm text-destructive"
                >
                    This relationship is duplicated.
                </p>
            </article>
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
        >
            <Link2 class="mx-auto mb-3 h-8 w-8" />
            No file relationships have been configured.
        </div>
    </div>
</template>
