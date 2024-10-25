<script setup>
import Withdrawal from "@/Pages/Leaderboard/Partials/Withdrawal.vue";
import WithdrawalHistory from "@/Pages/Leaderboard/Partials/WithdrawalHistory.vue";
import { ref } from "vue";

</script>

<template>
    <div class="flex flex-col md:h-[292px] items-center rounded-lg bg-white shadow-card">
        <div class="flex flex-col justify-center items-center gap-3 flex-1 self-stretch px-3 md:px-6 py-5 md:pt-6 md:pb-0 border-b border-gray-100">
            <div class="flex flex-col justify-center items-center gap-3 flex-1 self-stretch">
                <span class="self-stretch text-gray-500 text-center">
                    {{ $t('public.available_incentive') }}
                </span>
                <span class="self-stretch text-gray-950 text-center text-xxl font-semibold">
                    {{ `$&nbsp;0.00` }}
                </span>
            </div>
        </div>
        <div class="flex p-6 justify-center items-center gap-3 self-stretch">
            <Withdrawal/>
            <WithdrawalHistory/>
        </div>
    </div>
    
</template>