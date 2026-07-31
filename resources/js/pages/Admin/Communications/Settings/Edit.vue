<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    settings: {
        sender_name: string | null;
        sender_email: string | null;
        reply_to_name: string | null;
        reply_to_email: string | null;
        default_test_recipient: string | null;
    };
    fallbacks: { sender_name: string | null; sender_email: string | null };
}>();

const form = useForm({
    sender_name: props.settings.sender_name ?? '',
    sender_email: props.settings.sender_email ?? '',
    reply_to_name: props.settings.reply_to_name ?? '',
    reply_to_email: props.settings.reply_to_email ?? '',
    default_test_recipient: props.settings.default_test_recipient ?? '',
});

const submit = () => form.put('/admin/communications/settings', { preserveScroll: true });
</script>

<template>
    <Head title="Communication Settings" />
    <div class="space-y-6 p-6">
        <PageHeader title="Communication Settings" description="Manage the sender identity, reply-to address, and default test recipient used by customer emails." />
        <div class="flex flex-wrap gap-2">
            <Link href="/admin/communications/email-templates"><Button variant="outline">Email templates</Button></Link>
            <Link href="/admin/communications/delivery-activity"><Button variant="outline">Delivery activity</Button></Link>
        </div>

        <form class="max-w-4xl space-y-6" @submit.prevent="submit">
            <Card>
                <CardHeader><CardTitle>Sender</CardTitle><CardDescription>Leave these blank to use the mail settings from the environment. Current fallback: {{ fallbacks.sender_name }} &lt;{{ fallbacks.sender_email }}&gt;.</CardDescription></CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2"><Label for="sender_name">Sender name</Label><Input id="sender_name" v-model="form.sender_name" /><p class="text-sm text-destructive">{{ form.errors.sender_name }}</p></div>
                    <div class="space-y-2"><Label for="sender_email">Sender email</Label><Input id="sender_email" v-model="form.sender_email" type="email" /><p class="text-sm text-destructive">{{ form.errors.sender_email }}</p></div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Replies and testing</CardTitle><CardDescription>Replies can be directed to a monitored support inbox. The test recipient is prefilled when administrators send template tests.</CardDescription></CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2"><Label for="reply_to_name">Reply-to name</Label><Input id="reply_to_name" v-model="form.reply_to_name" /><p class="text-sm text-destructive">{{ form.errors.reply_to_name }}</p></div>
                    <div class="space-y-2"><Label for="reply_to_email">Reply-to email</Label><Input id="reply_to_email" v-model="form.reply_to_email" type="email" /><p class="text-sm text-destructive">{{ form.errors.reply_to_email }}</p></div>
                    <div class="space-y-2 md:col-span-2"><Label for="default_test_recipient">Default test recipient</Label><Input id="default_test_recipient" v-model="form.default_test_recipient" type="email" /><p class="text-sm text-destructive">{{ form.errors.default_test_recipient }}</p></div>
                </CardContent>
            </Card>
            <Button type="submit" :disabled="form.processing">Save communication settings</Button>
        </form>
    </div>
</template>
