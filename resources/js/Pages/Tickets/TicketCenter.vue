<script setup>
import {watch, ref, h, onMounted, onUnmounted} from "vue";
import Button from "@/Components/Button.vue";
import { IconChevronLeft } from "@tabler/icons-vue";
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import NewTicket from "@/Pages/Tickets/Partials/NewTicket.vue";
import MyTickets from "@/Pages/Tickets/Partials/MyTickets.vue";
import TicketHistory from "@/Pages/Tickets/Partials/TicketHistory.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { wTrans } from "laravel-vue-i18n";

const tabs = ref([
    { title: wTrans('public.my_tickets'), component: h(MyTickets), type: 'ongoing' },
    { title: wTrans('public.ticket_history'), component: h(TicketHistory), type: 'history' },
]);

const selectedType = ref('ongoing');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
    }
});

const isMobile = ref(window.innerWidth <= 768)

const updateScreenSize = () => {
  isMobile.value = window.innerWidth <= 768
}

onMounted(() => {
  window.addEventListener('resize', updateScreenSize)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateScreenSize)
})

</script>

<template>
    <AuthenticatedLayout :title="$t('public.ticket_center')">
        <div class="flex flex-col items-center gap-4 md:gap-6 self-stretch p-4 md:p-6 bg-white rounded-lg shadow-card w-full">
            <div class="flex w-full md:hidden">
                <NewTicket />
            </div>

            <div v-if="isMobile" class="self-stretch">
                <Tabs v-model:value="activeIndex" class="flex w-full gap-5">
                    <TabList>
                    <Tab v-for="(tab, index) in tabs" :key="tab.title" :value="index" class="flex-1">
                        {{ tab.title }}
                    </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel v-for="(tab, index) in tabs" :key="index" :value="index">
                            <component :is="tab.component" v-if="activeIndex === index" />
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>

            <div v-else class="flex flex-col items-center gap-6 self-stretch">
                <div class="md:flex justify-between items-center self-stretch">
                    <span class="text-gray-950 text-base font-semibold">{{ $t('public.my_tickets') }}</span>
                    <NewTicket />
                </div>

                <MyTickets />

                <div class="md:flex justify-between items-center self-stretch">
                    <span class="text-gray-950 text-base font-semibold">{{ $t('public.ticket_history') }}</span>
                </div>

                <TicketHistory />
            </div>
            
        </div>
    </AuthenticatedLayout>

   
</template>
