<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
const props = defineProps<{ preferences:{personalized_recommendations:boolean;retain_recently_viewed:boolean;allow_unlisted_wish_lists:boolean}; sharedWishLists:number; dataSummary:Record<string,number> }>();
const form = useForm({ ...props.preferences });
</script>
<template>
    <Head title="Privacy" />
    <AccountPageLayout>
        <template #title>Privacy</template>
        <template #description>Control personalization, browsing history, and shared wish-list visibility.</template>
        <form class="max-w-3xl space-y-6" @submit.prevent="form.put('/account/privacy')">
            <section class="rounded-2xl border p-5"><h2 class="text-lg font-semibold">Personalization</h2><label class="mt-5 flex gap-3"><input v-model="form.personalized_recommendations" type="checkbox" /><span><span class="block font-medium">Personalized recommendations</span><span class="text-sm text-stone-500">Use your activity to improve recommendations in your account.</span></span></label><label class="mt-5 flex gap-3"><input v-model="form.retain_recently_viewed" type="checkbox" /><span><span class="block font-medium">Keep recently viewed assets</span><span class="text-sm text-stone-500">Turning this off deletes your existing recently viewed history.</span></span></label></section>
            <section class="rounded-2xl border p-5"><h2 class="text-lg font-semibold">Wish-list sharing</h2><label class="mt-5 flex gap-3"><input v-model="form.allow_unlisted_wish_lists" type="checkbox" /><span><span class="block font-medium">Allow unlisted share links</span><span class="text-sm text-stone-500">Turning this off makes {{ sharedWishLists }} currently shared list(s) private and revokes their links.</span></span></label></section>
            <section class="rounded-2xl border p-5"><h2 class="text-lg font-semibold">Your account data</h2><dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4"><div v-for="(value,key) in dataSummary" :key="key"><dt class="text-sm capitalize text-stone-500">{{ String(key).replace('_',' ') }}</dt><dd class="mt-1 text-2xl font-semibold">{{ value }}</dd></div></dl><p class="mt-4 text-sm text-stone-500">Commerce and license records may be retained when required for accounting, fraud prevention, and proof of purchase.</p></section>
            <button type="submit" class="min-h-11 rounded-xl bg-[var(--brand-primary)] px-5 font-medium text-white" :disabled="form.processing">Save privacy settings</button>
        </form>
    </AccountPageLayout>
</template>
