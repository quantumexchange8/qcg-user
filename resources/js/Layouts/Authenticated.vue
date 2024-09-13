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
        
        <div class="flex flex-col flex-1">
            
            <Navbar :title="title" />

            <div class="px-3 py-5 gap-5 md:px-5">
                
                <Sidebar  />

                <div style="transition-property: margin; transition-duration: 150ms"
                :class="[
                    'min-h-screen flex flex-col',
                    {
                        'lg:ml-72': sidebarState.isOpen,
                        'md:ml-16': !sidebarState.isOpen,
                    },
                ]">
                    <main class="flex flex-1 justify-center items-start">
                        <div class="w-full max-w-[1440px]">
                            
                            <ToastList />
                            
                            <ConfirmationDialog />

                            <slot />
                        </div>
                    </main>

                    <PageFooter />
                </div>
            </div>
        </div>
    </div>
</template>
