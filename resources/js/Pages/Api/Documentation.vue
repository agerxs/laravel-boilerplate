<template>
    <AppLayout title="Documentation API">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Documentation API
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <redoc
                        spec-url="/api/doc"
                        :scroll-y-offset="80"
                        :theme="{
                            colors: {
                                primary: {
                                    main: '#4F46E5',
                                    light: '#818CF8',
                                    dark: '#3730A3',
                                },
                            },
                        }"
                        :options="{
                            nativeScrollbars: true,
                            hideDownloadButton: true,
                            hideHostname: true,
                            noAutoAuth: true,
                            pathInMiddlePanel: true,
                            showObjectSchemaExamples: true,
                            sideNavStyle: 'path-only',
                            sortPropsAlphabetically: true,
                            untitledSpec: 'Documentation API Meeting Lara',
                        }"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Redoc from 'redoc-vue'

export default defineComponent({
    components: {
        AppLayout,
        Redoc,
    },
})
</script> 