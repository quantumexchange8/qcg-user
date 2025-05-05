<script setup>
import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import {onMounted, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import {
    IconSmartHome,
    IconId,
    IconSitemap,
    IconReportMoney,
    IconReport,
    IconSettingsDollar,
    IconTrophy,
    IconDownload,
    IconUserCircle,
    IconGift,
    IconWand,
} from '@tabler/icons-vue';

const user = usePage().props.auth.user;
</script>

<template>
    <div
        tagname="nav"
        aria-label="main"
        class="flex flex-1 flex-col w-full gap-1 items-center max-h-full max-w-full relative"
    >
        <!-- Dashboard -->
        <SidebarLink
            :title="$t('public.sidebar.dashboard')"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
        >
            <template #icon>
                <IconSmartHome :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <SidebarLink
            :title="$t('public.sidebar.highlights')"
            :href="route('highlights')"
            :active="route().current('highlights')"
        >
            <template #icon>
                <IconWand :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Accounts -->
        <!-- <SidebarLink
            :title="$t('public.sidebar.accounts')"
            :href="route('accounts')"
            :active="route().current('accounts')"
        >
            <template #icon>
                <IconId :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

        <!-- Network -->
        <SidebarLink
            :title="$t('public.sidebar.network')"
            :href="route('network')"
            :active="route().current('network')"
        >
            <template #icon>
                <IconSitemap :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Transaction -->
        <SidebarLink
            :title="$t('public.sidebar.transaction')"
            :href="route('transaction')"
            :active="route().current('transaction')"
        >
            <template #icon>
                <IconReportMoney :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Report -->
        <SidebarLink
            :title="$t('public.sidebar.report')"
            :href="route('report')"
            :active="route().current('report')"
            v-if="user.role === 'agent'"
        >
            <template #icon>
                <IconReport :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

         <!-- Rewards -->
        <SidebarLink
            :title="$t('public.sidebar.rewards')"
            :href="route('rewards')"
            :active="route().current('rewards')"
        >
            <template #icon>
                <IconGift :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Rebate Setting -->
<!--        <SidebarLink-->
<!--            :title="$t('public.sidebar.rebate_setting')"-->
<!--            :href="route('rebate_setting')"-->
<!--            :active="route().current('rebate_setting')"-->
<!--            v-if="user.role === 'agent'"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconSettingsDollar :size="20" stroke-width="1.25" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

        <!-- Leaderboard -->
        <SidebarLink
            :title="$t('public.sidebar.leaderboard')"
            :href="route('leaderboard')"
            :active="route().current('leaderboard')"
            v-if="user.role === 'agent'"
        >
            <template #icon>
                <IconTrophy :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- Download Center -->
        <SidebarLink
            :title="$t('public.sidebar.download_center')"
            :href="route('download_center')"
            :active="route().current('download_center')"
        >
            <template #icon>
                <IconDownload :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink>

        <!-- My Profile -->
        <!-- <SidebarLink
            :title="$t('public.sidebar.profile')"
            :href="route('profile')"
            :active="route().current('profile')"
        >
            <template #icon>
                <IconUserCircle :size="20" stroke-width="1.25" />
            </template>
        </SidebarLink> -->

    </div>
</template>
