<script setup>
import Button from "@/Components/Button.vue";
import {ref, watchEffect} from "vue";
import {useForm, usePage, Link} from "@inertiajs/vue3";
import {IconInfoCircle} from "@tabler/icons-vue";
import Label from "@/Components/InputLabel.vue";
import Dialog from "primevue/dialog";
import InputNumber from 'primevue/inputnumber';
import {transactionFormat} from "@/Composables/index.js";


// const props = defineProps({
//     masterAccount: Object,
//     terms: Object,
// })

const visible = ref(false)
const transactionVisible = ref(false)

const closeDialog = () => {
    visible.value = false;
}

const form = useForm({
    account_type: '',
    leverage: '',
});

const submitForm = () => {

};

const leverages = ref();
const selectedLeverage = ref();

// const getResults = async () => {
//     try {
//         const response = await axios.get('/member/getFilterData');
//         countries.value = response.data.countries;
//         uplines.value = response.data.uplines;
//     } catch (error) {
//         console.error('Error changing locale:', error);
//     }
// };

// getResults();

// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getResults();
//     }
// });
</script>

<template>
    <Button
        type="button" variant="gray-outlined" class="w-full px-4 py-2"
        @click="visible = true"
    >
        {{ $t('public.deposit') }}
    </Button>

    <Dialog v-model:visible="visible" modal header=" " class="dialog-sm">
        <div class="flex flex-col w-full pb-6 gap-8">
            <div class="w-full h-[75px] md:h-[200px]">
                <img src="/assets/deposit.png" alt="No data" class="absolute top-0 left-0 ">
            </div>
            <div class="flex flex-col w-[148px] md:w-[204px] gap-1">
                <span class="text-sm md:text-md text-gray-950 font-bold">
                    {{ $t('public.ctrader_deposit') }}
                </span>   
                <span class="text-xs md:text-sm text-gray-700">
                    {{ $t('public.ctrader_deposit_caption') }}
                </span>
            </div>
        </div>
        <div class="pt-6 w-full">
            <Button variant="primary-flat" type="button" class="justify-center w-full" @click="transactionVisible = true">
                {{$t('public.continue')}}
            </Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="transactionVisible" modal :header="$t('public.deposit')" class="dialog-sm">
        <div class="flex flex-col py-6 gap-8">
            <div class="flex flex-col gap-1 px-8 py-3 bg-gray-100">
                <span class="text-xs text-center text-gray-500">#8000123 - {{ $t('public.current_account_balance') }}</span>
                <span class="text-lg text-center font-bold text-gray-950">$ 1,000.00</span>
            </div>
            <div class="flex flex-row gap-3 items-start">
                <div><IconInfoCircle size="24" color="#030712" stroke-width="2"/></div>
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-semibold text-gray-950">{{ $t('public.deposit_information') }}</span>
                    <span class="text-sm text-gray-700">{{ $t('public.deposit_information_caption') }}</span>
                </div>
            </div>
        </div>
        <div class="pt-6 w-full">
            <Button variant="primary-flat" type="button" class="justify-center w-full">
                {{$t('public.deposit_now')}}
            </Button>
        </div>
    </Dialog>

</template>
