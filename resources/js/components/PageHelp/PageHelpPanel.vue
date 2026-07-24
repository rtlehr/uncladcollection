<script setup lang="ts">
import { ExternalLink, HelpCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type HelpEntry = {
    id: number;
    title: string;
    summary?: string | null;
    content: string;
};

type PageHelpPayload = {
    key: string;
    page_name: string;
    entries: HelpEntry[];
    can_manage: boolean;
    manage_url?: string | null;
};

withDefaults(defineProps<{
    help: PageHelpPayload;
    publicStyle?: boolean;
}>(), {
    publicStyle: false,
});
</script>

<template>
    <Sheet>
        <TooltipProvider :delay-duration="200">
            <Tooltip>
                <TooltipTrigger as-child>
                    <SheetTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :class="publicStyle
                                ? 'h-10 w-10 rounded-full border border-stone-300 dark:border-stone-700'
                                : 'h-9 w-9'"
                            :aria-label="`Help for ${help.page_name}`"
                        >
                            <HelpCircle class="h-5 w-5" aria-hidden="true" />
                        </Button>
                    </SheetTrigger>
                </TooltipTrigger>
                <TooltipContent>Help for this page</TooltipContent>
            </Tooltip>
        </TooltipProvider>

        <SheetContent side="right" class="w-full overflow-y-auto p-0 sm:max-w-xl">
            <SheetHeader class="border-b px-6 py-5 text-left">
                <SheetTitle>{{ help.page_name }}</SheetTitle>
                <SheetDescription>
                    Contextual guidance for this page.
                </SheetDescription>
            </SheetHeader>

            <div class="space-y-6 px-6 py-6">
                <template v-if="help.entries.length">
                    <article
                        v-for="entry in help.entries"
                        :key="entry.id"
                        class="space-y-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold">{{ entry.title }}</h2>
                            <p
                                v-if="entry.summary"
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{ entry.summary }}
                            </p>
                        </div>

                        <div
                            class="prose prose-sm max-w-none dark:prose-invert prose-a:text-primary"
                            v-html="entry.content"
                        />
                    </article>
                </template>

                <div
                    v-else
                    class="rounded-lg border border-dashed p-5 text-sm text-muted-foreground"
                >
                    No help content has been published for this page yet.
                </div>

                <Button
                    v-if="help.can_manage && help.manage_url"
                    as-child
                    variant="outline"
                    class="w-full"
                >
                    <a :href="help.manage_url">
                        Manage help for this page
                        <ExternalLink class="ml-2 h-4 w-4" aria-hidden="true" />
                    </a>
                </Button>

                <p class="text-xs text-muted-foreground">
                    Page key: <code>{{ help.key }}</code>
                </p>
            </div>
        </SheetContent>
    </Sheet>
</template>
