<script setup>
import { TransitionGroup } from 'vue'
import { useNotify } from '@/composables/notify'

const { toasts, remove } = useNotify()
</script>

<template>
    <div class="pointer-events-none fixed inset-0 z-[9999]">
        <div class="absolute right-4 top-4 flex w-full max-w-sm flex-col gap-2">
            <TransitionGroup name="toast" tag="div">
                <div
                    v-for="t in toasts" :key="t.id"
                    class="pointer-events-auto rounded-lg px-4 py-3 shadow-lg text-sm text-white"
                    :class="{
            'bg-emerald-600': t.type === 'success',
            'bg-rose-600'   : t.type === 'error',
            'bg-slate-800'  : t.type === 'info'
          }"
                    role="status"
                >
                    <div class="flex items-start gap-3">
                        <span class="flex-1 whitespace-pre-line">{{ t.text }}</span>
                        <button
                            class="opacity-80 hover:opacity-100"
                            @click="remove(t.id)"
                            aria-label="Fermer la notification"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

<style scoped>
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-6px) scale(0.98); }
.toast-enter-active, .toast-leave-active { transition: all .18s ease; }
</style>
