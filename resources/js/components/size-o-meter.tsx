/**
 * Do we need to import anything?
 * If we import usePage, we can pull the environment check into this component.
 */

export function SizeOMeter() {

    return (
        <div className="font-extrabold flex flex-row items-center justify-center gap-6 bg-green-500 md:bg-blue-500 lg:bg-amber-500 xl:bg-red-500 2xl:bg-purple-500 2xl:text-beluga">
                <div className="md:hidden ">Small</div>
                <div className="hidden md:max-lg:block">Medium</div>
                <div className="hidden lg:max-xl:block">Large</div>
                <div className="hidden xl:max-2xl:block">Extra Large</div>
                <div className="hidden 2xl:block text-beluga">2XL</div>
                <div>Screen: {window.screen.width} x {window.screen.height}</div>
                <div>Viewport: {window.innerWidth} x {window.innerHeight}</div>
        </div>
    );
}