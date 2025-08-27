<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page  = usePage()
const flash = computed(() => page.props.flash || {})   // { success?, error?, info?, warning? }
const hide  = ref(false)

watch(
    () => flash.value,
    (val) => {
        if (val && (val.success || val.error || val.info || val.warning)) {
            hide.value = false
            // auto-hide après 4s
            setTimeout(() => (hide.value = true), 4000)
        }
    },
    { immediate: true, deep: true }
)
</script>

<template>
    <div v-if="!hide && (flash.success || flash.error || flash.info || flash.warning)" class="mb-4">
        <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-2 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i>
            <span>{{ flash.success }}</span>
        </div>
        <div v-else-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-2 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ flash.error }}</span>
        </div>
        <div v-else-if="flash.warning" class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-2 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ flash.warning }}</span>
        </div>
        <div v-else-if="flash.info" class="rounded-lg border border-blue-200 bg-blue-50 text-blue-800 px-4 py-2 flex items-center gap-2">
            <i class="fa-solid fa-circle-info"></i>
            <span>{{ flash.info }}</span>
        </div>
    </div>
</template>
