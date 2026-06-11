<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface LicenseType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price_cents: number;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    max_resolution: string;
    is_active: boolean;
    sort_order: number;
}

defineProps<{
    licenseTypes: LicenseType[];
}>();

const deleteId = ref<number | null>(null);

function formatPrice(priceCents: number): string {
    return `$${(priceCents / 100).toFixed(2)}`;
}

function confirmDelete(id: number) {
    deleteId.value = id;
}

function deleteLicenseType() {
    if (!deleteId.value) return;

    router.delete(`/admin/license-types/${deleteId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteId.value = null;
        },
    });
}
</script>

<template>
    <Head title="License Types" />

    <AppLayout>
        <div class="space-y-6 p-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        License Types
                    </h1>

                    <p class="text-muted-foreground">
                        Manage image licensing options available for purchase.
                    </p>
                </div>

                <Button as-child>
                    <Link href="/admin/license-types/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add License Type
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>
                        License Types
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>Resolution</TableHead>
                                <TableHead>Downloads</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Sort</TableHead>
                                <TableHead class="w-[120px] text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="licenseType in licenseTypes"
                                :key="licenseType.id"
                            >
                                <TableCell>
                                    <div class="font-medium">
                                        {{ licenseType.name }}
                                    </div>

                                    <div
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ licenseType.slug }}
                                    </div>
                                </TableCell>

                                <TableCell>
                                    {{ formatPrice(licenseType.price_cents) }}
                                </TableCell>

                                <TableCell>
                                    {{ licenseType.max_resolution }}
                                </TableCell>

                                <TableCell>
                                    {{
                                        licenseType.download_limit ?? 'Unlimited'
                                    }}
                                </TableCell>

                                <TableCell>
                                    <span
                                        :class="
                                            licenseType.is_active
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{
                                            licenseType.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>
                                </TableCell>

                                <TableCell>
                                    {{ licenseType.sort_order }}
                                </TableCell>

                                <TableCell>
                                    <div
                                        class="flex justify-end gap-2"
                                    >
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            as-child
                                        >
                                            <Link
                                                :href="`/admin/license-types/${licenseType.id}/edit`"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Link>
                                        </Button>

                                        <Button
                                            variant="destructive"
                                            size="icon"
                                            @click="confirmDelete(licenseType.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="licenseTypes.length === 0">
                                <TableCell
                                    colspan="7"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No license types found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <AlertDialog :open="deleteId !== null">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Delete License Type
                        </AlertDialogTitle>

                        <AlertDialogDescription>
                            Are you sure you want to delete this license type?
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter>
                        <AlertDialogCancel
                            @click="deleteId = null"
                        >
                            Cancel
                        </AlertDialogCancel>

                        <AlertDialogAction
                            @click="deleteLicenseType"
                        >
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </AppLayout>
</template>