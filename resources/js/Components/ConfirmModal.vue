<script setup>
import { Teleport } from 'vue'
import { useConfirm } from '@/composables/confirm'
const { state, close } = useConfirm()
</script>

<template>
    <Teleport to="body">
        <div v-if="state.open" class="fixed inset-0 z-[10000] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="close(false)"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">
                    {{ state.title }}
                </h3>
                <p class="mb-6 text-gray-600 whitespace-pre-line">
                    {{ state.message }}
                </p>

                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50"
                        @click="close(false)"
                    >
                        {{ state.cancelText }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-red-300 bg-red-600/10 px-4 py-2 text-red-700 hover:bg-red-600/20"
                        @click="close(true)"
                    >
                        {{ state.confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
