import React, { ImgHTMLAttributes, SourceHTMLAttributes } from 'react';

// define SourceProps so we can accept an array
interface SourceProps extends SourceHTMLAttributes<HTMLSourceElement> {}

interface HeroProps extends ImgHTMLAttributes<HTMLPictureElement> {
    src: string;
    alt: string;
    sources?: SourceProps[];
}

export default function Hero({src, alt, sources = []}:HeroProps) {
    const sourceList = sources.map(source => 
        <source srcSet={source.srcSet} media={source.media} />
    );
    return (
        <picture>
            {sourceList}
            <img src={src} alt={alt} width="100%" height="auto" />
        </picture>
    );
}