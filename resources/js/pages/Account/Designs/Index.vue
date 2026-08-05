<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ImagePlus, Pencil, Trash2 } from '@lucide/vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import { Button } from '@/components/ui/button';
interface Project { uuid:string; title:string; status:string; updated_at:string; canvas:[number,number]; preview_url:string|null; edit_url:string }
defineProps<{projects:Project[]}>();
function remove(project:Project){ if(confirm(`Delete “${project.title}”?`)) router.delete(`/account/designs/${project.uuid}`); }
</script>
<template>
<Head title="My Designs"/><AccountPageLayout><template #title>My Designs</template><template #description>Personalize licensed images with text and your own graphics. Your original purchases remain unchanged.</template>
<div v-if="projects.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
<article v-for="project in projects" :key="project.uuid" class="overflow-hidden rounded-3xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
<div class="aspect-video bg-stone-100 dark:bg-stone-800"><img v-if="project.preview_url" :src="project.preview_url" :alt="project.title" class="h-full w-full object-cover"/></div>
<div class="p-5"><h2 class="font-semibold">{{project.title}}</h2><p class="mt-1 text-sm text-stone-500">{{project.canvas[0]}} × {{project.canvas[1]}} · Updated {{project.updated_at}}</p>
<div class="mt-5 flex gap-2"><Button as-child class="flex-1"><Link :href="project.edit_url"><Pencil class="mr-2 h-4 w-4"/>Edit design</Link></Button><Button variant="outline" size="icon" aria-label="Delete design" @click="remove(project)"><Trash2 class="h-4 w-4"/></Button></div></div></article></div>
<div v-else class="rounded-3xl border border-dashed p-12 text-center"><ImagePlus class="mx-auto h-12 w-12 text-stone-400"/><h2 class="mt-4 text-xl font-semibold">No designs yet</h2><p class="mx-auto mt-2 max-w-md text-sm text-stone-500">Open an active license in My Library and choose Customize Image to begin.</p><Button as-child class="mt-6"><Link href="/account/library">Browse My Library</Link></Button></div>
</AccountPageLayout></template>
