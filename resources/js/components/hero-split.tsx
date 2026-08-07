import React, { ImgHTMLAttributes, ReactNode, SourceHTMLAttributes } from 'react';

interface HeroSplitProps extends ImgHTMLAttributes<HTMLPictureElement> {
    src: string;
    alt: string;
    pictureClassName?: string;
    imageClassName?: string;
    sources?: SourceHTMLAttributes<HTMLSourceElement>[];
    children?: ReactNode;
}

/**
 * Desktop (lg+): text left, image right, side-by-side columns -- the image
 * sits in its own box rather than filling the section as a background.
 * Mobile: image stacked above the text. That mobile arrangement is a stub
 * (no mobile designs exist yet) -- swap it by editing the `order-*` classes
 * below once real designs land, without touching the desktop layout.
 * 
 */
export default function HeroSplit({
    src,
    alt,
    pictureClassName = '',
    imageClassName = 'w-full h-full object-cover',
    sources = [],
    children = '',
}: HeroSplitProps) {
    const sourceList = sources.map((source, index) => (
        <source srcSet={source.srcSet} media={source.media} key={index} />
    ));

    return (
        <div className="hero-split flex w-full max-w-full flex-col mx-auto md:w-6xl lg:w-7xl lg:flex-row lg:items-stretch py-5">
            <picture
                className={`order-1 md:-mr-20 md:min-w-200 md:min-h-125 lg:order-2 lg:aspect-auto lg:flex-1 ${pictureClassName}`}
            >
                {sourceList}
                <img src={src} alt={alt} className={imageClassName} />
            </picture>
            <div className="hero-split-content order-2 flex w-full flex-col lg:order-1 lg:flex-1">
                <div className="hero-split-content__inner mx-auto flex h-full w-full max-w-3xl flex-col items-start justify-between gap-8 p-5 md:pr-0 lg:max-w-none">
                    {children}
                </div>
            </div>
        </div>
    );
}
