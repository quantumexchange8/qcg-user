<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref, h} from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { wTrans } from "laravel-vue-i18n";
import {usePage} from "@inertiajs/vue3";
import NetworkTree from "./Partials/NetworkTree.vue";
import NetworkListing from "./Partials/NetworkListing.vue";

const props = defineProps({
    tab: Number,
})

const user = usePage().props.auth.user;

const tabs = ref([
    {   
        title: wTrans('public.sidebar.network'),
        type: 'network',
        component: h(NetworkTree),
    },
    {   
        title: wTrans('public.listing'),
        type: 'listing',
        component: h(NetworkListing),
    },
]);

const selectedType = ref('network');
const activeIndex = ref(user.role === 'agent' ? props.tab : 0);

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.network')">
        <Tabs v-model:value="activeIndex" class="w-full gap-5"
        @tab-change="updateType"
        >
            <TabList v-if="user.role === 'agent'">
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