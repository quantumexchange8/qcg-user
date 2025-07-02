<script setup>
import Button from "@/Components/Button.vue";
import { IconChevronLeft } from "@tabler/icons-vue";
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import NewTicket from "@/Pages/Tickets/Partials/NewTicket.vue";
import MyTickets from "@/Pages/Tickets/Partials/MyTickets.vue";
import TicketHistory from "@/Pages/Tickets/Partials/TicketHistory.vue";

</script>

<template>
    <div class="flex justify-between items-center px-5 py-2 w-full">
        <div class="flex gap-2 items-center">
            <Button
                variant="gray-text"
                iconOnly
                pill
                :href="route('dashboard')"
            >
                <IconChevronLeft size="20" stroke-width="1.25"/>
            </Button>
            <span class="text-gray-950 font-bold">{{ $t('public.ticket_center') }}</span>
        </div>
    </div>
    <AuthenticatedLayout :title="$t('public.ticket_center')">
        <div class="flex flex-col items-center gap-4 md:gap-6 self-stretch p-4 md:p-6 bg-white rounded-lg shadow-card w-full">
            <div class="flex flex-row self-stretch items-center justify-between">
                <span class="text-gray-950 text-base font-semibold">{{ $t('public.my_tickets') }}</span>
                <NewTicket />

            </div>
            
            <MyTickets />

            <div class="flex flex-row self-stretch items-center justify-between">
                <span class="text-gray-950 text-base font-semibold">{{ $t('public.ticket_history') }}</span>

            </div>

            <TicketHistory />
            
        </div>
    </AuthenticatedLayout>

   
</template>
