export function readingTime(html: string | null): string {
    const text = (html ?? '').replace(/<[^>]+>/g, ' ');
    const words = text.trim().split(/\s+/).filter(Boolean).length;

    const minutes = Math.max(1, Math.ceil(words / 220));

    return `${minutes} min read`;
}