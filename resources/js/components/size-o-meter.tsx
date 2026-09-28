/**
 * Do we need to import anything?
 * If we import usePage, we can pull the environment check into this component.
 */
import {
    useState,
    useEffect,
} from 'react';

type TwoNumbers = [number, number];

type GteLt = TwoNumbers | null | undefined;

// interface Size {
//     label: string;
//     lt?: SizeValue;
//     gte?: SizeValue;
// }
// interface SizeValue {
//     rem: number;
//     px: number;
// }

class SizeValue {
    constructor(
        public rem: number,
        public px: number,
    ){}
    
    // static create({
    //     rem,
    //     px,
    // }:{
    //     rem: number;
    //     px: number;
    // }): SizeValue {
    //     return new SizeValue(rem, px);
    // }
}
class Size {
    private constructor(
        public label : string,
        public className?: string,
        public lt?: SizeValue,
        public gte?: SizeValue,
    ){}

    static create({
        label,
        className,
        lt,
        gte,
    }: {
        label: string;
        className?: string;
        // these could have been labeled start and stop
        lt?: SizeValue;
        gte?: SizeValue;
    }): Size {
        return new Size(label, className, lt, gte);
    }
}


const sizes : Size[] = [
    Size.create({
        label: 'Regular',
        className: 'sm:hidden',
        lt: new SizeValue(40, 640),
    }),
    Size.create({
        label: 'Small',
        className: 'hidden sm:max-md:block',
        gte: new SizeValue(40, 640),
        lt: new SizeValue(48, 768),
    }),
    Size.create({
        label: 'Medium',
        className: 'hidden md:max-lg:block',
        gte: new SizeValue(48, 768),
        lt: new SizeValue(64, 1024),
    }),
    Size.create({
        label: 'Large',
        className: 'hidden lg:max-xl:block',
        gte: new SizeValue(64, 1024),
        lt: new SizeValue(80, 1280),
    }),
    Size.create({
        label: 'XL',
        className: 'hidden xl:max-2xl:block',
        gte: new SizeValue(80, 1280),
        lt: new SizeValue(96, 1536),
    }),
    Size.create({
        label: '2XL',
        className: 'hidden 2xl:block text-beluga',
        gte: new SizeValue(96, 1536),
    }),
    
];

function useViewSize() {
    const [viewSize, setViewSize] = useState({
        innerWidth: window.innerWidth,
        innerHeight: window.innerHeight,
    });

    useEffect(() => {
        const handleResize = () => {
            setViewSize({
                innerWidth: window.innerWidth,
                innerHeight: window.innerHeight,
            });
        }

        window.addEventListener('resize', handleResize);

        return () => {
            window.removeEventListener('resize', handleResize);
        }
    }, []);

    return viewSize;
}

export function SizeOMeter() {

    const { innerWidth, innerHeight } = useViewSize();

    return (
        <div className="font-extrabold flex flex-col text-center items-center justify-center gap-4 bg-green-500 sm:bg-cyan-500 md:bg-blue-500 lg:bg-amber-500 xl:bg-red-500 2xl:bg-purple-500 2xl:text-beluga">
            {sizes.map(v => 
                <div key={v.label} className={v.className ?? ''}>
                    {v.label} 
                    <div>(
                        {v.gte && (`lower: >=${v.gte?.rem}rem (${v.gte?.px}px)`)}
                        {v.lt && (` upper: <${v.lt?.rem}rem (${v.lt?.px}px)`)}
                    )</div>
                </div>
            )}
                {/* <div className="sm:hidden">
                    Regular (&lt;40rem (640px))
                </div>
                <div className="hidden sm:max-md:block">
                    Small (&gt;=40rem (640px), &lt;48rem (768px))
                </div>
                <div className="hidden md:max-lg:block">
                    Medium (&gt;=48rem (768px), &lt;64rem (1024px))
                </div>
                <div className="hidden lg:max-xl:block">
                    Large 1024
                    (&gt;=64rem (1024px), &lt;80rem (1280px))
                </div>
                <div className="hidden xl:max-2xl:block">
                    Extra Large 80 1280
                </div>
                <div className="hidden 2xl:block text-beluga">
                    2XL 96 1536
                </div> */}
                <div className="flex flex-row gap-4">
                    <div>Screen: {window.screen.width} x {window.screen.height}</div>
                    <div> Viewport: {innerWidth} x {innerHeight}</div>
                </div>
        </div>
    );
}