import { ref, readonly } from 'vue'

let _id = 1
const toasts = ref([]) // { id, type: 'success'|'error'|'info', text }

function push(type, text, timeout = 3500) {
    const id = _id++
    toasts.value.push({ id, type, text })
    if (timeout > 0) {
        setTimeout(() => remove(id), timeout)
    }
}

function remove(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
}

export function useNotify() {
    return {
        toasts: readonly(toasts),
        remove,
        notify: (text, timeout) => push('info', text, timeout),
        success: (text, timeout) => push('success', text, timeout),
        error: (text, timeout) => push('error', text, timeout),
        info: (text, timeout) => push('info', text, timeout),
    }
}
