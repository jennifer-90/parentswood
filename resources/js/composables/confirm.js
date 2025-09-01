import { reactive } from 'vue'

const state = reactive({
    open: false,
    title: '',
    message: '',
    confirmText: 'Confirmer',
    cancelText: 'Annuler',
    _resolver: null,
})

export function useConfirm() {
    function close(result) {
        if (state._resolver) state._resolver(!!result)
        state.open = false
        state._resolver = null
    }

    function open(opts = {}) {
        return new Promise(resolve => {
            state.title = opts.title ?? 'Confirmation'
            state.message = opts.message ?? 'Êtes-vous sûr ?'
            state.confirmText = opts.confirmText ?? 'Confirmer'
            state.cancelText = opts.cancelText ?? 'Annuler'
            state._resolver = resolve
            state.open = true
        })
    }

    return { state, open, close }
}
