<!-- resources/js/Components/ConfirmModal.vue -->
<script setup>
const props = defineProps({
    open:         { type: Boolean, default: false },
    title:        { type: String,  default: 'Confirmation' },
    confirmLabel: { type: String,  default: 'Confirmer' },
    cancelLabel:  { type: String,  default: 'Annuler' },
    disabled:     { type: Boolean, default: false },
    showCancel:   { type: Boolean, default: true },
})

const emit = defineEmits(['update:open', 'confirm', 'cancel'])

const close = () => {
    emit('update:open', false)
    emit('cancel')
}
const onConfirm = () => { if (!props.disabled) emit('confirm') }
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">
              <i class="fa-solid fa-exclamation"></i>
            </span>
                        {{ title }}
                    </h3>
                </div>

                <div class="px-5 pt-4 pb-2">
                    <slot />
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        v-if="showCancel"
                        type="button"
                        @click="close"
                        class="px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition"
                    >
                        {{ cancelLabel }}
                    </button>

                    <button
                        type="button"
                        @click="onConfirm"
                        :disabled="disabled"
                        class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 font-semibold text-white
                   disabled:opacity-50 disabled:cursor-not-allowed
                   bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700
                   shadow-sm hover:shadow-md transition"
                    >
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
