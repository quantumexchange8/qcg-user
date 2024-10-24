<script setup>
import Dialog from "primevue/dialog";
import ConfirmDialog from 'primevue/confirmdialog';
import Button from "@/Components/Button.vue";
import { ref } from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Label from "@/Components/InputLabel.vue";

const props = defineProps({
    // tradingAccount: Object,
})

const { formatAmount } = transactionFormat();

const visible = ref(false);
const agreementVisible = ref(false)
const confirmVisible = ref(false)

const wallets = ref();
const selectedWallet = ref();

const closeDialog = () => {
    visible.value = false;
}

</script>

<template>
    <Button
        type="button"
        variant="primary-flat"
        class="flex-1"
        @click="visible = true"
    >
        {{ $t('public.withdrawal') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.incentive_withdrawal')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col w-full py-6 gap-8">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                        <span class="text-xs text-center text-gray-500">{{ $t('public.available_incentive') }}</span>
                        <span class="text-lg text-center font-bold text-gray-950">$ {{ formatAmount(props.tradingAccount.balance) }}</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label
                            for="receiving_wallet"
                        >{{ $t('public.receiving_wallet') }}</Label>
                        <Select v-model="selectedWallet" :options="wallets" :placeholder="$t('public.select_wallet')" class="w-full" />
                        <span>test_address</span>
                    </div>
                </div>
                <div class="flex justify-center gap-3 items-center self-stretch">
                    <span class="text-xs text-gray-700">
                        {{ $t('public.acknowledgement') }}
                        <Button
                            class="text-xs font-medium text-primary-500 hover:text-primary-600 focus:text-primary-600 !p-0"
                            @click="agreementVisible = true"
                            type="button"
                        >
                        {{ $t('public.trading_account_agreement') }}
                        </Button>.
                    </span>   
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 pt-6 w-full">
                <Button variant="gray-outlined" type="button" class="justify-center" @click="closeDialog">
                    {{$t('public.cancel')}}
                </Button>
                <Button variant="primary-flat" type="button" class="justify-center" @click="submitForm" :disabled="form.processing">{{$t('public.open')}}</Button>
            </div>
        </form>
    </Dialog>

    <!-- <Dialog v-model:visible="visible" modal :header="$t('public.trading_account_agreement')" class="dialog-lg">
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
    </Dialog> -->

    <Dialog v-model:visible="agreementVisible" modal :header="$t('public.trading_account_agreement')" class="dialog-lg">
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

    <Dialog v-model:visible="confirmVisible" modal :header="$t('public.trading_account_agreement')" class="dialog-lg">
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
