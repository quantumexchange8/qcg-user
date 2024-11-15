<script setup>
import {ref} from "vue";
import Button from "@/Components/Button.vue";
import Dialog from "primevue/dialog";

const agreementVisible = ref(false)

</script>

<template>
    <Button
        variant="primary-text"
        class="text-xs !p-0"
        @click="agreementVisible = true"
        type="button"
    >
    {{ $t('public.trading_account_agreement') }}
    </Button>

    <Dialog v-model:visible="agreementVisible" modal :header="$t('public.trading_account_agreement')" class="dialog-xs md:dialog-lg">
        <div class="flex flex-col pt-6 gap-8 text-sm">
            <span>{{ $t('public.trading_account_agreement_caption') }}</span>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_1') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_1') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_2') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_2') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_3') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_3') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_4') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_4') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_5') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_5') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_6') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_6') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_7') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_7') }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-950">{{ $t('public.trading_account_agreement_8') }}</span>
                    <span>{{ $t('public.trading_account_agreement_caption_8') }}</span>
                </div>
            </div>
        </div>
    </Dialog>
</template>
