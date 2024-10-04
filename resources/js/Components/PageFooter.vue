<script setup>
import dayjs from "dayjs";
</script>

<template>
    <footer class="flex flex-col flex-shrink-0 md:mt-[100px] mt-[80px] gap-3 p-5 text-x bg-white">
        <div class="flex flex-col gap-1">
            <p class=" text-gray-700 text-xs font-semibold">
                {{ $t('public.risk_warning') }}
            </p>
            <p class=" text-gray-500 text-xs">
                {{ $t('public.risk_caption') }}
            </p>
        </div>
        <p
        class=" text-gray-500 text-xs"
        >
            © {{ dayjs().year() }} Quantum Capital Group. All rights reserved.
        </p>
    </footer>
</template>
