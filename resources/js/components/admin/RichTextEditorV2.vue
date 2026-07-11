<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import { computed, ref, watch } from 'vue';

import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    Heading1,
    Heading2,
    Heading3,
    Highlighter,
    ImageIcon,
    Images,
    Italic,
    LinkIcon,
    List,
    ListOrdered,
    Pilcrow,
    Quote,
    Redo2,
    RemoveFormatting,
    Strikethrough,
    Trash2,
    UnderlineIcon,
    Undo2,
    WrapText,
} from '@lucide/vue';

import { Button } from '@/components/ui/button';
import ImagePickerDialog, {
    type LibraryImage,
} from '@/components/admin/ImagePickerDialog.vue';

const props = defineProps<{ modelValue: string }>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const codeView = ref(false);
const htmlCode = ref(props.modelValue ?? '');
const uploadingImage = ref(false);
const selectedImage = ref(false);
const selectedImageSrc = ref<string | null>(null);
const libraryOpen = ref(false);

const BlogImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),

            class: {
                default: 'blog-image-center blog-image-large',
                parseHTML: (element) => element.getAttribute('class'),
                renderHTML: (attributes) => ({
                    class: attributes.class,
                }),
            },

            alt: {
                default: null,
                parseHTML: (element) => element.getAttribute('alt'),
                renderHTML: (attributes) => ({
                    alt: attributes.alt,
                }),
            },

            imageId: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-image-id'),
                renderHTML: (attributes) =>
                    attributes.imageId
                        ? { 'data-image-id': attributes.imageId }
                        : {},
            },

            imageSlug: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-image-slug'),
                renderHTML: (attributes) =>
                    attributes.imageSlug
                        ? { 'data-image-slug': attributes.imageSlug }
                        : {},
            },

            photographer: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-photographer'),
                renderHTML: (attributes) =>
                    attributes.photographer
                        ? { 'data-photographer': attributes.photographer }
                        : {},
            },

            publicUrl: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-public-url'),
                renderHTML: (attributes) =>
                    attributes.publicUrl
                        ? { 'data-public-url': attributes.publicUrl }
                        : {},
            },
        };
    },
});

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            link: false,
            underline: false,
        }),
        Underline,
        Highlight,
        BlogImage.configure({
            inline: false,
            allowBase64: false,
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
            class: 'blog-content prose prose-neutral dark:prose-invert max-w-none min-h-[520px] rounded-b-md bg-background px-8 py-8 text-base leading-7 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
            role: 'textbox',
            'aria-multiline': 'true',
            'aria-label': 'Blog post content editor',
        },

        handleClickOn(view, pos, node) {
            if (node.type.name !== 'image') {
                selectedImage.value = false;
                selectedImageSrc.value = null;
                return false;
            }

            selectedImage.value = true;
            selectedImageSrc.value = node.attrs.src;

            return false;
        },

        handleClick(view, pos, event) {
            const target = event.target as HTMLElement;

            if (target.tagName !== 'IMG') {
                selectedImage.value = false;
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
});

const wordCount = computed(() => {
    if (!editor.value) return 0;

    return editor.value
        .getText()
        .trim()
        .split(/\s+/)
        .filter(Boolean).length;
});

const characterCount = computed(() => editor.value?.getText().length ?? 0);

const readingTime = computed(() => Math.max(1, Math.ceil(wordCount.value / 220)));

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;

        if (editor.value.getHTML() !== value) {
            editor.value.commands.setContent(value || '', false);
        }

        htmlCode.value = value || '';
    }
);

function emitEditorHtml() {
    if (!editor.value) return;

    const html = editor.value.getHTML();
    htmlCode.value = html;
    emit('update:modelValue', html);
}

function toggleCodeView() {
    if (!editor.value) return;

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
    if (!editor.value) return;

    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Enter URL', previousUrl || 'https://');

    if (url === null) return;

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run();
}

function imageClass(alignment: string, size: string): string {
    return `${alignment} ${size}`;
}

function findSelectedImage() {
    if (!editor.value || !selectedImageSrc.value) {
        alert('Click the image first, then choose an image option.');
        return null;
    }

    let found: { node: any; pos: number } | null = null;

    editor.value.state.doc.descendants((node, pos) => {
        if (node.type.name === 'image' && node.attrs.src === selectedImageSrc.value) {
            found = { node, pos };
            return false;
        }

        return true;
    });

    if (!found) {
        alert('Click the image first, then choose an image option.');
        selectedImage.value = false;
        selectedImageSrc.value = null;
    }

    return found;
}

function currentImageClass(): string {
    const found = findSelectedImage();

    return found?.node?.attrs?.class ?? '';
}

function currentAlignment(): string {
    const current = currentImageClass();

    if (current.includes('blog-image-left')) return 'blog-image-left';
    if (current.includes('blog-image-right')) return 'blog-image-right';

    return 'blog-image-center';
}

function currentSize(): string {
    const current = currentImageClass();

    if (current.includes('blog-image-small')) return 'blog-image-small';
    if (current.includes('blog-image-medium')) return 'blog-image-medium';
    if (current.includes('blog-image-full')) return 'blog-image-full';

    return 'blog-image-large';
}

function updateImage(alignment?: string, size?: string) {
    if (!editor.value) return;

    const found = findSelectedImage();

    if (!found) return;

    const newClass = imageClass(
        alignment ?? currentAlignment(),
        size ?? currentSize()
    );

    const { state, view } = editor.value;

    view.dispatch(
        state.tr.setNodeMarkup(found.pos, undefined, {
            ...found.node.attrs,
            class: newClass,
        })
    );

    selectedImage.value = true;
    selectedImageSrc.value = found.node.attrs.src;

    emitEditorHtml();
}

function removeImage() {
    if (!editor.value) return;

    const found = findSelectedImage();

    if (!found) return;

    const { state, view } = editor.value;

    view.dispatch(
        state.tr.delete(
            found.pos,
            found.pos + found.node.nodeSize
        )
    );

    selectedImage.value = false;
    selectedImageSrc.value = null;

    emitEditorHtml();
}

function insertImage(src: string, alt: string | null = null) {
    if (!editor.value) return;

    editor.value
        .chain()
        .focus()
        .setImage({
            src,
            alt: alt ?? '',
            class: 'blog-image-center blog-image-large',
        })
        .run();

    selectedImage.value = true;
    selectedImageSrc.value = src;

    emitEditorHtml();
}

function insertLibraryManagedImage(image: LibraryImage) {
    if (!editor.value) return;

    const src = image.high_res_url ?? image.thumbnail_url ?? image.icon_url;

    if (!src) {
        alert('This image does not have an available URL.');
        return;
    }

    editor.value
        .chain()
        .focus()
        .setImage({
            src,
            alt: image.title,
            class: 'blog-image-center blog-image-large',
            imageId: String(image.id),
            imageSlug: image.slug,
            photographer: image.photographer ?? '',
            publicUrl: image.public_url ?? '',
        })
        .run();

    selectedImage.value = true;
    selectedImageSrc.value = src;

    emitEditorHtml();
}

async function uploadImage() {
    if (!editor.value || uploadingImage.value) return;

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';

    input.onchange = async () => {
        const file = input.files?.[0];

        if (!file) return;

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

            insertImage(data.url, file.name);
        } catch {
            alert('Image upload failed.');
        } finally {
            uploadingImage.value = false;
        }
    };

    input.click();
}

function openImageLibrary() {
    libraryOpen.value = true;
}

function closeImageLibrary() {
    libraryOpen.value = false;
}

function insertLibraryImage(image: LibraryImage) {
    insertLibraryManagedImage(image);
    closeImageLibrary();
}
</script>

<template>
    <div class="overflow-hidden rounded-md border bg-background">
        <div v-if="editor" class="space-y-3 border-b bg-muted/30 p-3">
            <p id="editor-toolbar-help" class="sr-only">
                Use the formatting toolbar or standard keyboard shortcuts to edit the article content.
            </p>
            <div class="flex flex-wrap items-center gap-1.5" role="toolbar" aria-label="Text formatting toolbar" aria-describedby="editor-toolbar-help">
                <div class="flex flex-wrap gap-1.5 rounded-md border bg-background p-1">
                    <Button type="button" size="icon" variant="ghost" title="Bold" :aria-pressed="editor.isActive('bold')" @click="editor.chain().focus().toggleBold().run()" aria-label="Bold">
                        <Bold class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Italic" :aria-pressed="editor.isActive('italic')" @click="editor.chain().focus().toggleItalic().run()" aria-label="Italic">
                        <Italic class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Underline" :aria-pressed="editor.isActive('underline')" @click="editor.chain().focus().toggleUnderline().run()" aria-label="Underline">
                        <UnderlineIcon class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Strikethrough" :aria-pressed="editor.isActive('strike')" @click="editor.chain().focus().toggleStrike().run()" aria-label="Strikethrough">
                        <Strikethrough class="h-4 w-4" />
                    </Button>
                </div>

                <div class="flex flex-wrap gap-1.5 rounded-md border bg-background p-1">
                    <Button type="button" size="icon" variant="ghost" title="Heading 1" :aria-pressed="editor.isActive('heading', { level: 1 })" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" aria-label="Heading 1">
                        <Heading1 class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Heading 2" :aria-pressed="editor.isActive('heading', { level: 2 })" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" aria-label="Heading 2">
                        <Heading2 class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Heading 3" :aria-pressed="editor.isActive('heading', { level: 3 })" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" aria-label="Heading 3">
                        <Heading3 class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Paragraph" :aria-pressed="editor.isActive('paragraph')" @click="editor.chain().focus().setParagraph().run()" aria-label="Paragraph">
                        <Pilcrow class="h-4 w-4" />
                    </Button>
                </div>

                <div class="flex flex-wrap gap-1.5 rounded-md border bg-background p-1">
                    <Button type="button" size="icon" variant="ghost" title="Bullet List" :aria-pressed="editor.isActive('bulletList')" @click="editor.chain().focus().toggleBulletList().run()" aria-label="Bullet List">
                        <List class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Numbered List" :aria-pressed="editor.isActive('orderedList')" @click="editor.chain().focus().toggleOrderedList().run()" aria-label="Numbered List">
                        <ListOrdered class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Quote" :aria-pressed="editor.isActive('blockquote')" @click="editor.chain().focus().toggleBlockquote().run()" aria-label="Quote">
                        <Quote class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Code Block" :aria-pressed="editor.isActive('codeBlock')" @click="editor.chain().focus().toggleCodeBlock().run()" aria-label="Code Block">
                        <Code class="h-4 w-4" />
                    </Button>
                </div>

                <div class="flex flex-wrap gap-1.5 rounded-md border bg-background p-1">
                    <Button type="button" size="icon" variant="ghost" title="Highlight" :aria-pressed="editor.isActive('highlight')" @click="editor.chain().focus().toggleHighlight().run()" aria-label="Highlight">
                        <Highlighter class="h-4 w-4" />
                    </Button>

                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        title="Upload Image"
                        :disabled="uploadingImage"
                        @click="uploadImage"
                     aria-label="Upload Image">
                        <ImageIcon class="h-4 w-4" />
                    </Button>

                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        title="Insert From Library"
                        @click="openImageLibrary"
                     aria-label="Insert From Library">
                        <Images class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Add Link" @click="setLink" aria-label="Add Link">
                        <LinkIcon class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Remove Link" @click="editor.chain().focus().unsetLink().run()" aria-label="Remove Link">
                        <RemoveFormatting class="h-4 w-4" />
                    </Button>
                </div>

                <div class="flex flex-wrap gap-1.5 rounded-md border bg-background p-1">
                    <Button type="button" size="icon" variant="ghost" title="Text Left" @click="editor.chain().focus().setTextAlign('left').run()" aria-label="Text Left">
                        <AlignLeft class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Text Center" @click="editor.chain().focus().setTextAlign('center').run()" aria-label="Text Center">
                        <AlignCenter class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Text Right" @click="editor.chain().focus().setTextAlign('right').run()" aria-label="Text Right">
                        <AlignRight class="h-4 w-4" />
                    </Button>
                </div>

                <div class="flex flex-wrap gap-1.5 rounded-md border bg-background p-1">
                    <Button type="button" size="icon" variant="ghost" title="Undo" @click="editor.chain().focus().undo().run()" aria-label="Undo">
                        <Undo2 class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="icon" variant="ghost" title="Redo" @click="editor.chain().focus().redo().run()" aria-label="Redo">
                        <Redo2 class="h-4 w-4" />
                    </Button>

                    <Button type="button" size="sm" variant="ghost" title="HTML View" @click="toggleCodeView" aria-label="HTML View">
                        {{ codeView ? 'WYSIWYG' : 'HTML' }}
                    </Button>
                </div>
            </div>

            <div
                v-if="uploadingImage"
                class="rounded-md border border-dashed bg-background px-3 py-2 text-xs text-muted-foreground" role="status" aria-live="polite"
            >
                Uploading image...
            </div>

            <div
                v-if="selectedImage && !codeView"
                class="rounded-md border bg-background p-3 shadow-sm"
            >
                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <ImageIcon class="h-4 w-4" />
                    Image Tools
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage('blog-image-left')">
                        <WrapText class="mr-2 h-4 w-4" />
                        Left Wrap
                    </Button>

                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage('blog-image-center')">
                        <AlignCenter class="mr-2 h-4 w-4" />
                        Center
                    </Button>

                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage('blog-image-right')">
                        <WrapText class="mr-2 h-4 w-4 rotate-180" />
                        Right Wrap
                    </Button>

                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage(undefined, 'blog-image-small')">
                        Small
                    </Button>

                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage(undefined, 'blog-image-medium')">
                        Medium
                    </Button>

                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage(undefined, 'blog-image-large')">
                        Large
                    </Button>

                    <Button type="button" size="sm" variant="outline" @mousedown.prevent @click="updateImage('blog-image-center', 'blog-image-full')">
                        Full Width
                    </Button>

                    <Button type="button" size="sm" variant="destructive" @mousedown.prevent @click="removeImage">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Remove
                    </Button>
                </div>
            </div>
        </div>

        <label v-if="codeView" for="blog-html-source" class="sr-only">Blog post HTML source</label>

        <textarea
            v-if="codeView"
            id="blog-html-source"
            aria-label="Blog post HTML source"
            :value="htmlCode"
            class="min-h-[520px] w-full border-0 bg-background px-6 py-6 font-mono text-sm focus:outline-none"
            @input="updateCode(($event.target as HTMLTextAreaElement).value)"
        />

        <EditorContent v-else :editor="editor" />

        <div class="flex flex-wrap justify-between gap-3 border-t bg-muted/20 px-4 py-2 text-xs text-muted-foreground" role="status" aria-live="polite">
            <span>{{ wordCount }} words</span>
            <span>{{ characterCount }} characters</span>
            <span>{{ readingTime }} min read</span>
        </div>

        <ImagePickerDialog
            :open="libraryOpen"
            @close="closeImageLibrary"
            @select="insertLibraryImage"
        />
    </div>
</template>