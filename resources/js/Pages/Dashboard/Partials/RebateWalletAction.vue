<script setup>
import Button from "@/Components/Button.vue";
import { h, ref } from "vue";
import Dialog from "primevue/dialog";
import WalletWithdrawal from "@/Pages/Dashboard/Partials/WalletWithdrawal.vue";
import {usePage} from "@inertiajs/vue3";
import {trans} from "laravel-vue-i18n";
import {useConfirm} from "primevue/useconfirm";
import WalletTransfer from "@/Pages/Dashboard/Partials/WalletTransfer.vue";
import { IconQuestionMark } from "@tabler/icons-vue";

const props = defineProps({
    rebateWallet: Object,
    terms: Object,
})

const visible = ref(false);
const dialogType = ref('');
const paymentAccounts = usePage().props.auth.payment_account;
const kycStatus = usePage().props.auth.user.kyc_approval;
const confirm = useConfirm();

const requireAccountConfirmation = (accountType) => {
    const messages = {
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

const openDialog = (type) => {
    if (type === 'withdrawal' && kycStatus !== 'verified') {
        requireAccountConfirmation('verification');
    }
    else if (type === 'withdrawal' && paymentAccounts.length === 0) {
        requireAccountConfirmation('crypto');
    } else {
        dialogType.value = type;
        visible.value = true;
    }
}
</script>

<template>
    <div class="flex gap-3 items-center self-stretch">
        <Button
            type="button"
            variant="gray-outlined"
            class="w-full"
            @click="openDialog('transfer')"
            :disabled="!rebateWallet"
        >
            {{ $t('public.transfer') }}
        </Button>
        <Button
            type="button"
            variant="gray-outlined"
            class="w-full"
            @click="openDialog('withdrawal')"
            :disabled="!rebateWallet"
        >
            {{ $t('public.withdrawal') }}
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs md:dialog-sm"
    >
        <template v-if="dialogType === 'transfer'">
            <WalletTransfer
                :rebateWallet="rebateWallet"
                @update:visible="visible = false"
            />
        </template>

        <template v-if="dialogType === 'withdrawal'">
            <WalletWithdrawal
                :wallet="rebateWallet"
                :terms="terms"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
