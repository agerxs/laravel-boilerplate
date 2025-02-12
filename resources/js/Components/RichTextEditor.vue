<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-xl focus:outline-none max-w-none',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});
</script>

<template>
    <div class="border border-gray-300 rounded-lg">
        <!-- Barre d'outils -->
        <div class="border-b border-gray-300 p-2 flex flex-wrap gap-2">
            <button
                type="button"
                class="p-2 rounded hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor?.isActive('bold') }"
                @click="editor?.chain().focus().toggleBold().run()"
            >
                <span class="font-bold">B</span>
            </button>
            <button
                type="button"
                class="p-2 rounded hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor?.isActive('italic') }"
                @click="editor?.chain().focus().toggleItalic().run()"
            >
                <span class="italic">I</span>
            </button>
            <button
                type="button"
                class="p-2 rounded hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor?.isActive('bulletList') }"
                @click="editor?.chain().focus().toggleBulletList().run()"
            >
                •
            </button>
            <button
                type="button"
                class="p-2 rounded hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor?.isActive('orderedList') }"
                @click="editor?.chain().focus().toggleOrderedList().run()"
            >
                1.
            </button>
            <button
                type="button"
                class="p-2 rounded hover:bg-gray-100"
                @click="editor?.chain().focus().setHardBreak().run()"
            >
                ⏎
            </button>
        </div>

        <!-- Zone d'édition -->
        <div class="p-4">
            <editor-content :editor="editor" />
        </div>
    </div>
</template>

<style>
.ProseMirror {
    min-height: 200px;
}

.ProseMirror p.is-editor-empty:first-child::before {
    color: #adb5bd;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
</style> 