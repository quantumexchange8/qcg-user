<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";

import {ref, watch} from "vue";

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.network')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.network') }}
                </h2>
            </div>
        </template>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">

        </div>



    </AuthenticatedLayout>
</template>
