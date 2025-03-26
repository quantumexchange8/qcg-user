<script setup>
import Button from "@/Components/Button.vue";
// import { SwitchHorizontal01Icon } from "@/Components/Icons/outline";
import { IconInfoOctagonFilled, IconInfoCircle } from '@tabler/icons-vue';
import {computed, ref} from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Select from "primevue/select";
import IconField from 'primevue/iconfield';
import Checkbox from 'primevue/checkbox';
import axios from 'axios';
import InputNumber from "primevue/inputnumber";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    account: Object,
    type: String,
});

const showDepositDialog = ref(false);
const missingDepositDialog = ref(false);
const contDepositDialog = ref(false);
const showTransferDialog = ref(false);
const transferOptions = ref([]);
const selectedAccount = ref(0);
const {formatAmount} = transactionFormat()

const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        transferOptions.value = response.data.transferOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

// Computed property to exclude 'meta_login' from account.meta_login
const filteredTransferOptions = computed(() => {
    if (!transferOptions.value.length) {
        return [];
    }

    return transferOptions.value.filter(option => option.name !== props.account.meta_login);
});

const openDialog = (dialogRef) => {
    if (dialogRef === 'deposit') {
        showDepositDialog.value = true;
    } else if (dialogRef === 'transfer') {
        showTransferDialog.value = true;
    }
}

const closeDialog = (dialogName) => {
    if (dialogName === 'deposit') {
        showDepositDialog.value = false;
        formDepositDialog.value = false;
        contDepositDialog.value = false;
        depositForm.reset();
    } else if (dialogName === 'transfer') {
        showTransferDialog.value = false;
        transferForm.reset();
    }
}

const depositForm = useForm({
    meta_login: props.account.meta_login,
    amount: props.account.amount,
    checkbox1: false,
    checkbox2: false,
});

const transferForm = useForm({
    account_id: props.account.id,
    to_meta_login: '',
    amount: 0,
    checkbox: false,
});

const toggleFullAmount = () => {
    if (transferForm.amount) {
        transferForm.amount = 0;
    } else {
        transferForm.amount = Number(props.account.balance);
    }
};

const submitForm = (formType) => {
    if (formType === 'deposit') {
        depositForm.post(route('accounts.deposit_to_account'), {
            onSuccess: () => closeDialog('deposit'),
        });
    } else if (formType === 'transfer') {
        transferForm.to_meta_login = selectedAccount.value.name;
        transferForm.post(route('accounts.internal_transfer'), {
            onSuccess: () => closeDialog('transfer'),
        });
    }
}

const isDepositFormValid = computed(() => depositForm.checkbox1 && depositForm.checkbox2);
const isTransferFormValid = computed(() => {
    if (props.account.account_category === 'promotion') {
        return transferForm.checkbox;
    }
    return true;
});
</script>

<template>
    <Button
        type="button"
        variant="gray-outlined"
        size="sm"
        class="w-full"
        @click="openDialog('deposit')"
        :disabled="account.status === 'pending' || account.is_active === 'inactive'"
    >
        {{ $t('public.deposit') }}
    </Button>
    <Button
        type="button"
        variant="gray-outlined"
        size="sm"
        class="w-full"
        @click="openDialog('transfer')"
        :disabled="account.status === 'pending' || account.is_active === 'inactive'"
    >
        <!-- <SwitchHorizontal01Icon class="w-4 text-gray-950" /> -->
        {{ $t('public.transfer') }}
    </Button>

    <Dialog v-model:visible="showDepositDialog" header=" " modal class="dialog-xs sm:dialog-sm">
        <div class="flex flex-col w-full pb-4 gap-6 md:gap-8">
            <div class="w-full h-[145px] md:h-[200px]">
                <img src="/assets/deposit/d1a.png" alt="No data" class="absolute top-0 left-0 -z-10 hidden md:flex">
                <img src="/assets/deposit/d1c.png" alt="No data" class="absolute top-48 left-32 -z-10 hidden md:flex">
                <img src="/assets/deposit/d1b.png" alt="No data" class="absolute top-15 right-0 -z-10 hidden md:flex">

                <img src="/assets/deposit/d1a2.png" alt="No data" class="absolute top-0 left-0 -z-10 flex md:hidden">
                <img src="/assets/deposit/d1c2.png" alt="No data" class="absolute top-40 left-24 -z-10 flex md:hidden">
                <img src="/assets/deposit/d1b2.png" alt="No data" class="absolute top-15 right-0 -z-10 flex md:hidden">
            </div>
            <div class="flex flex-col w-[calc(100%-40%)] md:w-[204px] gap-1">
                <span class="text-sm md:text-base text-gray-950 font-bold">
                    {{ $t('public.ctrader_deposit') }}
                </span>
                <span class="text-xs md:text-sm text-gray-700">
                    {{ $t('public.ctrader_deposit_caption') }}
                </span>
            </div>
        </div>
        <div class="pt-6 w-full">
            <Button variant="primary-flat" type="button" class="justify-center w-full" @click="missingDepositDialog = true">
                {{$t('public.continue')}}
            </Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="missingDepositDialog" header=" " modal class="dialog-xs sm:dialog-sm">
        <div class="flex flex-col w-full pb-4 gap-6 md:gap-8">
            <div class="w-full h-[145px] md:h-[200px]">
                <img src="/assets/deposit/d2a.png" alt="No data" class="absolute top-0 left-0 -z-10 hidden md:flex">
                <img src="/assets/deposit/d2c.png" alt="No data" class="absolute top-56 left-40 -z-10 hidden md:flex">
                <img src="/assets/deposit/d2b.png" alt="No data" class="absolute top-15 right-0 -z-10 hidden md:flex">

                <img src="/assets/deposit/d2a2.png" alt="No data" class="absolute top-0 left-0 -z-10 flex md:hidden">
                <img src="/assets/deposit/d2c2.png" alt="No data" class="absolute top-36 left-20 -z-10 flex md:hidden">
                <img src="/assets/deposit/d2d2.png" alt="No data" class="absolute top-44 left-32 -z-10 flex md:hidden">
                <img src="/assets/deposit/d2b2.png" alt="No data" class="absolute top-15 right-0 -z-10 flex md:hidden">
            </div>
            <div class="flex flex-col w-[calc(100%-40%)] md:w-[204px] gap-1">
                <span class="text-sm md:text-base text-gray-950 font-bold">
                    {{ $t('public.report_missing_deposit') }}
                </span>
                <span class="text-xs md:text-sm text-gray-700">
                    {{ $t('public.report_missing_deposit_caption') }}
                </span>
            </div>
        </div>
        <div class="pt-6 w-full">
            <Button variant="primary-flat" type="button" class="justify-center w-full" @click="contDepositDialog = true">
                {{$t('public.continue')}}
            </Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="contDepositDialog" modal :header="$t('public.deposit')" class="dialog-xs sm:dialog-sm">
        <div class="flex flex-col py-6 gap-8">
            <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                <span class="text-xs text-center text-gray-500">{{ props.account.meta_login }} - {{ $t('public.current_account_balance') }}</span>
                <span class="text-lg text-center font-bold text-gray-950">$ {{ formatAmount(props.account.balance) }}</span>
            </div>
            <div class="flex flex-col items-start gap-1 self-stretch">
                <InputLabel for="amount" :value="$t('public.amount')" />
                <InputNumber
                    v-model="depositForm.amount"
                    inputId="amount"
                    prefix="$ "
                    class="w-full"
                    placeholder="$ 0.00"
                    :min="0"
                    :step="100"
                    :minFractionDigits="2"
                    fluid
                    autofocus
                    :invalid="!!depositForm.errors.amount"
                />
                <InputError :message="depositForm.errors.amount" />
            </div>
            <div class="flex flex-row gap-3 items-start">
                <div><IconInfoCircle size="24" color="#030712" stroke-width="2"/></div>
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-semibold text-gray-950">{{ $t('public.deposit_information') }}</span>
                    <span class="text-sm text-gray-700">{{ $t('public.deposit_information_caption') }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <label class="flex items-start gap-2">
                <Checkbox binary v-model="depositForm.checkbox1" class="w-5 h-5 flex-shrink-0" />
                <span class="text-xs text-error-600 font-medium">{{ $t('public.deposit_term_1') }}</span>
            </label>
            <label class="flex items-center gap-2">
                <Checkbox binary v-model="depositForm.checkbox2" class="w-5 h-5 flex-shrink-0" />
                <span class="text-xs text-error-600 font-medium">{{ $t('public.deposit_term_2') }}</span>
            </label>
        </div>
        <div class="pt-6 w-full">
            <Button variant="primary-flat" type="button" class="justify-center w-full" :disabled="!isDepositFormValid" @click.prevent="submitForm('deposit')">
                {{$t('public.deposit_now')}}
            </Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="showTransferDialog" :header="$t('public.transfer')" modal class="dialog-xs sm:dialog-sm" @show="getOptions">
        <form @submit.prevent="submitForm('transfer')">
            <div class="flex flex-col py-6 gap-5">
                <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                    <span class="text-gray-500 text-center text-xs">#{{ props.account.meta_login }} - {{ $t('public.current_account_balance') }}</span>
                    <span class="text-gray-950 text-center text-lg font-semibold">$ {{ formatAmount(props.account.balance) }}</span>
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="to_meta_login" :value="$t('public.transfer_to')" />
                    <Select
                        v-model="selectedAccount"
                        :options="filteredTransferOptions"
                        optionLabel="name"
                        :placeholder="$t('public.select')"
                        class="w-full"
                        scroll-height="236px"
                        :disabled="!filteredTransferOptions.length"
                    />
                    <span class="self-stretch text-gray-500 text-xs">{{ $t('public.balance') }}: $ {{ selectedAccount ? selectedAccount.value : selectedAccount }}</span>
                    <InputError :message="transferForm.errors.to_meta_login" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="amount" :value="$t('public.amount')" />
                    <div class="relative w-full">
                        <InputNumber
                            v-model="transferForm.amount"
                            inputId="currency-us"
                            prefix="$ "
                            class="w-full"
                            inputClass="py-2 px-4"
                            :min="0"
                            :step="100"
                            :minFractionDigits="2"
                            fluid
                            autofocus
                            :invalid="!!transferForm.errors.amount"
                        />
                        <div
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 cursor-pointer text-sm font-semibold"
                            :class="{
                                    'text-primary-500': !transferForm.amount,
                                    'text-error-500': transferForm.amount,
                                }"
                            @click="toggleFullAmount"
                        >
                            {{ transferForm.amount ? $t('public.clear') : $t('public.full_amount') }}
                        </div>
                    </div>
                    <InputError :message="transferForm.errors.amount" />
                </div>

                <label v-if="type==='promotion'"  class="flex items-center gap-2">
                    <Checkbox binary v-model="transferForm.checkbox" class="w-4 h-4 flex-shrink-0" />
                    <span class="text-gray-500 text-xs">{{ $t('public.transfer_term_1') }}</span>
                </label>
            </div>
            <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-6">
                <Button
                    type="button"
                    variant="gray-tonal"
                    class="w-full"
                    @click.prevent="closeDialog('transfer')"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    variant="primary-flat"
                    class="w-full"
                    @click.prevent="submitForm('transfer')"
                    :disabled="depositForm.processing || transferForm.processing || !filteredTransferOptions.length || !isTransferFormValid"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
