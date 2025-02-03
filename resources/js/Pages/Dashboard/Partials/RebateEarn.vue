<script setup>
import Button from "@/Components/Button.vue";
import { h,ref} from "vue";
import dayjs from "dayjs";
import {useConfirm} from "primevue/useconfirm";
import {trans} from "laravel-vue-i18n";
import {useForm} from "@inertiajs/vue3";
import Vue3Autocounter from 'vue3-autocounter';
import { IconCash } from "@tabler/icons-vue";

const rebateEarn = ref();

const getRebateEarnData = async () => {
    try {
        const response = await axios.get('/dashboard/getRebateEarnData');
        rebateEarn.value = Number(response.data.rebateEarn);
    } catch (error) {
        console.error('Error fetching live accounts:', error);
    }
};

getRebateEarnData();

const form = useForm({
    rebate_amount: ''
});

const confirm = useConfirm();

const requireConfirmation = () => {
    confirm.require({
        group: 'headless',
        color: 'primary',
        icon: h(IconCash),
        header: trans('public.apply_rebate'),
        actionType: 'rebate',
        message:  trans('public.apply_rebate_message'),
        cancelButton: trans('public.cancel'),
        acceptButton: trans('public.confirm'),
        accept: () => {
            form.rebate_amount = rebateEarn.value;
            form.post(route('dashboard.applyRebate'), {
                onSuccess: () => {
                    getRebateEarnData();
                }
            });
        }
    });
}

</script>

<template>
    <div class="bg-gray-50 p-4 md:p-6 flex flex-col justify-between items-center self-stretch gap-3 w-full">
        <div class="flex flex-col gap-1 justify-center items-center self-stretch w-full">
            <span class="text-center w-full text-xxs md:text-sm text-gray-500">{{ $t('public.total_rebate_earned') }}</span>
            <div class="text-center w-full text-lg md:text-xl font-semibold text-gray-950">
                $ <vue3-autocounter ref="counter" :startAmount="0" :endAmount="rebateEarn" :duration="1" separator="," decimalSeparator="." :decimals="2" :autoinit="true" />
            </div>
        </div>
        <div class="flex flex-col gap-3 items-center self-stretch">
            <Button
                type="button"
                variant="primary-flat"
                class="w-full"
                @click="requireConfirmation"
            >
                {{ $t('public.apply_rebate') }}
            </Button>
        </div>
    </div>
</template>
