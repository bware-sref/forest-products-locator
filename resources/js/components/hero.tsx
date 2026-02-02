import React, { ImgHTMLAttributes, ReactNode, SourceHTMLAttributes } from 'react';

// define SourceProps so we can accept an array
// lint complained about this interface not adding any new members
// interface SourceProps extends SourceHTMLAttributes<HTMLSourceElement> {}

interface HeroProps extends ImgHTMLAttributes<HTMLPictureElement> {
    src: string;
    alt: string;
    pictureClassName?: string;
    imageClassName?: string;
    // sources?: SourceProps[];
    sources?: SourceHTMLAttributes<HTMLSourceElement>[];
    children?: ReactNode;
}

export default function Hero({src, alt, pictureClassName = '', imageClassName = '', sources = [], children = ''}:HeroProps) {
    const sourceList = sources.map(source => 
        <source srcSet={source.srcSet} media={source.media} />
    );
    return (
        <div className="grid grid-cols-1 grid-rows-1 w-full">
            <picture className={pictureClassName}>
                {sourceList}
                <img src={src} alt={alt} width="100%" height="auto" className={imageClassName} />
            </picture>
            <div className="hero-content w-full col-start-1 row-start-1 flex flex-col Xbg-sky-500/30">
                <div className="hero-content__inner mx-auto flex flex-col gap-8 h-full w-full lg:w-7xl max-w-[335px] lg:max-w-7xl items-start p-5 Xbg-amber-300/30">
                    {children}
                </div>
            </div>
        </div>
    );
}