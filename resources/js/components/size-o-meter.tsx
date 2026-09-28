/**
 * Do we need to import anything?
 * If we import usePage, we can pull the environment check into this component.
 */
import {
    useState,
    useEffect,
} from 'react';
import {
    cn
} from "@/lib/utils";

class SizeValue {
    constructor(
        public rem: number,
        public px: number,
    ){}
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

        // return a method that removes the event listener
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
                <div key={v.label} className={cn('flex flex-col', v.className ?? '')}>
                    <span className="text-2xl">{v.label}</span>
                    <div>
                        {v.gte && <div>starts: &gt;={v.gte?.rem}rem ({v.gte?.px}px)</div>}
                        {v.lt && (` ends: <${v.lt?.rem}rem (${v.lt?.px}px)`)}
                    </div>
                </div>
            )}
            <div className="flex flex-row gap-4">
                <div>Screen: {window.screen.width}px x {window.screen.height}px</div>
                <div>Viewport: {innerWidth}px x {innerHeight}px</div>
            </div>
        </div>
    );
}