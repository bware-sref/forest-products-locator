// components/hero.tsx
// For best results:
//  - pass your smallest mobile image for the <img>,
//  - order sources from largest to smallest and use "min-width" for media values,
// Also, I'm considering not allowing the "essential" styles to be overridden.

import React, 
{ 
    ImgHTMLAttributes,
    ReactNode,
    SourceHTMLAttributes
} from 'react';
import {
    cn
} from "@/lib/utils"


interface HeroProps extends ImgHTMLAttributes<HTMLPictureElement> {
    src: string;
    alt: string;
    className?: string;
    pictureClassName?: string;
    imageClassName?: string;
    sources?: SourceHTMLAttributes<HTMLSourceElement>[];
    children?: ReactNode;
}

export default function Hero({
    src,
    alt,
    // defaults moved to elements for use with cn()
    className = '',
    pictureClassName = '',
    imageClassName = '',
    sources = [],
    children = ''
}: HeroProps) {
    const sourceList = sources.map((source, index) => 
        <source srcSet={source.srcSet} media={source.media} key={index}/>
    );
    return (
        // grid-rows-[minmax(0,min-content)] is part of the magic that constrains row height to the content.
        <div className={cn('hero', "grid grid-cols-1 grid-rows-[minmax(0,min-content)] w-full max-w-full", className)}>
            {/**
             * Move text content before <picture> so screen readers see it first!
             */}
            <div className="hero-content flex flex-col w-full max-w-full col-start-1 row-start-1 z-10">
                <div className="hero-content__inner mx-auto flex flex-col gap-8 max-h-full w-full md:w-6xl lg:w-7xl max-w-full items-start p-5">
                    {children}
                </div>
            </div>
            {/*
            NOTE: to make the hero height governed by the height of the text container, we have to use 
            h-0 + min-h-full on the <picture>.
            h-0 is used by the CSS Grid engine when determining content height,
            i.e., this one's height is 0, use another child for height.
            min-h-full then allows the <picture> to grow without impacting the overall container height.
             */}
            <picture className={cn('col-start-1 row-start-1 block w-full h-0 min-h-full z-0', pictureClassName)}>
                {sourceList}
                <img src={src} alt={alt} className={cn('w-full h-full object-cover', imageClassName)} />
            </picture>
        </div>
    );
}