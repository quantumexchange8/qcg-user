<script setup>
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import {usePage} from "@inertiajs/vue3";
import { Link } from '@inertiajs/vue3'
import {sidebarState} from "@/Composables/index.js";
import { IconCircleCheckFilled, IconExclamationCircleFilled, IconClockFilled } from "@tabler/icons-vue";

const user = usePage().props.auth.user;
const $page = usePage();
</script>

<template>
    <div class="flex flex-col items-center self-stretch pb-1">
        <Link
            :href="route('profile')"
            :class="[
            'flex items-center gap-2 self-stretch rounded group select-none cursor-pointer transition-colors px-3 py-2',
            {
                'text-gray-700 hover:bg-gray-100 focus:bg-gray-100': !route().current('profile'),
                'text-primary-600 bg-primary-100': route().current('profile'),
            },
        ]"
        >
            <div class="relative infline-flex items-center">
                <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 grow-0 relative">
                    <div v-if="$page.props.auth.profile_image" class="h-full w-full">
                        <img :src="$page.props.auth.profile_image" alt="Profile Image" class="h-full w-full"/>
                    </div>
                    <div v-else>
                        <DefaultProfilePhoto />
                    </div>
                </div>
                <div class="absolute -right-0.5 -bottom-1 bg-white rounded-full">
                    <IconCircleCheckFilled v-if="user.kyc_approval === 'verified'" size="12" stroke-width="1.25" class="text-success-500 grow-0 shrink-0" />
                    <IconClockFilled v-else-if="user.kyc_approval === 'pending'" size="12" stroke-width="1.25" class="text-warning-500 grow-0 shrink-0" />
                    <IconExclamationCircleFilled v-else size="12" stroke-width="1.25" class="text-error-500 grow-0 shrink-0" />
                </div>
            </div>

            <div v-show="sidebarState.isOpen || sidebarState.isHovered" class="flex flex-col items-start">
                <span
                    class="text-sm font-medium truncate max-w-[144px]"
                    :class="{
                        'text-gray-700': !route().current('profile'),
                        'text-gray-700': route().current('profile'),
                    }"
                >{{ user.chinese_name ?? user.first_name }}</span>
                <span
                    class="text-xs truncate max-w-[144px]"
                    :class="{
                        'text-gray-500': !route().current('profile'),
                        'text-gray-500': route().current('profile'),
                    }"
                >{{ user.email }}</span>
            </div>
        </Link>
    </div>
</template>
