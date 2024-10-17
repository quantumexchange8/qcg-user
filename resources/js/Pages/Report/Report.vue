<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref, h} from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { wTrans } from "laravel-vue-i18n";
import Rebate from "./Partials/Rebate.vue";
import GroupTransaction from "./Partials/GroupTransaction.vue";

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

const selectedType = ref('rebate');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.report')">
        <Tabs v-model:value="activeIndex" class="w-full gap-5"
        @tab-change="updateType"
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
                <TabPanel :key="activeIndex" :value="activeIndex">
                    <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" v-if="tabs[activeIndex].component"/>
                </TabPanel>
            </TabPanels>
        </Tabs>
                
    </AuthenticatedLayout>
</template>