<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'

const page = usePage<any>()
</script>

<template>
    <div class="mb-4 space-y-2">
        <div
            v-if="page.props.flash?.success"
            class="rounded-md border border-green-300 bg-green-50 px-4 py-3 text-green-800"
        >
            {{ page.props.flash.success }}
        </div>

        <div
            v-if="page.props.flash?.error"
            class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-red-800"
        >
            {{ page.props.flash.error }}
        </div>

        <div
            v-if="page.props.flash?.warning"
            class="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-yellow-800"
        >
            {{ page.props.flash.warning }}
        </div>

        <div
            v-if="page.props.flash?.info"
            class="rounded-md border border-blue-300 bg-blue-50 px-4 py-3 text-blue-800"
        >
            {{ page.props.flash.info }}
        </div>
    </div>
</template>