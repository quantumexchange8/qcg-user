<script setup>
import Button from "@/Components/Button.vue";
import { IconCircleCheckFilled } from '@tabler/icons-vue';
import { ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Dialog from 'primevue/dialog';
import Select from "primevue/select";
import InputNumber from "primevue/inputnumber";
import {transactionFormat} from "@/Composables/index.js";
import { usePage, useForm } from "@inertiajs/vue3";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {useLangObserver} from "@/Composables/localeObserver.js";

const {locale} = useLangObserver();
const { formatAmount } = transactionFormat();

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

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class="md:w-40"
        size="sm"
        @click="openDialog('live', liveAccountForm)"
        :disabled="!accountOptions.length"
    >
        {{ $t('public.open_account') }}
    </Button>
    <Button
        type="button"
        variant="primary-outlined"
        class="md:w-40"
        size="sm"
        @click="openDialog('demo', demoAccountForm)"
    >
        {{ $t('public.demo_account') }}
    </Button>

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
                                {{ account.descriptions[locale] }}
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
