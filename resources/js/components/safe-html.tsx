import DOMPurify from 'isomorphic-dompurify';

// I'd prefer to make the wrapper element dynamic, but a div will do for now.
/** CKEditor fields store HTML; sanitize before rendering. */
export function SafeHtml({ html, className }: { html?: string; className?: string }) {
    if (!html) return null;
    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(html) }}
        />
    );
}
