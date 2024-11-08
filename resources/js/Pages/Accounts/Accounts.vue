<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import OpenLiveAccount from "@/Pages/Accounts/Partials/OpenLiveAccount.vue";
import OpenDemoAccount from "@/Pages/Accounts/Partials/OpenDemoAccount.vue";
import IndividualAccount from '@/Pages/Accounts/Partials/IndividualAccount.vue';
// import DemoAccount from '@/Pages/Accounts/Partials/DemoAccount.vue';
import {ref, watch} from "vue";

const props = defineProps({
    account_types: Array,
    leverages: Array,
})

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.accounts')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.accounts') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-4">
            <div class="relative bg-white rounded-lg pl-5 py-5 md:pl-10 md:pt-10 md:h-60">
                <!-- <img src="/assets/account-vector-l.svg" alt="no data" class="absolute bottom-0 left-0 w-[300px] object-contain z-0"/>
                <img src="/assets/account-vector-r.svg" alt="no data" class="absolute bottom-0 overflow-hidden z-0"/> -->
                <img src="/assets/account-bg.svg" alt="no data" class="absolute top-0 right-0 object-contain hidden md:block"/>
                <div class="flex flex-col gap-5 md:w-1/2 relative">
                    <div class="flex flex-col gap-2">
                        <p class="text-lg font-bold">{{ $t('public.open_acc_header')}}</p>
                        <p class="text-sm">{{ $t('public.open_acc_caption')}}</p>
                    </div>
                    <!-- Action? -->
                    <div class="grid md:grid-cols-2 gap-3 w-1/2 md:w-full">
                        <OpenLiveAccount 
                            :account_types="account_types"
                            :leverages="leverages"
                        />
                        <OpenDemoAccount
                            :leverages="leverages"
                        />
                        <!-- <Button type="button" variant="primary-outlined" class="w-full px-4 py-2"  >
                            <span>{{ $t('public.demo_account') }}</span>
                        </Button> -->
                    </div>
                </div>
            </div>

            <!-- Toast -->
            <!-- <div>

            </div> -->
            <div>
                <Tabs value="0" class="gap-5">
                    <TabList>
                        <Tab value="0"> 
                            {{ $t('public.individual') }}
                        </Tab>
                        <Tab value="1">                
                            {{ $t('public.demo') }}
                        </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="0">
                            <IndividualAccount
                            />
                        </TabPanel>
                        <TabPanel value="1">
                            <div>demo account panel here</div>
                            <!-- <DemoAccount
                                :walletSel="walletSel"
                                :leverageSel="leverageSel"
                                :accountCounts="accountCounts"
                            /> -->
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>




    </AuthenticatedLayout>
</template>
