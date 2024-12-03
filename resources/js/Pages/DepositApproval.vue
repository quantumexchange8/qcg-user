<script setup>
import GuestLayout from '@/Layouts/Guest.vue'
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import dayjs from "dayjs";
import {transactionFormat} from "@/Composables/index.js";
import {ref} from "vue";
import Tag from "primevue/tag";

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
                        <Tag severity="success" :value="$t(`public.${transaction.status}`)" />
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
