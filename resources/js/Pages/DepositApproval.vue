<script setup>
import GuestLayout from '@/Layouts/Guest.vue'
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import dayjs from "dayjs";
import {transactionFormat} from "@/Composables/index.js";
import {ref} from "vue";
import Tag from "primevue/tag";
import {useForm} from "@inertiajs/vue3";
import Button from "primevue/button";
import Image from "primevue/image";

const props = defineProps({
    transaction: Object
});

const {formatAmount} = transactionFormat();
const tooltipText = ref('copy')

function copyToClipboard(text) {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        setTimeout(() => {
            tooltipText.value = 'copy';
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}

const getSeverity = (status) => {
    switch (status) {
        case 'failed':
            return 'danger';

        case 'successful':
            return 'success';

        case 'processing':
            return 'info';
    }
}

const form = useForm({
    transaction_id: props.transaction.id,
    action: ''
})

const handleApproval = (data) => {
    form.action = data;

    form.post(route('depositApproval'))
}
</script>

<template>
    <GuestLayout :title="$t('public.deposit_information')">
        <div class="w-full flex flex-col justify-center items-center gap-8 self-stretch">
            <div class="flex flex-col items-center gap-6 self-stretch">
                <ApplicationLogo class="w-14 h-14 md:w-16 md:h-16" />
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <span class="self-stretch text-gray-950 text-center text-lg font-bold md:text-xl">{{ $t('public.deposit_information') }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.from') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        CRM
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.name') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        {{ transaction.user.first_name }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.email') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        {{ transaction.user.email }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.account') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        {{ transaction.to_meta_login }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.amount') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        $ {{ formatAmount(transaction.amount ?? 0) }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.transfer_amount') }}
                    </div>
                    <div class="text-primary text-sm md:text-base md:w-full font-medium">
                        $ {{ formatAmount(transaction.transaction_amount ?? 0) }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        {{ dayjs(transaction.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.completed_date') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        {{ transaction.approved_at ? dayjs(transaction.approved_at).format('YYYY/MM/DD HH:mm:ss') : '-' }}
                    </div>
                </div>
                <div class="flex md:items-center gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.transaction_number') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        {{ transaction.transaction_number }}
                    </div>
                </div>
                <div class="flex items-start gap-1 self-stretch relative">
                    <Tag
                        v-if="tooltipText === 'copied'"
                        class="absolute -top-6 right-[90px] md:-top-7 md:right-20 !bg-gray-950 !text-white"
                        :value="$t(`public.${tooltipText}`)"
                    ></Tag>
                    <div class="min-w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.txid') }}
                    </div>
                    <div
                        class="text-gray-950 text-sm md:text-base md:w-full font-medium break-all hover:cursor-pointer select-none"
                        @click="copyToClipboard(transaction.txn_hash)"
                    >
                        {{ transaction.txn_hash }}
                    </div>
                </div>
                <div class="flex gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.status') }}
                    </div>
                    <div class="text-gray-950 text-sm md:text-base md:w-full font-medium">
                        <Tag :severity="getSeverity(transaction.status)" :value="$t(`public.${transaction.status}`)" />
                    </div>
                </div>
                <div v-if="transaction.media.length > 0" class="flex gap-1 self-stretch">
                    <div class="w-[140px] md:w-full text-gray-500 text-xs md:text-sm font-medium">
                        {{ $t('public.payment_slip') }}
                    </div>
                    <div
                        class="relative w-full py-2 pl-2 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"
                    >
                        <div class="inline-flex items-center gap-3">
                            <Image
                                :src="transaction.media[0].original_url"
                                preview
                                alt="Payment slip"
                                image-class="w-10 h-8 object-contain rounded" />
                            <div class="text-gray-500">
                                {{ transaction.media[0].name }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="transaction.status === 'processing'"
                    class="flex justify-end items-center pt-6 gap-4 self-stretch w-full"
                >
                    <Button
                        type="button"
                        severity="danger"
                        class="w-full"
                        @click="handleApproval('reject')"
                    >
                        {{ $t('public.reject') }}
                    </Button>
                    <Button
                        type="button"
                        severity="success"
                        class="w-full"
                        @click="handleApproval('approve')"
                    >
                        {{ $t('public.approve') }}
                    </Button>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
