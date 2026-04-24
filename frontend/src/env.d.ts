import type { ComponentPublicInstance } from 'vue';

declare module 'vue' {
    export type GenericComponentInstance = ComponentPublicInstance;
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';

    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;

    export default component;
}
