<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import {ref, h, watch, onMounted, computed} from "vue";
import TabView from 'primevue/tabview';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import IndividualAccounts from '@/Pages/Accounts/Partials/IndividualAccounts.vue';
import DemoAccounts from '@/Pages/Accounts/Partials/DemoAccounts.vue';
import { usePage, useForm } from "@inertiajs/vue3";
import Dialog from 'primevue/dialog';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import IconField from 'primevue/iconfield';
import Select from "primevue/select";
import {IconCircleCheckFilled, IconInfoCircle, IconX} from '@tabler/icons-vue';
import { trans, wTrans } from "laravel-vue-i18n";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import { transactionFormat } from "@/Composables/index.js";

const { formatAmount, formatDate } = transactionFormat();
const props = defineProps({
    terms: Object
})

// Initialize the form with user data
const user = usePage().props.auth.user;

const liveAccountForm = useForm({
    user_id: user.id,
    accountType: '',
    leverage: '',
});

const demoAccountForm = useForm({
    user_id: user.id,
    amount: 0.00,
    leverage: '',
});

const tabs = ref([
    { title: wTrans('public.individual'), component: h(IndividualAccounts), type: 'individual' },
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

const showLiveAccountDialog = ref(false);
const showDemoAccountDialog = ref(false);

// Functions to open and close the dialog
const openDialog = (dialogRef, formRef = null) => {
    if (formRef) formRef.reset();
    if (dialogRef === 'live') {
        selectedAccountType.value = '';
        showLiveAccountDialog.value = true;
    } else if (dialogRef === 'demo') {
        showDemoAccountDialog.value = true;
    }
};

const closeDialog = (dialogName, formRef = null) => {
    if (formRef) formRef.reset();
    if (dialogName === 'live') {
        showLiveAccountDialog.value = false;
    } else if (dialogName === 'demo') {
        showDemoAccountDialog.value = false;
    }
};


const leverages = ref();
const transferOptions = ref();
const accountOptions = ref([]);
const selectedAccountType = ref('');

const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        leverages.value = response.data.leverages;
        accountOptions.value = response.data.accountOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();

// Handle selection of an account
function selectAccount(type) {
    selectedAccountType.value = type;
    liveAccountForm.accountType = type;

    // Find selected account and update leverage
    const selectedAccount = accountOptions.value.find(account => account.account_group === type);
    if (selectedAccount && selectedAccount.leverage) {
        liveAccountForm.leverage = selectedAccount.leverage;
    } else {
        liveAccountForm.leverage = ''; // Clear leverage if no value
    }
}

const openLiveAccount = () => {
    liveAccountForm.post(route('accounts.create_live_account'), {
        onSuccess: () => {
            closeDialog('live', liveAccountForm);
        },
        onError: (error) => {
            console.error('Failed to open live account.', error);
        },
    });
};

const openDemoAccount = () => {
    demoAccountForm.post(route('accounts.create_demo_account'), {
        onSuccess: () => {
            closeDialog('demo', demoAccountForm);
        },
        onError: (error) => {
            console.error('Failed to open live account.', error);
        },
    });
};

const buttonSize = computed(() => {
    return window.innerWidth < 768 ? 'sm' : 'base';
})

const noticeVisible = ref(true);
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.accounts')">
        <div class="flex flex-col gap-20 md:gap-[100px]">
            <div class="flex flex-col items-start gap-5 self-stretch">
                <!-- banner -->
                <div class="relative h-[260px] pt-5 px-5 pb-[44px] self-stretch rounded-lg bg-white shadow-card md:h-60
                    bg-no-repeat bg-right-bottom bg-contain overflow-hidden
                    md:pl-10 md:pt-[30px] md:pb-[58px] md:pr-[310px]
                    lg:pt-10 lg:pb-[68px] xl:pr-[469px] z-0"
                    >
                    <div class="absolute inset-0 -z-10">
                        <img src="/assets/account-vector-l-xl.svg" alt="" class="absolute bottom-0 left-0 object-contain hidden xl:block"/>
                        <img src="/assets/account-vector-l-md.svg" alt="" class="absolute bottom-0 left-0 object-contain hidden md:block xl:hidden"/>
                        <img src="/assets/account-vector-l-sm.svg" alt="" class="absolute bottom-0 left-0 object-contain md:hidden"/>

                        <img src="/assets/account-vector-r-xl.svg" alt="" class="absolute bottom-0 object-contain ml-[128px] hidden xl:block"/>
                        <img src="/assets/account-vector-r-md.svg" alt="" class="absolute bottom-0 object-contain ml-[128px] hidden md:block xl:hidden"/>
                        <img src="/assets/account-vector-r-sm.svg" alt="" class="absolute bottom-0 object-contain right-0 md:hidden"/>

                        <img src="/assets/account-bg-xl.png" alt="" class="absolute top-0 right-0 object-contain hidden xl:block"/>
                        <img src="/assets/account-bg-md.png" alt="" class="absolute top-0 right-0 object-contain hidden md:block xl:hidden"/>
                    </div>
                    <!-- Content -->
                    <div class="flex flex-col items-center gap-5 md:w-[380px] md:items-start xl:w-[480px]">
                        <div class="flex flex-col justify-center items-start gap-2 self-stretch">
                            <span class="self-stretch text-gray-950 font-bold text-md md:text-lg">{{ $t('public.open_acc_header') }}</span>
                            <span class="self-stretch text-gray-700 text-sm">{{ $t('public.open_acc_caption') }}</span>
                        </div>
                        <div class="flex flex-col justify-center items-start gap-3 self-stretch md:flex-row md:justify-end md:items-center">
                            <Button
                                type="button"
                                variant="primary-flat"
                                class="w-[140px] md:w-full"
                                :size="buttonSize"
                                @click="openDialog('live', liveAccountForm)"
                                :disabled="!accountOptions.length"
                            >
                                {{ $t('public.open_account') }}
                            </Button>
                            <Button
                                type="button"
                                variant="primary-outlined"
                                class="w-[140px] md:w-full"
                                :size="buttonSize"
                                @click="openDialog('demo', demoAccountForm)"
                            >
                                {{ $t('public.demo_account') }}
                            </Button>
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
                        class="py-4 px-5 flex justify-center self-stretch gap-3 border-l-8 rounded border-info-500 shadow-card bg-info-100 items-start"
                        role="alert"
                    >
                        <div class="text-info-500">
                            <IconInfoCircle size="24" stroke-width="2.0"/>
                        </div>
                        <div
                            class="flex flex-col gap-1 items-start w-full text-sm"
                        >
                            <div class="text-info-500 font-semibold">
                                {{ $t('public.inactive_account_notice') }}
                            </div>
                            <div class="text-gray-700">
                                {{ $t('public.inactive_account_notice_message') }}
                            </div>
                        </div>
                        <div class="text-info-500 hover:text-info-700 hover:cursor-pointer select-none" @click="noticeVisible = false">
                            <IconX size="16" stroke-width="1.25" />
                        </div>
                    </div>
                </TransitionGroup>

                <!-- tab -->
                <div class="flex items-center gap-3 self-stretch">
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
    </AuthenticatedLayout>

    <Dialog v-model:visible="showLiveAccountDialog" modal :header="$t('public.open_live_account')" class="dialog-xs sm:dialog-sm">
        <div class="flex flex-col items-center gap-6 py-4 md:py-6 self-stretch md:gap-8">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col items-start gap-2 self-stretch">
                    <InputLabel for="accountType" :value="$t('public.select_account')" />
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div
                            v-for="(account, index) in accountOptions"
                            :key="account.account_group"
                            @click="selectAccount(account.account_group)"
                            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer"
                            :class="{
                                'bg-primary-50 border-primary-500': selectedAccountType === account.account_group,
                                'bg-white border-gray-300 hover:bg-primary-50 hover:border-primary-500': selectedAccountType !== account.account_group,
                                'border-error-500': liveAccountForm.errors.leverage,
                            }"
                        >
                            <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 text-gray-950"
                                >
                                    {{ $t(`public.${account.slug}`) }}
                                </span>
                                <IconCircleCheckFilled v-if="selectedAccountType === account.account_group" size="20" stroke-width="1.25" color="#2E7D32" />
                            </div>
                            <span
                                class="self-stretch text-xs transition-colors duration-300 group-hover:text-primary-500 text-gray-500"
                            >
                                {{ account.descriptions }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="leverage" :value="$t('public.leverage')" />
                    <Select
                        v-model="liveAccountForm.leverage"
                        :options="leverages"
                        optionLabel="name"
                        optionValue="value"
                        class="w-full"
                        scroll-height="236px"
                        :invalid="!!liveAccountForm.errors.leverage"
                        :disabled="!accountOptions.find(account => account.account_group === selectedAccountType) || accountOptions.find(account => account.account_group === selectedAccountType)?.leverage !== 0"
                        >
                    <template #value="slotProps">
                        <span :class="{
                            'text-gray-400': !accountOptions.find(account => account.account_group === selectedAccountType) || accountOptions.find(account => account.account_group === selectedAccountType)?.leverage !== 0
                        }">
                            {{ leverages.find(option => option.value === slotProps.value)?.name || slotProps.value || $t('public.select_leverage') }}
                        </span>
                    </template>
                    </Select>
                    <InputError :message="liveAccountForm.errors.leverage" />
                </div>
            </div>
            <div class="self-stretch">
                <div class="text-gray-500 text-xs">{{ $t('public.acknowledgement') }}
                    <TermsAndCondition
                    />.
                </div>
            </div>
        </div>
        <div class="flex justify-end items-center pt-6 gap-4 self-stretch w-full">
            <Button variant="gray-outlined" type="button" class="justify-center w-full" @click="closeDialog('live')">
                {{$t('public.cancel')}}
            </Button>
            <Button variant="primary-flat" type="button" class="justify-center w-full" :class="{ 'opacity-25': liveAccountForm.processing }" :disabled="liveAccountForm.processing" @click.prevent="openLiveAccount">{{ $t('public.open') }}</Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="showDemoAccountDialog" modal :header="$t('public.open_demo_account')" class="dialog-xs sm:dialog-sm">
        <div class="flex flex-col items-center gap-6 py-4 md:py-6 self-stretch md:gap-8">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col items-start gap-2 self-stretch">
                    <InputLabel for="amount" :value="$t('public.balance_amount')" />

                        <!-- <div class="text-gray-950 text-sm">$</div> -->
                        <InputNumber
                            id="amount"
                            inputId="currency-us"
                            prefix="$ "
                            class="block w-full"
                            :minFractionDigits="2"
                            v-model="demoAccountForm.amount"
                            :placeholder="'$ ' + formatAmount(0)"
                            :invalid="!!demoAccountForm.errors.amount"
                        />

                    <InputError :message="demoAccountForm.errors.amount" />
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="leverage" :value="$t('public.leverage')" />
                    <Select
                        v-model="demoAccountForm.leverage"
                        :options="leverages"
                        optionLabel="name"
                        optionValue="value"
                        class="w-full"
                        scroll-height="236px"
                        :invalid="!!demoAccountForm.errors.leverage"
                    >
                    <template #value="slotProps">
                        <span :class="{
                            'text-gray-400': !!accountOptions.find(account => account.account_group === selectedAccountType)?.leverage
                        }">
                            {{ leverages.find(option => option.value === slotProps.value)?.name || slotProps.value || $t('public.select_leverage') }}
                        </span>
                    </template>
                    </Select>
                </div>
            </div>
            <div class="self-stretch">
                <div class="text-gray-500 text-xs">{{ $t('public.acknowledgement') }}
                    <TermsAndCondition
                    />.
                </div>
            </div>
        </div>
        <div class="flex justify-end items-center pt-6 gap-4 self-stretch ">
            <Button variant="gray-outlined" type="button" class="justify-center w-full" @click="closeDialog('demo')">
                {{$t('public.cancel')}}
            </Button>
            <Button variant="primary-flat" type="button" class="justify-center w-full" :class="{ 'opacity-25': demoAccountForm.processing }" :disabled="demoAccountForm.processing" @click.prevent="openDemoAccount">{{ $t('public.open') }}</Button>
        </div>
    </Dialog>
</template>
