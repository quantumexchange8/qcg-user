<script setup>
import {ref, h, computed} from 'vue';
import { usePage, useForm } from "@inertiajs/vue3";
import Dialog from 'primevue/dialog';
import Button from "@/Components/Button.vue";
import {
    IconDotsVertical,
    // IconDatabaseMinus,
    IconCash,
    IconTool,
    IconReportMoney,
    IconTrashX,
    IconQuestionMark,
    IconHelp
} from '@tabler/icons-vue';
import toast from '@/Composables/toast';
import AccountReport from '@/Pages/Accounts/Partials/AccountReport.vue';
import { useConfirm } from 'primevue/useconfirm';
import { trans } from "laravel-vue-i18n";
import TieredMenu from "primevue/tieredmenu";
import AccountWithdrawal from "@/Pages/Accounts/Partials/AccountWithdrawal.vue";
import ChangeLeverage from "@/Pages/Accounts/Partials/ChangeLeverage.vue";
import MissingAmount from "@/Pages/Accounts/Partials/MissingAmount.vue";

const props = defineProps({
    account: Object,
    type: String,
});

const paymentAccounts = usePage().props.auth.payment_account;
const kycStatus = usePage().props.auth.user.kyc_approval;
const menu = ref();
const visible = ref(false);
const dialogType = ref('');

const items = ref([
    {
        label: 'withdrawal',
        icon: h(IconCash),
        command: () => {
            if (kycStatus !== 'verified') {
                requireAccountConfirmation('verification');
            }
            else if (paymentAccounts.length === 0) {
                requireAccountConfirmation('crypto');
            } else {
                visible.value = true;
                dialogType.value = 'withdrawal';
            }
        },
    },
    {
        label: 'change_leverage',
        icon: h(IconTool),
        command: () => {
            if (props.account.account_type_leverage === 0) {
                visible.value = true;
                dialogType.value = 'change_leverage';
            } else {
                toast.add({
                    title: trans('public.toast_leverage_change_warning'),
                    type: 'warning',
                });
            }
        },
        // account_type: 'standard_account'
    },
    {
        label: 'account_report',
        icon: h(IconReportMoney),
        command: () => {
            visible.value = true;
            dialogType.value = 'account_report';
        },
    },
    {
        label: 'missing_amount',
        icon: h(IconHelp),
        command: () => {
            visible.value = true;
            dialogType.value = 'missing_amount';
        },
    },
    // {
    //     label: 'delete_account',
    //     icon: h(IconTrashX),
    //     command: () => {
    //         requireAccountConfirmation('live');
    //     },
    // },
]);

const filteredItems = computed(() => {
    return items.value.filter(item => {
        if (props.account.asset_master_id) {
            return !(item.label === 'withdrawal' || item.label === 'change_leverage' || item.label === 'delete' || item.label === 'missing_amount' || item.separator);
        }

        if (props.account.is_active === 'inactive') {
            return item.label === 'account_report';
        }

        if (item.account_type) {
            return item.account_type === props.account.account_type;
        }
        return true;
    });
});

const toggle = (event) => {
    menu.value.toggle(event);
};

const form = useForm({
    account_id: props.account.id,
    type: '',
});

const confirm = useConfirm();
const requireAccountConfirmation = (accountType) => {
    const accountNo = props.account.meta_login;
    const messages = {
        // live: {
        //     group: 'headless',
        //     color: 'error',
        //     icon: h(IconTrashX),
        //     header: trans('public.delete_account'),
        //     message: trans('public.delete_account_message', { accountNo }),
        //     cancelButton: trans('public.cancel'),
        //     acceptButton: trans('public.delete'),
        //     action: () => {
        //         form.delete(route('accounts.delete_account'));
        //     },
        // },
        // demo: {
        //     group: 'headless',
        //     color: 'error',
        //     icon: h(IconTrashX),
        //     header: trans('public.delete_demo_account'),
        //     message: trans('public.delete_demo_account_message'),
        //     cancelButton: trans('public.cancel'),
        //     acceptButton: trans('public.delete'),
        //     action: () => {
        //         form.type = 'demo';
        //         form.delete(route('accounts.delete_account'));
        //     },
        // },
        crypto: {
            group: 'headless',
            color: 'primary',
            icon: h(IconQuestionMark),
            header: trans('public.missing_cryptocurrency_wallet'),
            message: trans('public.missing_cryptocurrency_message'),
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

    const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[accountType];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });
};
</script>

<template>
    <Button
        v-if="props.type !== 'demo'"
        variant="gray-text"
        size="sm"
        type="button"
        iconOnly
        pill
        @click="toggle"
        aria-haspopup="true"
        aria-controls="overlay_tmenu"
    >
        <IconDotsVertical size="16" stroke-width="1.25" color="#374151" />
    </Button>

    <Button
        v-if="props.type === 'demo'"
        variant="gray-text"
        size="sm"
        type="button"
        iconOnly
        pill
        @click="requireAccountConfirmation('demo')"
    >
        <IconTrashX size="16" stroke-width="1.25" color="#374151" />
    </Button>

    <!-- Menu -->
    <TieredMenu ref="menu" id="overlay_tmenu" :model="filteredItems" popup >
        <template #item="{ item, props }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
                :class="{ 'hidden': item.disabled }"
            >
                <component :is="item.icon" size="20" stroke-width="1.25" :color="item.label === 'delete_account' ? '#F04438' : '#667085'" />
                <span class="text-sm" :class="{'text-error-500': item.label === 'delete_account'}">{{ $t(`public.${item.label}`) }}</span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs"
        :class="(dialogType === 'account_report') ? 'md:dialog-md' : 'md:dialog-sm'"
    >
        <template v-if="dialogType === 'withdrawal'">
            <AccountWithdrawal
                :account="account"
                :type="type"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'change_leverage'">
            <ChangeLeverage
                :account="account"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'account_report'">
            <AccountReport
                :account="account"
                @update:visible="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'missing_amount'">
            <MissingAmount
                :account="account"
                @update:visible="visible = $event"
            />
        </template>
    </Dialog>
</template>
