import React, { ImgHTMLAttributes } from 'react';

// yay, typescript!
interface HtmlImageProps extends ImgHTMLAttributes<HTMLImageElement> {
    src: string;
    alt: string;
    className?: string;
    width?: string|number;
    height?: string|number;
}

export default function HtmlImage({ src, alt, ...props}: HtmlImageProps) {
    return (
        <img src={src} alt={alt} {...props} />
    );
}
