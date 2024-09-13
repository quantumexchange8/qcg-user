<script setup>
defineProps({
    value: {
        type: String,
    },
    invalid: Boolean
});
</script>

<template>
    <label
        class="block text-sm"
        :class="{
            'text-gray-700': !invalid,
            'text-error-500': invalid
        }"
    >
        <div v-if="value">
            {{ value }}
        </div>
        <div v-else>
            <slot />
        </div>
    </label>
</template>
