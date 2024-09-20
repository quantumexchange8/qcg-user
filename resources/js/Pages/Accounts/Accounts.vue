<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import IndividualAccount from '@/Pages/Accounts/Partials/IndividualAccount.vue';
// import DemoAccount from '@/Pages/Accounts/Partials/DemoAccount.vue';
import {ref, watch} from "vue";

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
            <div class="bg-white rounded-lg pl-5 pt-5 md:pl-10 md:pt-10 h-60">
                <div class="flex flex-col gap-5 w-1/2">
                    <div class="flex flex-col gap-2">
                        <p class="text-lg font-bold">{{ $t('public.open_acc_header')}}</p>
                        <P class="text-sm">{{ $t('public.open_acc_caption')}}</P>
                    </div>
                    <!-- Action? -->
                    <div class="flex flex-row gap-3">
                        <Button type="button" variant="primary-flat" class="w-full px-4 py-2"  >
                            <span>{{ $t('public.open_account') }}</span>
                        </Button>
                        <Button type="button" variant="primary-outlined" class="w-full px-4 py-2"  >
                            <span>{{ $t('public.demo_account') }}</span>
                        </Button>
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
