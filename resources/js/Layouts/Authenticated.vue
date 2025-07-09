<script setup>
import {Head, usePage} from '@inertiajs/vue3'
import Sidebar from '@/Components/Sidebar/Sidebar.vue'
import Navbar from '@/Components/Navbar.vue'
import { sidebarState } from '@/Composables'
import ToastList from "@/Components/ToastList.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import PageFooter from '@/Components/PageFooter.vue'

defineProps({
    title: String
})
</script>

<template>
    <Head :title="title"></Head>

    <div class="min-h-screen bg-gray-100 flex flex-col items-center">
        <!-- Main container for Sidebar and Navbar -->
        <div class="flex flex-col px-3 md:px-5 flex-1 max-w-[1440px]">
            <!-- Navbar -->
            <Navbar :title="title"/>

            <div class="flex flex-1 py-3 md:py-5 gap-5">
                <!-- Sidebar -->
                <Sidebar  v-if="title!==$t('public.forum') && title!==$t('public.ticket_center')"/>

                <!-- Page Content -->
                <main class="flex flex-1 justify-center items-start overflow-hidden">
                    <div class="flex flex-col h-full w-full">
                        <!-- Toast -->
                        <ToastList />
                        <!-- Confirmation Dialog -->
                        <ConfirmationDialog />

                        <slot />

                        <PageFooter />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
