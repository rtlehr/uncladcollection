<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const codeView = ref(false);
const htmlCode = ref(props.modelValue ?? '');
const uploadingImage = ref(false);
const selectedImageSrc = ref<string | null>(null);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        Highlight,
        Image.configure({
            inline: false,
            allowBase64: false,
            HTMLAttributes: {
                class: 'blog-image-center blog-image-large',
            },
        }),
        Link.configure({
            openOnClick: false,
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
    ],
    editorProps: {
        attributes: {
            class: 'blog-content prose prose-neutral dark:prose-invert max-w-none min-h-[400px] rounded-md border bg-background px-3 py-2 text-sm focus:outline-none',
        },
        handleClick(view, pos, event) {
            const target = event.target as HTMLElement;

            if (target.tagName === 'IMG') {
                selectedImageSrc.value = (target as HTMLImageElement).src;
            } else {
                selectedImageSrc.value = null;
            }

            return false;
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        htmlCode.value = html;
        emit('update:modelValue', html);
    },
    onSelectionUpdate: ({ editor }) => {
        const attrs = editor.getAttributes('image');
        selectedImageSrc.value = attrs?.src ?? null;
    },
});

const selectedImageClass = computed(() => {
    if (!editor.value) {
        return '';
    }

    return editor.value.getAttributes('image')?.class ?? '';
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) {
            return;
        }

        if (editor.value.getHTML() !== value) {
            editor.value.commands.setContent(value || '', false);
        }

        htmlCode.value = value || '';
    }
);

function emitEditorHtml() {
    if (!editor.value) {
        return;
    }

    const html = editor.value.getHTML();
    htmlCode.value = html;
    emit('update:modelValue', html);
}

function toggleCodeView() {
    if (!editor.value) {
        return;
    }

    if (!codeView.value) {
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
    if (!editor.value) {
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

function buildImageClass(alignment: string, size: string): string {
    return `${alignment} ${size}`;
}

function getCurrentImageSize(): string {
    const currentClass = selectedImageClass.value;

    if (currentClass.includes('blog-image-small')) {
        return 'blog-image-small';
    }

    if (currentClass.includes('blog-image-medium')) {
        return 'blog-image-medium';
    }

    if (currentClass.includes('blog-image-full')) {
        return 'blog-image-full';
    }

    return 'blog-image-large';
}

function getCurrentImageAlignment(): string {
    const currentClass = selectedImageClass.value;

    if (currentClass.includes('blog-image-left')) {
        return 'blog-image-left';
    }

    if (currentClass.includes('blog-image-right')) {
        return 'blog-image-right';
    }

    return 'blog-image-center';
}

function updateSelectedImageClass(alignment?: string, size?: string) {
    if (!editor.value) {
        return;
    }

    const newAlignment = alignment ?? getCurrentImageAlignment();
    const newSize = size ?? getCurrentImageSize();

    editor.value
        .chain()
        .focus()
        .updateAttributes('image', {
            class: buildImageClass(newAlignment, newSize),
        })
        .run();

    emitEditorHtml();
}

function alignImageLeft() {
    updateSelectedImageClass('blog-image-left');
}

function alignImageCenter() {
    updateSelectedImageClass('blog-image-center');
}

function alignImageRight() {
    updateSelectedImageClass('blog-image-right');
}

function setImageSmall() {
    updateSelectedImageClass(undefined, 'blog-image-small');
}

function setImageMedium() {
    updateSelectedImageClass(undefined, 'blog-image-medium');
}

function setImageLarge() {
    updateSelectedImageClass(undefined, 'blog-image-large');
}

function setImageFull() {
    updateSelectedImageClass('blog-image-center', 'blog-image-full');
}

function removeSelectedImage() {
    if (!editor.value) {
        return;
    }

    editor.value.chain().focus().deleteSelection().run();
    selectedImageSrc.value = null;
    emitEditorHtml();
}

async function uploadImage() {
    if (!editor.value || uploadingImage.value) {
        return;
    }

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = async () => {
        const file = input.files?.[0];

        if (!file) {
            return;
        }

        uploadingImage.value = true;

        try {
            const formData = new FormData();
            formData.append('image', file);

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

            const response = await fetch('/admin/blog-posts/upload-content-image', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                alert('Image upload failed.');
                return;
            }

            const data = await response.json();

            editor.value
                ?.chain()
                .focus()
                .setImage({
                    src: data.url,
                    alt: file.name,
                    class: 'blog-image-center blog-image-large',
                })
                .run();

            emitEditorHtml();
        } catch (error) {
            alert('Image upload failed.');
        } finally {
            uploadingImage.value = false;
        }
    };

    input.click();
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

            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="uploadingImage"
                @click="uploadImage"
            >
                {{ uploadingImage ? 'Uploading...' : 'Image' }}
            </Button>

            <Button type="button" size="sm" variant="outline" @click="setLink">
                Link
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().unsetLink().run()">
                Unlink
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setTextAlign('left').run()">
                Text Left
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setTextAlign('center').run()">
                Text Center
            </Button>

            <Button type="button" size="sm" variant="outline" @click="editor.chain().focus().setTextAlign('right').run()">
                Text Right
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

        <div
            v-if="editor && selectedImageSrc && !codeView"
            class="flex flex-wrap items-center gap-2 rounded-md border bg-background p-2 shadow-sm"
        >
            <span class="mr-2 text-xs font-semibold text-muted-foreground">
                Image:
            </span>

            <Button type="button" size="sm" variant="outline" @click="alignImageLeft">
                Left Wrap
            </Button>

            <Button type="button" size="sm" variant="outline" @click="alignImageCenter">
                Center
            </Button>

            <Button type="button" size="sm" variant="outline" @click="alignImageRight">
                Right Wrap
            </Button>

            <Button type="button" size="sm" variant="outline" @click="setImageSmall">
                Small
            </Button>

            <Button type="button" size="sm" variant="outline" @click="setImageMedium">
                Medium
            </Button>

            <Button type="button" size="sm" variant="outline" @click="setImageLarge">
                Large
            </Button>

            <Button type="button" size="sm" variant="outline" @click="setImageFull">
                Full
            </Button>

            <Button type="button" size="sm" variant="destructive" @click="removeSelectedImage">
                Remove
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