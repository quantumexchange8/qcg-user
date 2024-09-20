<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import {TabGroup, TabList, Tab, TabPanels, TabPanel} from '@headlessui/vue'
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
                <TabGroup>
                    <div class="w-full flex flex-col sm:flex-row gap-4 sm:justify-between items-center">

                        <TabList 
                                class="flex space-x-1 rounded-xl p-1 w-full max-w-md">
                            <Tab
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    :class="[
                                        'w-full p-3 text-sm font-medium leading-5',
                                        'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                        selected
                                        ? 'bg-white text-primary-500 shadow'
                                        : 'text-gray-700 hover:bg-white/[0.12] hover:text-primary-600',
                                    ]"
                                >
                                    {{ $t('public.individual') }}
                                </button>
                            </Tab>

                            <Tab
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    :class="[
                                        'w-full p-3 text-sm font-medium leading-5',
                                        'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                        selected
                                        ? 'bg-white text-primary-500 shadow'
                                        : 'text-gray-700 hover:bg-white/[0.12] hover:text-primary-600',
                                    ]"
                                >
                                    {{ $t('public.demo') }}
                                </button>
                            </Tab>

                        </TabList>

                    </div>
                    <TabPanels class="mt-2">
                        <TabPanel
                            class="py-3"
                        >
                            <div>individual account panel here</div>
                            <IndividualAccount
                                :walletSel="walletSel"
                                :leverageSel="leverageSel"
                                :accountCounts="accountCounts"
                            />
                        </TabPanel>

                        <TabPanel
                            class="py-3"
                        >
                            <div>demo account panel here</div>
                            <!-- <DemoAccount
                                :walletSel="walletSel"
                                :leverageSel="leverageSel"
                                :accountCounts="accountCounts"
                            /> -->
                        </TabPanel>

                    </TabPanels>
                </TabGroup>
            </div>
        </div>




    </AuthenticatedLayout>
</template>
