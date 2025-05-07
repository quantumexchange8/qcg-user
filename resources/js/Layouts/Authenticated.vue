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

    <div class="min-h-screen bg-gray-100 flex flex-col">
        <!-- Main container for Sidebar and Navbar -->
        <div class="flex flex-col flex-1">
            <!-- Navbar -->
            <Navbar :title="title" v-if="title!==$t('public.forum')"/>

            <div class="flex flex-1 p-3 md:p-5 gap-5">
                <!-- Sidebar -->
                <Sidebar  v-if="title!==$t('public.forum')"/>

                <!-- Page Content -->
                <main class="flex flex-1 justify-center items-start overflow-hidden">
                    <div class="flex flex-col h-full w-full max-w-[1440px]">
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
