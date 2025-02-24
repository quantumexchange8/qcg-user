<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {watch, ref, h} from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { wTrans } from "laravel-vue-i18n";
import Rebate from "./Partials/Rebate.vue";
import GroupTransaction from "./Partials/GroupTransaction.vue";
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const tabs = ref([
    {   
        title: wTrans('public.rebate'),
        type: 'rebate',
        component: h(Rebate),
    },
    {   
        title: wTrans('public.group_transaction'),
        type: 'group_transaction',
        component: h(GroupTransaction),
    },
]);

// Function to get query parameter value
const getQueryParam = (key) => {
    return new URL(window.location.href).searchParams.get(key);
};

// Set initial tab from URL
const selectedType = ref(getQueryParam('tab') || 'rebate');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

// Watch for URL changes and update active tab
watch(
    () => window.location.search, 
    () => {
        const newTab = getQueryParam('tab');
        if (newTab) {
            selectedType.value = newTab;
            activeIndex.value = tabs.value.findIndex(tab => tab.type === newTab);
        }
    },
    { immediate: true }
);

// Update the URL when the tab changes
const updateType = (event) => {
    const selectedTab = tabs.value[event];
    selectedType.value = selectedTab.type;
    history.pushState({}, '', `/report?tab=${selectedTab.type}`);
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.report')">
        <Tabs v-model:value="activeIndex" class="w-full gap-5"
             @update:value="updateType"
        >
            <TabList>
                <Tab 
                    v-for="(tab, index) in tabs" 
                    :key="tab.title"
                    :value="index"
                >
                    {{ `${tab.title}` }}
            </Tab>
            </TabList>
            <TabPanels>
                <TabPanel v-for="(tab, index) in tabs" :key="index" :value="index">
                    <component :is="tab.component" v-if="activeIndex === index" />
                </TabPanel>
            </TabPanels>
        </Tabs>
                
    </AuthenticatedLayout>
</template>