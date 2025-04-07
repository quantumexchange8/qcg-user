<script setup>
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/InputLabel.vue";
import InputNumber from 'primevue/inputnumber';
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import {usePage} from "@inertiajs/vue3";
import Select from "primevue/select";
import { h, ref, watch} from "vue";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {useConfirm} from "primevue/useconfirm";
import {trans} from "laravel-vue-i18n";
import { IconQuestionMark, IconChecks } from "@tabler/icons-vue";

const props = defineProps({
    incentiveWallet: Object,
})
const paymentAccounts = usePage().props.auth.payment_account;
const kycStatus = usePage().props.auth.user.kyc_approval;

const walletOptions = ref([]);
const getOptions = async () => {
    try {
        const response = await axios.get('/accounts/getOptions');
        walletOptions.value = response.data.walletOptions;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getOptions();
const confirm = useConfirm();

const { formatAmount } = transactionFormat();

const visible = ref(false);

const form = useForm({
    wallet_id: props.incentiveWallet?.id || null,
    amount: props.incentiveWallet?.balance || 0,
    wallet_address: '',
})

watch(walletOptions, (newWallet) => {
    form.wallet_address = newWallet[0].value
})

watch(
    () => props.incentiveWallet,
    (newWallet) => {
        if (newWallet?.id) {
            form.wallet_id = newWallet.id;
            form.amount = newWallet.balance;
        }
    },
    { immediate: true } // Ensures the watcher runs initially if the prop is already set
);

const transactionConfirmation = (accountType) => {
    const messages = {
        incentive: {
            group: 'headless-primary',
            color: 'primary',
            icon: h(IconChecks),
            header: trans('public.withdrawal_request_submitted'),
            text: trans('public.withdrawal_request_message'),
            actionType: 'incentive',
            acceptButton: trans('public.alright'),
            accept: () => {
                window.location.reload();
            }
        },
        crypto: {
            group: 'headless',
            color: 'primary',
            icon: h(IconQuestionMark),
            header: trans('public.missing_cryptocurrency_wallet'),
            message: trans('public.missing_cryptocurrency_message'),
            actionType: 'crypto',
            cancelButton: trans('public.later'),
            acceptButton: trans('public.add_wallet'),
            action: () => {
                window.location.href = route('profile');
            }
        },
        verification: {
            group: 'headless',
            color: 'primary',
            icon: h(IconQuestionMark),
            header: trans('public.kyc_verification_required'),
            message: trans('public.kyc_verification_required_message'),
            cancelButton: trans('public.later'),
            acceptButton: trans('public.proceed'),
            action: () => {
                window.location.href = route('profile');
            }
        }
    };

    const { group, color, icon, header, message, actionType, cancelButton, acceptButton, action } = messages[accountType];

    confirm.require({
        group,
        color,
        icon,
        header,
        actionType,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });

}

const submitForm = () => {
    form.post(route('leaderboard.incentiveWithdrawal'), {
        onSuccess: () => {
            closeDialog();
            transactionConfirmation('incentive');
        }
    });
}

const openDialog = (type) => {
    if (type === 'withdrawal' && kycStatus !== 'verified') {
        transactionConfirmation('verification');
    }
    else if (type === 'withdrawal' && paymentAccounts.length === 0) {
        transactionConfirmation('crypto');
    } else {
        visible.value = true;
    }
}

const closeDialog = () => {
    visible.value = false;
}

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class="flex-1"
        @click="openDialog('withdrawal')"
    >
        {{ $t('public.withdrawal') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.incentive_withdrawal')"
        class="dialog-xs sm:dialog-sm"
    >
        <form>
            <div class="flex flex-col w-full items-center gap-8 pt-6 self-stretch md:gap-10">
                <div class="flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col ustify-center items-center py-3 px-8 gap-1 self-stretch bg-gray-100">
                        <span class="w-full text-xs text-center text-gray-500">{{ $t('public.available_incentive') }}</span>
                        <span class="w-full text-lg text-center font-semibold text-gray-950">$ {{ formatAmount(form.amount) }}</span>
                    </div>
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel for="receiving_wallet" :value="$t('public.receiving_wallet')" />
                        <Select
                            v-model="form.wallet_address"
                            :options="walletOptions"
                            optionLabel="name"
                            optionValue="value"
                            :placeholder="$t('public.receiving_wallet_placeholder')"
                            class="w-full"
                            scroll-height="236px"
                            :invalid="!!form.errors.wallet_address"
                            :disabled="!walletOptions.length"
                        />
                        <InputError :message="form.errors.wallet_address" />
                        <span class="self-stretch text-gray-500 text-xs">{{ walletOptions.length ? form.wallet_address : $t('public.loading_caption')}}</span>
                    </div>
                </div>
                <div class="self-stretch">
                    <div class="text-gray-500 text-xs">{{ $t('public.acknowledgement') }}
                        <TermsAndCondition
                        />.
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-6 w-full">
                <Button variant="gray-outlined" type="button" class="justify-center" @click="closeDialog">
                    {{$t('public.cancel')}}
                </Button>
                <Button variant="primary-flat" type="button" class="justify-center" @click="submitForm" :disabled="form.processing || !walletOptions.length">{{$t('public.confirm')}}</Button>
            </div>
        </form>
    </Dialog>
    
</template>
