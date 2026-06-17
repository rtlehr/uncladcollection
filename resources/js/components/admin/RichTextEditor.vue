<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import Highlight from '@tiptap/extension-highlight';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const codeView = ref(false);
const htmlCode = ref(props.modelValue ?? '');

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        Highlight,
        Link.configure({
            openOnClick: false,
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
    ],
    editorProps: {
        attributes: {
            class: 'min-h-[400px] rounded-md border bg-background px-3 py-2 text-sm focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        htmlCode.value = html;
        emit('update:modelValue', html);
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (! editor.value) {
            return;
        }

        if (editor.value.getHTML() !== value) {
            editor.value.commands.setContent(value || '', false);
        }

        htmlCode.value = value || '';
    }
);

function toggleCodeView() {
    if (! editor.value) {
        return;
    }

    if (! codeView.value) {
        htmlCode.value = editor.value.getHTML();
        codeView.value = true;
        return;
    }

    editor.value.commands.setContent(htmlCode.value || '', false);
    emit('update:modelValue', htmlCode.value || '');
    codeView.value = false;
}

function updateCode(value: string) {
    htmlCode.value = value;
    emit('update:modelValue', value);
}

function setLink() {
    if (! editor.value) {
        return;
    }

    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Enter URL', previousUrl || 'https://');

    if (url === null) {
        return;
    }

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-if="editor"
            class="flex flex-wrap gap-2 rounded-md border bg-muted/30 p-2"
        >
            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleBold().run()">
                Bold
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleItalic().run()">
                Italic
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleUnderline().run()">
                Underline
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleStrike().run()">
                Strike
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">
                H1
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
                H2
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">
                H3
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setParagraph().run()">
                Paragraph
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleBulletList().run()">
                Bullets
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleOrderedList().run()">
                Numbers
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleBlockquote().run()">
                Quote
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleCodeBlock().run()">
                Code Block
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().toggleHighlight().run()">
                Highlight
            </Button>

            <Button type="button" size="sm" variant="outline" @click="setLink">
                Link
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().unsetLink().run()">
                Unlink
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setTextAlign('left').run()">
                Left
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setTextAlign('center').run()">
                Center
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setTextAlign('right').run()">
                Right
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().undo().run()">
                Undo
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().redo().run()">
                Redo
            </Button>

            <Button type="button" size="sm" variant="outline" @click="toggleCodeView">
                {{ codeView ? 'WYSIWYG View' : 'Code View' }}
            </Button>
        </div>

        <textarea
            v-if="codeView"
            :value="htmlCode"
            class="min-h-[400px] w-full rounded-md border bg-background px-3 py-2 font-mono text-sm"
            @input="updateCode(($event.target as HTMLTextAreaElement).value)"
        />

        <EditorContent
            v-else
            :editor="editor"
        />
    </div>
</template>