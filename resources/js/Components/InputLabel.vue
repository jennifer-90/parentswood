<script setup>
defineProps({
    value: {
        type: String,
    },
    customClass: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <label :class="`block text-sm font-medium ${customClass}`">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
