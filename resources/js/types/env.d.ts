/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue'
    const component: DefineComponent<{}, {}, any>
    export default component
}


import { PageProps as InertiaPageProps } from '@inertiajs/core'

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps {
        auth: {
            user: {
                id: number
                name: string
                email: string
                role: string
            } | null
        }
    }
}