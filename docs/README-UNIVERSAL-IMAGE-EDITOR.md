# Universal Image Editor — Installation

This package adds the first reusable implementation of the Unclad Collection universal image editor and integrates it into Marketing Campaign images.

## Included

- Reusable Vue image editor dialog
- Drag-to-position interaction
- Zoom slider and buttons
- Rule-of-thirds safe-area overlay
- Fixed reusable output presets
- Browser-side rendered image generation
- Original image retention
- Stored crop/edit metadata for reopening the editor
- Marketing Campaign integration

## Install

Copy all package files into the project, replacing matching files.

Run:

```bash
php artisan migrate
npm run build
php artisan optimize:clear
```

## Storage

Marketing campaign images are stored as:

```text
marketing/campaigns/{uuid}/original/source.ext
marketing/campaigns/{uuid}/rendered/marketing-hero.jpg
```

## Reuse

Import `ImageEditorDialog.vue` into any upload workflow and provide a preset:

```ts
const preset = {
    key: 'avatar',
    label: 'Avatar',
    width: 800,
    height: 800,
    outputType: 'image/jpeg',
    quality: 0.9,
};
```

The `apply` event returns the edited `File`, preview URL, and reusable edit metadata.

## Testing

1. Create an image marketing campaign.
2. Select a JPG, PNG, or WebP image.
3. Confirm the editor opens automatically.
4. Drag and zoom the image.
5. Apply the crop and save the campaign.
6. Confirm both original and rendered files exist in storage.
7. Edit the campaign and reopen the crop editor.
8. Confirm the saved crop state is restored.
9. Verify video campaigns continue to upload normally.
