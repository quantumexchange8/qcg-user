<script setup>
import Withdrawal from "@/Pages/Leaderboard/Partials/Withdrawal.vue";
import WithdrawalHistory from "@/Pages/Leaderboard/Partials/WithdrawalHistory.vue";
import {computed, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import Vue3Autocounter from "vue3-autocounter";

const incentiveWallet = ref();

const getIncentiveData = async () => {
    try {
        const response = await axios.get('/leaderboard/getIncentiveData');
        incentiveWallet.value = response.data.incentiveWallet

    } catch (error) {
        console.error('Error pending counts:', error);
    }
};

getIncentiveData();

watchEffect(() => {
    if (usePage().props.toast !== null || usePage().props.notification !== null) {
        getIncentiveData();
    }
});
</script>

<template>
    <div class="flex flex-col self-stretch items-center rounded-lg bg-white shadow-card">
        <div class="flex flex-col justify-center items-center gap-3 flex-1 self-stretch px-3 md:px-6 py-5 md:pt-6 md:pb-0 border-b border-gray-100">
            <div class="flex flex-col justify-center items-center gap-3 flex-1 self-stretch">
                <span class="self-stretch text-gray-500 text-center">
                    {{ $t('public.available_incentive') }}
                </span>
                <span class="text-xxl text-gray-950 font-semibold"> $ <vue3-autocounter ref="counter" :startAmount="0" :endAmount="incentiveWallet ? Number(incentiveWallet.balance) : 0" :duration="1" separator="," decimalSeparator="." :decimals="2" :autoinit="true" /></span>
            </div>
        </div>
        <div class="flex p-6 justify-center items-center gap-3 self-stretch">
            <Withdrawal :incentiveWallet="incentiveWallet"/>
            <WithdrawalHistory/>
        </div>
    </div>
    
</template>