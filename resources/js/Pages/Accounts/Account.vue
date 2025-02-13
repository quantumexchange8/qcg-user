<script setup>
import Button from "@/Components/Button.vue";
import {ref, h, watch} from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import IndividualAccounts from '@/Pages/Accounts/Partials/IndividualAccounts.vue';
import PromotionAccounts from '@/Pages/Accounts/Partials/PromotionAccounts.vue';
import DemoAccounts from '@/Pages/Accounts/Partials/DemoAccounts.vue';
import OpenAccount from '@/Pages/Accounts/Partials/OpenAccount.vue';
import { IconInfoCircle, IconX, IconAlertTriangle} from '@tabler/icons-vue';
import { wTrans } from "laravel-vue-i18n";

const tabs = ref([
    { title: wTrans('public.individual'), component: h(IndividualAccounts), type: 'individual' },
    { title: wTrans('public.promotion'), component: h(PromotionAccounts), type: 'promotion' },
    { title: wTrans('public.demo'), component: h(DemoAccounts), type: 'demo' },
]);

const selectedType = ref('individual');
const activeIndex = ref(tabs.value.findIndex(tab => tab.type === selectedType.value));

// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
    }
});

function updateType(event) {
    const selectedTab = tabs.value[event.index];
    selectedType.value = selectedTab.type;
}

const noticeVisible = ref(true);
const warningVisible = ref(true);
</script>

<template>
    <div class="flex flex-col gap-20 md:gap-[100px] w-full">
        <div class="flex flex-col items-start gap-2 self-stretch">
            <!-- banner -->
            <div class="relative pt-3 px-3 pb-[44px] self-stretch rounded-lg bg-white shadow-card md:h-[180px]
                bg-no-repeat bg-right-bottom bg-contain overflow-hidden
                md:pl-6 md:pt-5 md:pb-9 md:pr-[308px]
                z-0"
                >
                <div class="absolute inset-0 -z-10">
                    <img src="/assets/account-vector-l-xl.svg" alt="" class="absolute bottom-0 left-0 object-contain hidden xl:block"/>
                    <img src="/assets/account-vector-l-md.svg" alt="" class="absolute bottom-0 left-0 object-contain hidden md:block xl:hidden"/>
                    <img src="/assets/account-vector-l-sm.svg" alt="" class="absolute bottom-0 left-0 object-contain md:hidden"/>

                    <img src="/assets/account-vector-r-xl.svg" alt="" class="absolute bottom-0 object-contain ml-[128px] hidden xl:block"/>
                    <img src="/assets/account-vector-r-md.svg" alt="" class="absolute bottom-0 object-contain ml-[128px] hidden md:block xl:hidden"/>
                    <img src="/assets/account-vector-r-sm.svg" alt="" class="absolute bottom-0 object-contain ml-[63px] md:hidden"/>

                    <img src="/assets/account-banner.png" alt="" class="absolute top-0 right-0 object-contain hidden md:block"/>
                </div>
                <!-- Content -->
                <div class="flex flex-col items-center gap-5 md:items-start">
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch">
                        <span class="self-stretch text-gray-950 font-bold text-sm md:text-base">{{ $t('public.open_acc_header') }}</span>
                        <span class="self-stretch text-gray-700 text-xs md:text-sm">{{ $t('public.open_acc_caption') }}</span>
                    </div>
                    <div class="flex gap-3 self-stretch flex-row justify-start items-center">
                        <OpenAccount />
                    </div>
                </div>
            </div>

            <!-- notice -->
            <TransitionGroup
                tag="div"
                enter-from-class="-translate-y-full opacity-0"
                enter-active-class="duration-300"
                leave-active-class="duration-300"
                leave-to-class="-translate-y-full opacity-0"
                class="w-full"
            >
                <div
                    v-if="noticeVisible"
                    class="p-2 md:py-4 md:px-5 flex justify-center self-stretch gap-3 border-l-8 rounded border-info-500 shadow-card bg-info-100 items-start"
                    role="alert"
                >
                    <div class="text-info-500">
                        <IconInfoCircle size="24" stroke-width="2.0"/>
                    </div>
                    <div
                        class="flex flex-col gap-1 items-start w-full"
                    >
                        <div class="text-info-500 font-semibold text-sm">
                            {{ $t('public.inactive_account_notice') }}
                        </div>
                        <div class="text-gray-700 text-xs md:text-sm">
                            {{ $t('public.inactive_account_notice_message') }}
                        </div>
                    </div>
                    <div class="text-info-500 hover:text-info-700 hover:cursor-pointer select-none" @click="noticeVisible = false">
                        <IconX size="16" stroke-width="1.25" />
                    </div>
                </div>
            </TransitionGroup>

            <!-- <TransitionGroup
                v-if="tabs[activeIndex].type === 'promotion'"
                tag="div"
                enter-from-class="-translate-y-full opacity-0"
                enter-active-class="duration-300"
                leave-active-class="duration-300"
                leave-to-class="-translate-y-full opacity-0"
                class="w-full"
            >
                <div
                    v-if="warningVisible"
                    class="py-4 px-5 flex justify-center self-stretch gap-3 border-l-8 rounded border-warning-600 shadow-card bg-warning-100 items-start"
                    role="alert"
                >
                    <div class="text-warning-600">
                        <IconAlertTriangle size="24" stroke-width="2.0"/>
                    </div>
                    <div
                        class="flex flex-col gap-1 items-start w-full text-sm"
                    >
                        <div class="text-warning-600 font-semibold">
                            {{ $t('public.unused_credit_warning') }}
                        </div>
                        <div class="text-gray-700">
                            {{ $t('public.unused_credit_warning_message') }}
                        </div>
                    </div>
                    <div class="text-warning-600 hover:text-warning-800 hover:cursor-pointer select-none" @click="warningVisible = false">
                        <IconX size="16" stroke-width="1.25" />
                    </div>
                </div>
            </TransitionGroup> -->

            <!-- tab -->
            <div class="flex items-center gap-3 self-stretch text-xs md:text-sm">
                <Tabs v-model:value="activeIndex" class="w-full" @tab-change="updateType">
                    <TabList>
                        <Tab v-for="(tab, index) in tabs" :key="tab.title" :value="index">
                            {{ $t(tab.title) }}
                        </Tab>
                    </TabList>
                </Tabs>
            </div>

            <Tabs v-model:value="activeIndex" class="w-full">
                <TabPanels>
                    <TabPanel :key="activeIndex" :value="activeIndex">
                        <component :is="tabs[activeIndex].component" :key="tabs[activeIndex].type" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </div>

</template>
